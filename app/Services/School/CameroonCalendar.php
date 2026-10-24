<?php

namespace App\Services\School;

use Illuminate\Support\Carbon;

/**
 * The shape of a Cameroonian school year.
 *
 * A year runs from the September rentrée to the end of July, in three
 * trimesters of two sequences each. The calendar runs itself from a start
 * date, so a school never has to type twenty dates by hand.
 */
class CameroonCalendar
{
    public const TRIMESTERS = 3;

    public const SEQUENCES_PER_TRIMESTER = 2;

    /**
     * Days of examinations that close each sequence.
     */
    public const EXAM_DAYS = 3;

    /**
     * Build the calendar of the year that starts in September of the given year.
     *
     * @return array{
     *     starts_on: string,
     *     ends_on: string,
     *     trimesters: array<int, array{starts_on: string, ends_on: string, sequences: array<int, array{starts_on: string, ends_on: string, exam_starts_on: string, exam_ends_on: string}>}>,
     *     breaks: array<int, array{name: string, starts_on: string, ends_on: string}>
     * }
     */
    public function plan(int $startYear, ?string $rentree = null): array
    {
        // The rentrée is the first Monday after 1 September, the day the
        // ministry's calendar opened the year in 2025 (8 September).
        $start = $rentree === null
            ? Carbon::create($startYear, 9, 1)->startOfDay()->next(Carbon::MONDAY)
            : Carbon::parse($rentree)->startOfDay();

        if (!$start->isMonday()) {
            $start = $start->next(Carbon::MONDAY);
        }

        $end = Carbon::create($startYear + 1, 7, 31)->startOfDay();

        // The break that straddles the new year is fixed: school stops on
        // 19 December and the second trimester opens after the January rentrée.
        $christmas = [
            'name' => 'christmas',
            'starts_on' => Carbon::create($startYear, 12, 19),
            'ends_on' => $this->firstMondayOfJanuary($startYear + 1),
        ];

        // The second break follows Easter, which moves every year.
        $easter = $this->easterSunday($startYear + 1);
        $spring = [
            'name' => 'easter',
            'starts_on' => $easter->copy()->subDays(3),
            'ends_on' => $easter->copy()->addDays(15),
        ];

        $spans = [
            [$start, $christmas['starts_on']->copy()->subDay()],
            [$christmas['ends_on']->copy()->addDay(), $spring['starts_on']->copy()->subDay()],
            [$spring['ends_on']->copy()->addDay(), $end],
        ];

        return [
            'starts_on' => $start->toDateString(),
            'ends_on' => $end->toDateString(),
            'trimesters' => array_map(fn (array $span): array => $this->trimester(...$span), $spans),
            'breaks' => [
                ['name' => $christmas['name'], 'starts_on' => $christmas['starts_on']->toDateString(), 'ends_on' => $christmas['ends_on']->toDateString()],
                ['name' => $spring['name'], 'starts_on' => $spring['starts_on']->toDateString(), 'ends_on' => $spring['ends_on']->toDateString()],
            ],
        ];
    }

    /**
     * Split one trimester into its sequences, each closed by examinations.
     *
     * @return array{starts_on: string, ends_on: string, sequences: array<int, array{starts_on: string, ends_on: string, exam_starts_on: string, exam_ends_on: string}>}
     */
    private function trimester(Carbon $startsOn, Carbon $endsOn): array
    {
        $days = (int) $startsOn->diffInDays($endsOn) + 1;
        $half = (int) ceil($days / self::SEQUENCES_PER_TRIMESTER);

        $sequences = [];

        for ($index = 0; $index < self::SEQUENCES_PER_TRIMESTER; $index++) {
            $sequenceStart = $startsOn->copy()->addDays($index * $half);
            $sequenceEnd = $sequenceStart->copy()->addDays($half - 1);

            if ($sequenceEnd->greaterThan($endsOn)) {
                $sequenceEnd = $endsOn->copy();
            }

            if ($sequenceStart->greaterThan($endsOn)) {
                break;
            }

            $examStart = $sequenceEnd->copy()->subDays(self::EXAM_DAYS - 1);

            if ($examStart->lessThan($sequenceStart)) {
                $examStart = $sequenceStart->copy();
            }

            $sequences[] = [
                'starts_on' => $sequenceStart->toDateString(),
                'ends_on' => $sequenceEnd->toDateString(),
                'exam_starts_on' => $examStart->toDateString(),
                'exam_ends_on' => $sequenceEnd->toDateString(),
            ];
        }

        return [
            'starts_on' => $startsOn->toDateString(),
            'ends_on' => $endsOn->toDateString(),
            'sequences' => $sequences,
        ];
    }

    private function firstMondayOfJanuary(int $year): Carbon
    {
        $day = Carbon::create($year, 1, 1)->startOfDay();

        return $day->isMonday() ? $day : $day->next(Carbon::MONDAY);
    }

    /**
     * Easter Sunday, by the Gregorian computus.
     */
    private function easterSunday(int $year): Carbon
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day)->startOfDay();
    }
}
