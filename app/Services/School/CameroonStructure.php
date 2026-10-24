<?php

namespace App\Services\School;

use App\Enums\SchoolCategory;

/**
 * The classes a Cameroonian school is made of, in both subsystems.
 *
 * The names follow the school map of law 98/004 of 14 April 1998: nursery,
 * primary, the two secondary cycles and the technical route. The francophone
 * and anglophone routes do not line up class for class (four lower-secondary
 * years in French, five in English), so each subsystem keeps its own list.
 *
 * @phpstan-type LevelList array<int, array{code: string, name: string}>
 * @phpstan-type Group array{code: string, name: string, fr: LevelList, en: LevelList}
 */
class CameroonStructure
{
    /**
     * The level groups and the classes inside them.
     *
     * @return array<int, Group>
     */
    public static function groups(SchoolCategory $category): array
    {
        return match ($category) {
            SchoolCategory::Nursery => [
                self::group(
                    'group-nursery',
                    'Maternel / Nursery',
                    [['ps', 'Petite Section'], ['ms', 'Moyenne Section'], ['gs', 'Grande Section']],
                    [['pre-nursery', 'Pre-nursery'], ['nursery-1', 'Nursery 1'], ['nursery-2', 'Nursery 2']],
                ),
            ],
            SchoolCategory::Primary => [
                self::group(
                    'group-primary',
                    'Primaire / Primary',
                    [['sil', 'SIL'], ['cp', 'CP'], ['ce1', 'CE1'], ['ce2', 'CE2'], ['cm1', 'CM1'], ['cm2', 'CM2']],
                    [['class-1', 'Class 1'], ['class-2', 'Class 2'], ['class-3', 'Class 3'], ['class-4', 'Class 4'], ['class-5', 'Class 5'], ['class-6', 'Class 6']],
                ),
            ],
            SchoolCategory::SecondaryGeneral => [
                self::group(
                    'group-lower-secondary',
                    'Premier cycle / Lower Secondary',
                    [['6e', '6e'], ['5e', '5e'], ['4e', '4e'], ['3e', '3e']],
                    [['form-1', 'Form 1'], ['form-2', 'Form 2'], ['form-3', 'Form 3'], ['form-4', 'Form 4'], ['form-5', 'Form 5']],
                ),
                self::group(
                    'group-upper-secondary',
                    'Second cycle / Upper Secondary',
                    [['2nde', '2nde'], ['1ere', '1ère'], ['terminale', 'Terminale']],
                    [['lower-sixth', 'Lower Sixth'], ['upper-sixth', 'Upper Sixth']],
                ),
            ],
            SchoolCategory::SecondaryTechnical => [
                self::group(
                    'group-technical',
                    'Technique / Technical',
                    [
                        ['cap-1', 'CAP 1'], ['cap-2', 'CAP 2'], ['cap-3', 'CAP 3'], ['cap-4', 'CAP 4'],
                        ['2nde-technique', '2nde technique'], ['1ere-technique', '1ère technique'], ['terminale-technique', 'Terminale technique'],
                    ],
                    [
                        ['technical-form-1', 'Technical Form 1'], ['technical-form-2', 'Technical Form 2'],
                        ['technical-form-3', 'Technical Form 3'], ['technical-form-4', 'Technical Form 4'],
                        ['technical-form-5', 'Technical Form 5'], ['technical-lower-sixth', 'Technical Lower Sixth'],
                        ['technical-upper-sixth', 'Technical Upper Sixth'],
                    ],
                ),
            ],
            SchoolCategory::HigherEducation, SchoolCategory::VocationalCentre => [],
        };
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $fr
     * @param  array<int, array{0: string, 1: string}>  $en
     * @return Group
     */
    private static function group(string $code, string $name, array $fr, array $en): array
    {
        return [
            'code' => $code,
            'name' => $name,
            'fr' => self::levels($fr),
            'en' => self::levels($en),
        ];
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $levels
     * @return LevelList
     */
    private static function levels(array $levels): array
    {
        return array_map(
            fn (array $level): array => ['code' => $level[0], 'name' => $level[1]],
            $levels,
        );
    }
}
