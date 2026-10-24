<?php

namespace App\Enums;

/**
 * What a school teaches: nursery, primary, secondary, higher education or a
 * vocational centre. Each one is supervised by a different ministry.
 */
enum SchoolCategory: string
{
    case Nursery = 'nursery';

    case Primary = 'primary';

    case SecondaryGeneral = 'secondary-general';

    case SecondaryTechnical = 'secondary-technical';

    case HigherEducation = 'higher-education';

    case VocationalCentre = 'vocational-centre';

    public function label(): string
    {
        return match ($this) {
            self::Nursery => 'Nursery',
            self::Primary => 'Primary',
            self::SecondaryGeneral => 'Secondary general',
            self::SecondaryTechnical => 'Secondary technical',
            self::HigherEducation => 'Higher education',
            self::VocationalCentre => 'Vocational centre',
        };
    }
}
