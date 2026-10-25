<?php

namespace App\Console\Commands;

use App\Enums\AcademicStructureStatus;
use App\Enums\AssessmentMode;
use App\Enums\EducationSubsystem;
use App\Enums\SchoolCategory;
use App\Models\AcademicLevel;
use App\Models\School;
use App\Services\School\CameroonStructure;
use Illuminate\Console\Command;

/**
 * Fill a school with the classes of the Cameroonian school map.
 *
 * The command is safe to run again: a class is matched by its code, so names
 * and positions are refreshed instead of duplicated.
 */
class SeedCameroonStructure extends Command
{
    /**
     * @var string
     */
    protected $signature = 'school:cameroon-structure
        {--school= : School id, defaults to the only school}
        {--subsystem=francophone : francophone, anglophone or bilingual}
        {--category=* : nursery, primary, secondary-general, secondary-technical, or all}';

    /**
     * @var string
     */
    protected $description = 'Create the nursery, primary, secondary and technical classes of a Cameroonian school';

    public function handle(): int
    {
        $subsystem = EducationSubsystem::tryFrom((string) $this->option('subsystem'));

        if ($subsystem === null) {
            $this->error('The subsystem must be francophone, anglophone or bilingual.');

            return self::FAILURE;
        }

        $categories = $this->categories();

        if ($categories === []) {
            $this->error('Name at least one --category, or all.');

            return self::FAILURE;
        }

        $school = $this->school();

        if ($school === null) {
            $this->error('Name the school with --school when more than one exists.');

            return self::FAILURE;
        }

        $created = 0;

        foreach ($categories as $category) {
            foreach (CameroonStructure::groups($category) as $group) {
                $parent = AcademicLevel::updateOrCreate(
                    ['school_id' => $school->id, 'code' => $group['code']],
                    [
                        'name' => $group['name'],
                        'is_group' => true,
                        'parent_id' => null,
                        'position' => 0,
                        'status' => AcademicStructureStatus::Active,
                    ],
                );

                $position = 0;

                foreach ($this->languages($subsystem) as $language) {
                    foreach ($group[$language] as $level) {
                        AcademicLevel::updateOrCreate(
                            ['school_id' => $school->id, 'code' => $level['code']],
                            [
                                'name' => $level['name'],
                                'is_group' => false,
                                'parent_id' => $parent->id,
                                'position' => ++$position,
                                'status' => AcademicStructureStatus::Active,
                                'assessment_mode' => self::assessmentMode($category),
                            ],
                        );

                        $created++;
                    }
                }
            }
        }

        $this->info("{$school->name}: {$created} classes in place.");

        return self::SUCCESS;
    }

    /**
     * The way a category reports on its learners.
     *
     * Nursery and primary assess competencies; the rest mark subjects.
     */
    private static function assessmentMode(SchoolCategory $category): AssessmentMode
    {
        return match ($category) {
            SchoolCategory::Nursery, SchoolCategory::Primary => AssessmentMode::Competency,
            default => AssessmentMode::Marks,
        };
    }

    /**
     * The subsystems whose class names the school gets.
     *
     * @return array<int, string>
     */
    private function languages(EducationSubsystem $subsystem): array
    {
        return $subsystem === EducationSubsystem::Anglophone ? ['en'] : ['fr', 'en'];
    }

    /**
     * @return array<int, SchoolCategory>
     */
    private function categories(): array
    {
        $names = $this->option('category');

        if ($names === ['all']) {
            return SchoolCategory::cases();
        }

        return array_values(array_filter(array_map(
            fn (string $name): ?SchoolCategory => SchoolCategory::tryFrom($name),
            (array) $names,
        )));
    }

    private function school(): ?School
    {
        $id = $this->option('school');

        if ($id !== null) {
            return School::query()->find($id);
        }

        return School::query()->count() === 1 ? School::query()->first() : null;
    }
}
