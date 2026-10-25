<?php

namespace App\Enums;

/**
 * The mastery level a learner reached on one competency.
 *
 * The three levels are the ones the MINEDUB report card prints: acquired, in
 * progress, not acquired.
 */
enum CompetencyLevel: string
{
    case Acquired = 'acquired';

    case InProgress = 'in_progress';

    case NotAcquired = 'not_acquired';

    public function label(): string
    {
        return match ($this) {
            self::Acquired => 'Acquired',
            self::InProgress => 'In progress',
            self::NotAcquired => 'Not acquired',
        };
    }

    /**
     * Check if the learner masters this competency.
     */
    public function isAcquired(): bool
    {
        return $this === self::Acquired;
    }
}
