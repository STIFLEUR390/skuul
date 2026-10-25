<?php

namespace App\Livewire;

use App\Enums\CompetencyLevel;
use App\Models\AcademicLevel;
use App\Models\AcademicPeriod;
use App\Models\CompetencyAssessment;
use App\Models\CompetencyDomain;
use App\Models\StudentRecord;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Record the mastery level a learner reached on each competency of the period.
 *
 * A level set to the empty choice removes the assessment, so a teacher can
 * correct a mistake without leaving a stray row on the report card.
 */
class AssessCompetencies extends Component
{
    public StudentRecord $studentRecord;

    public string $academicPeriodId = '';

    /**
     * The chosen level per competency id.
     *
     * @var array<int, string>
     */
    public array $levels = [];

    /**
     * The teacher's comment per competency id.
     *
     * @var array<int, string>
     */
    public array $comments = [];

    public function mount(StudentRecord $studentRecord): void
    {
        Gate::authorize('view', $studentRecord);

        $this->studentRecord = $studentRecord;
        $this->academicPeriodId = (string) ($this->periods()->first()?->id ?? '');
        $this->fillFromSaved();
    }

    public function updatedAcademicPeriodId(): void
    {
        $this->fillFromSaved();
    }

    public function save(): void
    {
        Gate::authorize('manage', $this->studentRecord);

        $this->validate([
            'academicPeriodId' => ['required', 'integer', Rule::exists('academic_periods', 'id')->where('school_id', current_school_id())],
            'levels.*' => ['nullable', Rule::enum(CompetencyLevel::class)],
            'comments.*' => ['nullable', 'string', 'max:500'],
        ], [], ['academicPeriodId' => 'period']);

        $academicPeriodId = (int) $this->academicPeriodId;

        foreach ($this->competencyIds() as $competencyId) {
            $level = $this->levels[$competencyId] ?? null;
            $key = ['student_record_id' => $this->studentRecord->id, 'competency_id' => $competencyId, 'academic_period_id' => $academicPeriodId];

            if (blank($level)) {
                CompetencyAssessment::query()->where($key)->delete();

                continue;
            }

            CompetencyAssessment::query()->updateOrCreate($key, [
                'school_id' => current_school_id(),
                'level' => $level,
                'comment' => filled($this->comments[$competencyId] ?? null) ? $this->comments[$competencyId] : null,
                'assessed_by' => auth()->id(),
            ]);
        }

        session()->flash('success', 'The competency levels were saved.');
    }

    public function render(): View
    {
        return view('livewire.assess-competencies', [
            'domains' => $this->domains(),
            'periods' => $this->periods(),
            'assessmentLevel' => $this->level(),
            'levels' => CompetencyLevel::cases(),
        ]);
    }

    /** @return Collection<int, CompetencyDomain> */
    private function domains(): Collection
    {
        return CompetencyDomain::query()->inSchool()
            ->forLevel($this->studentRecord->academicCycleSection?->academic_level_id)
            ->with('competencies')
            ->orderBy('position')
            ->get();
    }

    /** @return array<int, int> */
    private function competencyIds(): array
    {
        return $this->domains()->flatMap(fn (CompetencyDomain $domain): array => $domain->competencies->pluck('id')->all())->all();
    }

    /** @return Collection<int, AcademicPeriod> */
    private function periods(): Collection
    {
        $academicYearId = $this->studentRecord->academicCycleSection?->academic_year_id;

        return AcademicPeriod::query()->inSchool()
            ->when($academicYearId !== null, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->orderBy('starts_on')
            ->orderBy('position')
            ->get();
    }

    private function level(): ?AcademicLevel
    {
        return $this->studentRecord->academicCycleSection?->academicLevel;
    }

    /**
     * Load what the period already holds into the form.
     */
    private function fillFromSaved(): void
    {
        $this->levels = [];
        $this->comments = [];

        if ($this->academicPeriodId === '') {
            return;
        }

        $saved = CompetencyAssessment::query()->inSchool()
            ->where('student_record_id', $this->studentRecord->id)
            ->where('academic_period_id', (int) $this->academicPeriodId)
            ->get();

        foreach ($saved as $assessment) {
            $this->levels[$assessment->competency_id] = $assessment->level->value;
            $this->comments[$assessment->competency_id] = (string) $assessment->comment;
        }
    }
}
