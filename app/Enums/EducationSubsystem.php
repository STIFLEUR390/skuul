<?php

namespace App\Enums;

/**
 * The language subsystem a class belongs to.
 *
 * Cameroon runs two school systems side by side: francophone and anglophone.
 */
enum EducationSubsystem: string
{
    case Francophone = 'francophone';

    case Anglophone = 'anglophone';

    case Bilingual = 'bilingual';

    public function label(): string
    {
        return match ($this) {
            self::Francophone => 'Francophone',
            self::Anglophone => 'Anglophone',
            self::Bilingual => 'Bilingual',
        };
    }

    /**
     * The systems this one teaches.
     *
     * @return array<int, self>
     */
    public function systems(): array
    {
        return match ($this) {
            self::Francophone => [self::Francophone],
            self::Anglophone => [self::Anglophone],
            self::Bilingual => [self::Francophone, self::Anglophone],
        };
    }
}
