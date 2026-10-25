<?php

namespace App\Enums;

/**
 * Say how a level reports on its learners.
 *
 * Nursery and primary work by competency (APC); secondary works by marks.
 */
enum AssessmentMode: string
{
    /**
     * Subjects are marked, then averaged out of 20.
     */
    case Marks = 'marks';

    /**
     * Competencies are graded on a mastery level, with no mark out of 20.
     */
    case Competency = 'competency';

    public function label(): string
    {
        return match ($this) {
            self::Marks => 'Marks out of 20',
            self::Competency => 'Competency based',
        };
    }
}
