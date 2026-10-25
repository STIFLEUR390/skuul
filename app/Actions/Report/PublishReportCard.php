<?php

namespace App\Actions\Report;

use App\Actions\Audit\RecordAuditEvent;
use App\Enums\AcademicPeriodStatus;
use App\Enums\AssessmentMode;
use App\Enums\AuditAction;
use App\Exceptions\InvalidValueException;
use App\Models\AcademicPeriod;
use App\Models\CompetencyAssessment;
use App\Models\ReportCardSnapshot;
use App\Models\ResultSnapshot;
use App\Models\StudentRecord;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PublishReportCard
{
    public function __construct(private RecordAuditEvent $audit) {}

    /** @throws InvalidValueException */
    public function publish(StudentRecord $studentRecord, AcademicPeriod $academicPeriod, User $actor, ?string $reason = null): ReportCardSnapshot
    {
        if ($academicPeriod->academic_year_id === null) {
            throw new InvalidValueException('Choose a student and academic period from the same school.');
        }

        // A learner who moved campus is still reported by the campus that
        // taught the period. Their results there prove they studied it.
        if ($studentRecord->school_id !== $academicPeriod->school_id
            && $this->latestResults($studentRecord, $academicPeriod)->isEmpty()
            && $this->competencyAssessments($studentRecord, $academicPeriod)->isEmpty()) {
            throw new InvalidValueException('Choose a student and academic period from the same school.');
        }

        if (!in_array($academicPeriod->status, [AcademicPeriodStatus::Closing, AcademicPeriodStatus::Closed, AcademicPeriodStatus::Archived], true)) {
            throw new InvalidValueException('Report cards can be published only while an academic period is closing or finished.');
        }

        return DB::transaction(function () use ($studentRecord, $academicPeriod, $actor, $reason): ReportCardSnapshot {
            $results = $this->latestResults($studentRecord, $academicPeriod);
            $competencyMode = $this->assessesCompetencies($studentRecord);

            if ($competencyMode) {
                $assessments = $this->competencyAssessments($studentRecord, $academicPeriod);

                if ($assessments->isEmpty()) {
                    throw new InvalidValueException('Assess at least one competency before publishing this report card.');
                }
            } else {
                $assessments = collect();

                if ($results->isEmpty()) {
                    throw new InvalidValueException('Publish at least one subject result before publishing this report card.');
                }
            }

            $previous = ReportCardSnapshot::query()
                ->where('student_record_id', $studentRecord->id)
                ->where('academic_period_id', $academicPeriod->id)
                ->lockForUpdate()
                ->orderByDesc('revision')
                ->first();
            $payload = $competencyMode
                ? $this->competencyPayload($studentRecord, $academicPeriod, $assessments)
                : $this->payload($studentRecord, $academicPeriod, $results);
            $reportCard = ReportCardSnapshot::create([
                'school_id' => $academicPeriod->school_id,
                'student_record_id' => $studentRecord->id,
                'academic_year_id' => $academicPeriod->academic_year_id,
                'academic_period_id' => $academicPeriod->id,
                'revision' => $previous === null ? 1 : $previous->revision + 1,
                'average_percentage' => $payload['summary']['average_percentage'],
                'payload' => $payload,
                'reason' => $reason,
                'published_at' => now(),
                'published_by' => $actor->id,
            ]);
            $this->audit->record($previous === null ? AuditAction::ReportCardPublished : AuditAction::ReportCardRevised, $reportCard, ['student_record_id' => $studentRecord->id, 'academic_period_id' => $academicPeriod->id, 'revision' => $reportCard->revision], $actor);

            return $reportCard;
        });
    }

    /** @return Collection<int, ResultSnapshot> */
    private function latestResults(StudentRecord $studentRecord, AcademicPeriod $academicPeriod): Collection
    {
        return ResultSnapshot::query()->where('student_record_id', $studentRecord->id)
            ->approved()
            ->whereHas('courseOffering', fn ($query) => $query->where('academic_period_id', $academicPeriod->id))
            ->with('courseOffering.subject:id,name,short_name')
            ->orderBy('course_offering_id')->orderByDesc('revision')->get()->unique('course_offering_id')->values();
    }

    /**
     * Answer whether the learner's level reports by competency instead of marks.
     */
    private function assessesCompetencies(StudentRecord $studentRecord): bool
    {
        return $studentRecord->academicCycleSection?->academicLevel?->assessment_mode === AssessmentMode::Competency;
    }

    /** @return Collection<int, CompetencyAssessment> */
    private function competencyAssessments(StudentRecord $studentRecord, AcademicPeriod $academicPeriod): Collection
    {
        return CompetencyAssessment::query()->where('student_record_id', $studentRecord->id)
            ->where('academic_period_id', $academicPeriod->id)
            ->with('competency.domain')
            ->get();
    }

    /**
     * Build the card of a competency-assessed level: no mark, only mastery levels.
     *
     * @param  Collection<int, CompetencyAssessment>  $assessments
     * @return array<string, mixed>
     */
    private function competencyPayload(StudentRecord $studentRecord, AcademicPeriod $academicPeriod, Collection $assessments): array
    {
        $domains = $assessments
            ->sortBy(fn (CompetencyAssessment $assessment): string => sprintf('%04d-%04d', $assessment->competency->domain->position, $assessment->competency->position))
            ->groupBy(fn (CompetencyAssessment $assessment): string => $assessment->competency->domain->name)
            ->map(fn (Collection $group): array => [
                'domain' => $group->first()->competency->domain->name,
                'domain_en' => $group->first()->competency->domain->name_en,
                'competencies' => $group->map(fn (CompetencyAssessment $assessment): array => [
                    'competency_id' => $assessment->competency_id,
                    'name' => $assessment->competency->name,
                    'name_en' => $assessment->competency->name_en,
                    'level' => $assessment->level->value,
                    'level_label' => $assessment->level->label(),
                    'comment' => $assessment->comment,
                ])->values()->all(),
            ])->values()->all();

        return [
            'assessment_mode' => AssessmentMode::Competency->value,
            'student' => ['id' => $studentRecord->id, 'admission_number' => $studentRecord->admission_number],
            'academic_period' => ['id' => $academicPeriod->id, 'name' => $academicPeriod->label ?? $academicPeriod->name],
            'results' => [],
            'competencies' => $domains,
            'summary' => [
                'subjects_reported' => 0,
                'total_coefficients' => 0,
                'average_percentage' => null,
                'average_out_of_20' => null,
                'competencies_assessed' => $assessments->count(),
            ],
        ];
    }

    /** @param Collection<int, ResultSnapshot> $results @return array<string, mixed> */
    private function payload(StudentRecord $studentRecord, AcademicPeriod $academicPeriod, Collection $results): array
    {
        // A subject counts by its coefficient, the way the report card prints it.
        $reported = $results->filter(fn (ResultSnapshot $result): bool => $result->percentage !== null);
        $coefficientOf = fn (ResultSnapshot $result): float => max((float) $result->courseOffering->coefficient, 0.0);
        $totalCoefficients = round($reported->sum($coefficientOf), 2);
        $average = $totalCoefficients > 0
            ? round($reported->sum(fn (ResultSnapshot $result): float => $result->percentage * $coefficientOf($result)) / $totalCoefficients, 2)
            : null;

        return ['assessment_mode' => AssessmentMode::Marks->value, 'student' => ['id' => $studentRecord->id, 'admission_number' => $studentRecord->admission_number], 'academic_period' => ['id' => $academicPeriod->id, 'name' => $academicPeriod->label ?? $academicPeriod->name], 'results' => $results->map(fn (ResultSnapshot $result): array => ['course_offering_id' => $result->course_offering_id, 'source_result_snapshot_id' => $result->id, 'source_revision' => $result->revision, 'subject' => ['name' => $result->courseOffering->subject->name, 'short_name' => $result->courseOffering->subject->short_name], 'percentage' => $result->percentage, 'coefficient' => $coefficientOf($result), 'out_of_20' => $result->percentage === null ? null : round($result->percentage / 5, 2), 'result' => $result->payload])->all(), 'summary' => ['subjects_reported' => $results->count(), 'total_coefficients' => $totalCoefficients, 'average_percentage' => $average, 'average_out_of_20' => $average === null ? null : round($average / 5, 2)]];
    }
}
