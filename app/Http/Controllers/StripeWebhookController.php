<?php

namespace App\Http\Controllers;

use App\Actions\Finance\ReceivePayment;
use App\Exceptions\InvalidValueException;
use App\Models\Scopes\SchoolScope;
use App\Models\StudentRecord;
use App\Services\Finance\Channels\StripeChannel;
use App\Services\School\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Book the card payments Stripe says it took.
 *
 * Stripe owns this message, so nothing in it is believed before the signature
 * proves where it came from, and a message that arrives twice books once.
 */
class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        StripeChannel $stripe,
        ReceivePayment $receive,
        SchoolContext $schools,
    ): JsonResponse {
        $outcome = $stripe->readCallback($request);

        // A message with no reference never proved it came from Stripe.
        if ($outcome->reference === '') {
            return response()->json(['status' => 'refused'], 400);
        }

        if (!$outcome->settled) {
            return response()->json(['status' => 'nothing to book'], 202);
        }

        $enrollment = $this->learner($outcome->payload['metadata'] ?? []);

        if ($enrollment === null) {
            Log::warning('A Stripe message named a learner this school does not know.', [
                'reference' => $outcome->reference,
            ]);

            return response()->json(['status' => 'unknown learner'], 200);
        }

        // There is no session behind a webhook, so the campus the money
        // belongs to is named here rather than read from the browser.
        $schools->set($enrollment->school, remember: false);

        try {
            $receive->receive(
                enrollment: $enrollment,
                amount: $outcome->amount,
                method: $stripe->key(),
                reference: $outcome->reference,
                note: 'Card payment taken by Stripe.',
                schoolId: $enrollment->school_id,
            );
        } catch (InvalidValueException $exception) {
            // Stripe repeats a message until it is acknowledged, so money it
            // already told us about is not a failure: it is the same money.
            Log::info('A Stripe message was acknowledged without booking.', [
                'reference' => $outcome->reference,
                'reason' => $exception->getMessage(),
            ]);

            return response()->json(['status' => 'already booked'], 200);
        }

        return response()->json(['status' => 'booked'], 201);
    }

    /**
     * Get the learner the payment names, whichever campus holds their file.
     *
     * @param  array<string, mixed>  $metadata
     */
    private function learner(array $metadata): ?StudentRecord
    {
        $id = (int) ($metadata['student_record_id'] ?? 0);

        return $id === 0
            ? null
            : StudentRecord::withoutGlobalScope(SchoolScope::class)->find($id);
    }
}
