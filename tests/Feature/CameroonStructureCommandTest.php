<?php

namespace Tests\Feature;

use App\Models\AcademicLevel;
use App\Traits\FeatureTestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A Cameroonian school can be filled with the classes of both subsystems.
 */
class CameroonStructureCommandTest extends TestCase
{
    use FeatureTestTrait;
    use RefreshDatabase;

    public function test_a_bilingual_secondary_school_gets_both_subsystems(): void
    {
        $school = $this->workingSchool();
        $before = AcademicLevel::query()->count();

        $this->artisan('school:cameroon-structure', [
            '--school' => $school->id,
            '--subsystem' => 'bilingual',
            '--category' => ['secondary-general'],
        ])->assertSuccessful();

        $this->assertSame(2, AcademicLevel::query()->where('is_group', true)->count());
        $this->assertSame($before + 16, AcademicLevel::query()->count());
        $this->assertDatabaseHas('academic_levels', ['school_id' => $school->id, 'code' => '6e', 'name' => '6e']);
        $this->assertDatabaseHas('academic_levels', ['school_id' => $school->id, 'code' => 'form-1', 'name' => 'Form 1']);
    }

    public function test_the_classes_sit_under_their_cycle(): void
    {
        $school = $this->workingSchool();

        $this->artisan('school:cameroon-structure', [
            '--school' => $school->id,
            '--subsystem' => 'bilingual',
            '--category' => ['secondary-general'],
        ])->assertSuccessful();

        $lower = AcademicLevel::query()->where('code', 'group-lower-secondary')->firstOrFail();
        $upper = AcademicLevel::query()->where('code', 'group-upper-secondary')->firstOrFail();

        $this->assertSame(9, $lower->children()->count());
        $this->assertSame(5, $upper->children()->count());
    }

    public function test_running_it_again_does_not_duplicate_the_classes(): void
    {
        $school = $this->workingSchool();

        $options = [
            '--school' => $school->id,
            '--subsystem' => 'anglophone',
            '--category' => ['primary'],
        ];

        $this->artisan('school:cameroon-structure', $options)->assertSuccessful();
        $afterFirst = AcademicLevel::query()->count();

        $this->artisan('school:cameroon-structure', $options)->assertSuccessful();

        $this->assertSame($afterFirst, AcademicLevel::query()->count());
        $this->assertSame(1, AcademicLevel::query()->where('is_group', true)->count());
        $this->assertSame(6, AcademicLevel::query()->whereNotNull('parent_id')->count());
        $this->assertDatabaseHas('academic_levels', ['code' => 'class-1', 'name' => 'Class 1']);
        $this->assertDatabaseMissing('academic_levels', ['code' => 'sil']);
    }

    public function test_it_refuses_an_unknown_subsystem(): void
    {
        $school = $this->workingSchool();
        $before = AcademicLevel::query()->count();

        $this->artisan('school:cameroon-structure', [
            '--school' => $school->id,
            '--subsystem' => 'germanophone',
            '--category' => ['primary'],
        ])->assertFailed();

        $this->assertSame($before, AcademicLevel::query()->count());
    }
}
