<?php

namespace Tests\Feature;

use App\Enums\AcademicPeriodType;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Traits\FeatureTestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The Cameroonian year opens with its trimesters, sequences and breaks.
 */
class CameroonYearTest extends TestCase
{
    use FeatureTestTrait;
    use RefreshDatabase;

    public function test_the_year_holds_three_trimesters_of_two_sequences(): void
    {
        $school = $this->workingSchool();

        $this->artisan('academic:year-cameroon', [
            '--school' => $school->id,
            '--year' => 2025,
            '--start' => '2025-09-08',
        ])->assertSuccessful();

        $trimesters = AcademicPeriod::query()
            ->ofType([AcademicPeriodType::Trimester])
            ->ordered()
            ->get();

        $this->assertCount(3, $trimesters);

        foreach ($trimesters as $trimester) {
            $sequences = $trimester->children()->ofType([AcademicPeriodType::Sequence])->ordered()->get();

            $this->assertCount(2, $sequences, "{$trimester->name} should hold two sequences.");
            $this->assertTrue($sequences[0]->starts_on->gte($trimester->starts_on));
            $this->assertTrue($sequences[1]->ends_on->lte($trimester->ends_on));
            $this->assertTrue($sequences[0]->ends_on->lt($sequences[1]->starts_on));
        }
    }

    public function test_the_official_calendar_of_2025_and_2026_is_reproduced(): void
    {
        $school = $this->workingSchool();

        $this->artisan('academic:year-cameroon', [
            '--school' => $school->id,
            '--year' => 2025,
            '--start' => '2025-09-08',
        ])->assertSuccessful();

        $year = AcademicYear::query()->where('start_year', 2025)->firstOrFail();
        $this->assertSame('2025-09-08', $year->starts_on->toDateString());
        $this->assertSame('2026-07-31', $year->ends_on->toDateString());

        $christmas = AcademicPeriod::query()->ofType([AcademicPeriodType::Holiday])->where('name', 'Christmas break')->firstOrFail();
        $this->assertSame('2025-12-19', $christmas->starts_on->toDateString());
        $this->assertSame('2026-01-05', $christmas->ends_on->toDateString());

        $easter = AcademicPeriod::query()->ofType([AcademicPeriodType::Holiday])->where('name', 'Easter break')->firstOrFail();
        $this->assertSame('2026-04-02', $easter->starts_on->toDateString());
        $this->assertSame('2026-04-20', $easter->ends_on->toDateString());
    }

    public function test_a_break_is_not_a_teaching_period(): void
    {
        $school = $this->workingSchool();

        $this->artisan('academic:year-cameroon', [
            '--school' => $school->id,
            '--year' => 2025,
        ])->assertSuccessful();

        foreach (AcademicPeriod::query()->ofType([AcademicPeriodType::Holiday])->get() as $holiday) {
            $this->assertFalse($holiday->isTeachingPeriod(), "{$holiday->name} should not expect attendance.");
        }

        $this->assertTrue(AcademicPeriod::query()->ofType([AcademicPeriodType::Sequence])->firstOrFail()->isTeachingPeriod());
    }

    public function test_running_it_again_does_not_duplicate_the_calendar(): void
    {
        $school = $this->workingSchool();
        $options = ['--school' => $school->id, '--year' => 2025, '--start' => '2025-09-08'];

        $this->artisan('academic:year-cameroon', $options)->assertSuccessful();
        $periodsAfterFirst = AcademicPeriod::query()->count();
        $yearsAfterFirst = AcademicYear::query()->count();

        $this->artisan('academic:year-cameroon', $options)->assertSuccessful();

        $this->assertSame($periodsAfterFirst, AcademicPeriod::query()->count());
        $this->assertSame($yearsAfterFirst, AcademicYear::query()->count());
        // 3 trimesters, 6 sequences, 6 examination windows and 2 breaks.
        $this->assertSame(17, AcademicPeriod::query()->where('academic_year_id', AcademicYear::query()->where('start_year', 2025)->value('id'))->count());
    }

    public function test_every_examination_window_sits_inside_its_sequence(): void
    {
        $school = $this->workingSchool();

        $this->artisan('academic:year-cameroon', ['--school' => $school->id, '--year' => 2025])->assertSuccessful();

        $windows = AcademicPeriod::query()->ofType([AcademicPeriodType::ExamWindow])->with('parent')->get();

        $this->assertCount(6, $windows);

        foreach ($windows as $window) {
            $this->assertNotNull($window->parent);
            $this->assertTrue(Carbon::parse($window->starts_on)->gte($window->parent->starts_on));
            $this->assertTrue(Carbon::parse($window->ends_on)->lte($window->parent->ends_on));
        }
    }
}
