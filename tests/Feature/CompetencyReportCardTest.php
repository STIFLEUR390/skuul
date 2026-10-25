<?php

namespace Tests\Feature;

use App\Actions\Report\PublishReportCard;
use App\Enums\AcademicPeriodStatus;
use App\Enums\AssessmentMode;
use App\Enums\CompetencyLevel;
use App\Exceptions\InvalidValueException;
use App\Models\AcademicCycleSection;
use App\Models\AcademicLevel;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\Competency;
use App\Models\CompetencyAssessment;
use App\Models\CompetencyDomain;
use App\Models\CourseOffering;
use App\Models\ResultSnapshot;
use App\Models\StudentRecord;
use App\Models\Subject;
use App\Traits\FeatureTestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetencyReportCardTest extends TestCase
{
    use FeatureTestTrait;
    use RefreshDatabase;

    public function test_a_nursery_report_card_shows_competency_levels_and_no_mark_out_of_twenty(): void
    {
        $this->authorized_user(['read report', 'create report']);
        $school = $this->workingSchool();
        $actor = $this->memberOf($school);
        [$student, $period] = $this->competencyLearner($school->id);
        $domain = CompetencyDomain::create(['school_id' => $school->id, 'academic_level_id' => $student->academicCycleSection->academic_level_id, 'name' => 'Langage', 'name_en' => 'Language', 'position' => 1]);
        $competency = Competency::create(['competency_domain_id' => $domain->id, 'name' => 'Nommer les couleurs', 'position' => 1]);
        CompetencyAssessment::create(['school_id' => $school->id, 'student_record_id' => $student->id, 'competency_id' => $competency->id, 'academic_period_id' => $period->id, 'level' => CompetencyLevel::Acquired, 'comment' => 'Très bien']);

        $reportCard = app(PublishReportCard::class)->publish($student, $period, $actor);

        $this->assertNull($reportCard->average_percentage);
        $this->assertSame(AssessmentMode::Competency->value, $reportCard->payload['assessment_mode']);
        $this->assertSame([], $reportCard->payload['results']);
        $this->assertNull($reportCard->payload['summary']['average_out_of_20']);
        $this->assertSame(1, $reportCard->payload['summary']['competencies_assessed']);
        $this->assertSame('Langage', $reportCard->payload['competencies'][0]['domain']);
        $this->assertSame('Acquired', $reportCard->payload['competencies'][0]['competencies'][0]['level_label']);

        $this->get(route('report-cards.show', $reportCard))
            ->assertOk()
            ->assertSee('Competency levels')
            ->assertSee('Nommer les couleurs')
            ->assertSee('Très bien')
            ->assertSee('Competencies assessed')
            ->assertDontSee('Mark /20');
    }

    public function test_a_secondary_report_card_still_marks_subjects_out_of_twenty(): void
    {
        $this->authorized_user(['read report', 'create report']);
        $school = $this->workingSchool();
        $actor = $this->memberOf($school);
        $academicYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $period = AcademicPeriod::factory()->create(['school_id' => $school->id, 'academic_year_id' => $academicYear->id, 'status' => AcademicPeriodStatus::Closing]);
        $level = AcademicLevel::factory()->create(['school_id' => $school->id]);
        $section = AcademicCycleSection::factory()->create(['school_id' => $school->id, 'academic_year_id' => $academicYear->id, 'academic_level_id' => $level->id]);
        $student = StudentRecord::factory()->create(['school_id' => $school->id, 'academic_cycle_section_id' => $section->id]);
        $offering = CourseOffering::factory()->create(['school_id' => $school->id, 'academic_year_id' => $academicYear->id, 'academic_period_id' => $period->id, 'academic_level_id' => $level->id, 'subject_id' => Subject::factory()->create(['school_id' => $school->id])->id]);
        ResultSnapshot::create(['school_id' => $school->id, 'student_record_id' => $student->id, 'course_offering_id' => $offering->id, 'revision' => 1, 'percentage' => 60, 'payload' => ['percentage' => 60], 'published_at' => now()]);

        $reportCard = app(PublishReportCard::class)->publish($student, $period, $actor);

        $this->assertSame(AssessmentMode::Marks->value, $reportCard->payload['assessment_mode']);
        $this->assertEquals(12.0, $reportCard->payload['summary']['average_out_of_20']);

        $this->get(route('report-cards.show', $reportCard))
            ->assertOk()
            ->assertSee('Subject results')
            ->assertSee('Mark /20')
            ->assertDontSee('Competency levels');
    }

    public function test_a_competency_level_cannot_publish_a_card_before_any_competency_is_assessed(): void
    {
        $school = $this->workingSchool();
        $actor = $this->memberOf($school);
        [$student, $period] = $this->competencyLearner($school->id);

        $this->expectException(InvalidValueException::class);

        app(PublishReportCard::class)->publish($student, $period, $actor);
    }

    /**
     * A learner placed in a level that reports by competency.
     *
     * @return array{0: StudentRecord, 1: AcademicPeriod}
     */
    private function competencyLearner(int $schoolId): array
    {
        $academicYear = AcademicYear::factory()->create(['school_id' => $schoolId]);
        $period = AcademicPeriod::factory()->create(['school_id' => $schoolId, 'academic_year_id' => $academicYear->id, 'status' => AcademicPeriodStatus::Closing]);
        $level = AcademicLevel::factory()->create(['school_id' => $schoolId, 'assessment_mode' => AssessmentMode::Competency]);
        $section = AcademicCycleSection::factory()->create(['school_id' => $schoolId, 'academic_year_id' => $academicYear->id, 'academic_level_id' => $level->id]);
        $student = StudentRecord::factory()->create(['school_id' => $schoolId, 'academic_cycle_section_id' => $section->id]);

        return [$student->load('academicCycleSection.academicLevel'), $period];
    }
}
