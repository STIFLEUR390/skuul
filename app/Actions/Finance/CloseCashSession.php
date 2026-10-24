<?php

namespace App\Actions\Finance;

use App\Exceptions\InvalidValueException;
use App\Models\CashSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Count the till and close the cashier's day.
 *
 * The counted amount is kept even when it does not match what the counter
 * recorded: the gap is what the office has to explain, not something to hide.
 */
class CloseCashSession
{
    public function close(CashSession $session, float $countedAmount, ?User $actor = null, ?string $note = null): CashSession
    {
        if ($countedAmount < 0) {
            throw new InvalidValueException('The counted amount cannot be negative.');
        }

        return DB::transaction(function () use ($session, $countedAmount, $actor, $note): CashSession {
            // The lock makes a second close, sent from another tab at the same
            // moment, wait here and then see the first one.
            $locked = CashSession::query()->whereKey($session->getKey())->lockForUpdate()->firstOrFail();

            if (!$locked->isOpen()) {
                throw new InvalidValueException('This till was already counted and closed.');
            }

            $locked->forceFill([
                'closed_by' => $actor?->getKey(),
                'closed_at' => now(),
                'counted_amount' => round($countedAmount, 2),
                'note' => $note ?? $locked->note,
            ])->save();

            return $locked;
        });
    }
}
