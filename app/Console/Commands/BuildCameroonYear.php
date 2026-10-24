<?php

namespace App\Console\Commands;

use App\Enums\AcademicPeriodStatus;
use App\Enums\AcademicPeriodType;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Services\School\CameroonCalendar;
use Illuminate\Console\Command;

/**
 * Open a Cameroonian school year with its trimesters, sequences and breaks.
 *
 * The command is safe to run again: each part of the calendar is matched by
 * its kind and place, so dates are refreshed instead of duplicated.
 */
class BuildCameroonYear extends Command
{
    /**
     * @var string
     */
    protected $signature = 'academic:year-cameroon
        {--school= : School id, defaults to the only school}
        {--year= : Year the session opens in, defaults to the current one}
        {--start= : Date of the rentrée, defaults to the first Monday after 1 September}';

    /**
     * @var string
     */
    protected $description = 'Create a Cameroonian school year with its trimesters, sequences, examinations and breaks';

    public function handle(CameroonCalendar $calendar): int
    {
        $school = $this->school();

        if ($school === null) {
            $this->error('Name the school with --school when more than one exists.');

            return self::FAILURE;
        }

        $start = (string) ($this->option('year') ?? now()->year);
        $startYear = (int) $start;
        $rentree = $this->option('start');

        if ($rentree !== null && strtotime((string) $rentree) === false) {
            $this->error('The --start date must read as a date, such as 2025-09-08.');

            return self::FAILURE;
        }

        $plan = $calendar->plan($startYear, $rentree === null ? null : (string) $rentree);

        $year = AcademicYear::query()->updateOrCreate(
            ['school_id' => $school->id, 'start_year' => $startYear],
            [
                'stop_year' => $startYear + 1,
                'starts_on' => $plan['starts_on'],
                'ends_on' => $plan['ends_on'],
            ],
        );

        $created = 0;

        foreach ($plan['trimesters'] as $index => $trimester) {
            $number = $index + 1;

            $period = AcademicPeriod::query()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'parent_id' => null,
                    'type' => AcademicPeriodType::Trimester->value,
                    'position' => $number,
                ],
                [
                    'name' => __('Trimester :number', ['number' => $number]),
                    'starts_on' => $trimester['starts_on'],
                    'ends_on' => $trimester['ends_on'],
                    'status' => AcademicPeriodStatus::Open->value,
                ],
            );

            $created++;

            foreach ($trimester['sequences'] as $sequenceIndex => $sequence) {
                $sequenceNumber = $sequenceIndex + 1;

                $sequencePeriod = AcademicPeriod::query()->updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'academic_year_id' => $year->id,
                        'parent_id' => $period->id,
                        'type' => AcademicPeriodType::Sequence->value,
                        'position' => $sequenceNumber,
                    ],
                    [
                        'name' => __('Sequence :number', ['number' => $sequenceNumber]),
                        'starts_on' => $sequence['starts_on'],
                        'ends_on' => $sequence['ends_on'],
                        'status' => AcademicPeriodStatus::Open->value,
                    ],
                );

                AcademicPeriod::query()->updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'academic_year_id' => $year->id,
                        'parent_id' => $sequencePeriod->id,
                        'type' => AcademicPeriodType::ExamWindow->value,
                        'position' => 1,
                    ],
                    [
                        'name' => __('Sequence :number tests', ['number' => $sequenceNumber]),
                        'starts_on' => $sequence['exam_starts_on'],
                        'ends_on' => $sequence['exam_ends_on'],
                        'status' => AcademicPeriodStatus::Open->value,
                    ],
                );

                $created += 2;
            }
        }

        foreach ($plan['breaks'] as $break) {
            AcademicPeriod::query()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'parent_id' => null,
                    'type' => AcademicPeriodType::Holiday->value,
                    'name' => __($break['name'] === 'christmas' ? 'Christmas break' : 'Easter break'),
                ],
                [
                    'starts_on' => $break['starts_on'],
                    'ends_on' => $break['ends_on'],
                    'status' => AcademicPeriodStatus::Open->value,
                ],
            );

            $created++;
        }

        $this->info(__(':school: :year in place, :count periods created.', [
            'school' => $school->name,
            'year' => $year->name,
            'count' => $created,
        ]));

        return self::SUCCESS;
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
