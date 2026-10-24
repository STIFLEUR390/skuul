<?php

namespace App\Actions\Finance;

use App\Exceptions\InvalidValueException;
use App\Models\CashSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Open the till for a cashier's day.
 *
 * One counter session per cashier: a second opening while the first is still
 * running would split the cash of the same drawer across two counts.
 */
class OpenCashSession
{
    public function open(User $actor, float $openingFloat = 0.0, ?string $note = null, ?int $schoolId = null): CashSession
    {
        if ($openingFloat < 0) {
            throw new InvalidValueException('The float in the till cannot be negative.');
        }

        $schoolId ??= current_school_id();

        return DB::transaction(function () use ($actor, $openingFloat, $note, $schoolId): CashSession {
            $alreadyOpen = CashSession::query()
                ->where('school_id', $schoolId)
                ->where('opened_by', $actor->getKey())
                ->whereNull('closed_at')
                ->lockForUpdate()
                ->exists();

            if ($alreadyOpen) {
                throw new InvalidValueException('Your till is already open. Count it before opening a new session.');
            }

            return CashSession::create([
                'school_id' => $schoolId,
                'opened_by' => $actor->getKey(),
                'opened_at' => now(),
                'opening_float' => round($openingFloat, 2),
                'note' => $note,
            ]);
        });
    }
}
