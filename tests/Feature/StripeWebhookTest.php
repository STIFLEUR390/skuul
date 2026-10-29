<?php

namespace Tests\Feature;

use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\FeeInvoice;
use App\Models\FinancialPeriod;
use App\Models\StudentPayment;
use App\Models\StudentRecord;
use App\Services\Fee\FeeInvoiceService;
use App\Services\Finance\StudentLedger;
use App\Traits\FeatureTestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Money that reaches the school through Stripe, and the message that says so.
 *
 * Stripe owns the message, so the school books it only once its signature is
 * proven, only once however often Stripe repeats it, and against the campus
 * whose learner paid, without a session to say which one that is.
 */
class StripeWebhookTest extends TestCase
{
    use FeatureTestTrait;
    use RefreshDatabase;

    private const SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.webhook_secret' => self::SECRET,
            'services.stripe.secret' => 'sk_test_placeholder',
        ]);
    }

    public function test_a_paid_checkout_books_the_money_stripe_took(): void
    {
        $this->workingSchool();
        $enrollment = $this->enrollment();
        $invoice = $this->invoiceFor($enrollment, [['amount' => 500]]);

        $response = $this->webhook($this->paidSession($enrollment, 500, 'cs_test_paid'));

        $response->assertCreated();

        $payment = StudentPayment::withoutGlobalScopes()->sole();

        $this->assertSame('stripe', $payment->method);
        $this->assertSame('cs_test_paid', $payment->reference);
        $this->assertSame($enrollment->school_id, $payment->school_id);
        $this->assertSame(money_minor(500), $payment->amount->getMinorAmount()->toInt());
        $this->assertSame(money_minor(500), $invoice->fresh()->paid->getMinorAmount()->toInt());
        $this->assertSame(0.0, app(StudentLedger::class)->balance($enrollment->fresh()));
    }

    public function test_a_message_stripe_repeats_books_once(): void
    {
        $this->workingSchool();
        $enrollment = $this->enrollment();
        $this->invoiceFor($enrollment, [['amount' => 500]]);

        $this->webhook($this->paidSession($enrollment, 500, 'cs_test_twice'))->assertCreated();
        $this->webhook($this->paidSession($enrollment, 500, 'cs_test_twice'))->assertOk();

        $this->assertSame(1, StudentPayment::withoutGlobalScopes()->count());
    }

    public function test_a_message_that_does_not_come_from_stripe_books_nothing(): void
    {
        $this->workingSchool();
        $enrollment = $this->enrollment();
        $this->invoiceFor($enrollment, [['amount' => 500]]);

        $this->webhook($this->paidSession($enrollment, 500, 'cs_test_forged'), 'not-the-secret')
            ->assertStatus(400);

        $this->assertSame(0, StudentPayment::withoutGlobalScopes()->count());
    }

    public function test_a_checkout_that_is_not_paid_yet_books_nothing(): void
    {
        $this->workingSchool();
        $enrollment = $this->enrollment();
        $this->invoiceFor($enrollment, [['amount' => 500]]);

        $session = $this->paidSession($enrollment, 500, 'cs_test_open');
        $session['data']['object']['payment_status'] = 'unpaid';

        $this->webhook($session)->assertStatus(202);

        $this->assertSame(0, StudentPayment::withoutGlobalScopes()->count());
    }

    public function test_a_message_naming_an_unknown_learner_books_nothing(): void
    {
        $this->workingSchool();

        $session = $this->paidSession($this->enrollment(), 500, 'cs_test_stranger');
        $session['data']['object']['metadata']['student_record_id'] = 999_999;

        $this->webhook($session)->assertOk();

        $this->assertSame(0, StudentPayment::withoutGlobalScopes()->count());
    }

    /**
     * Post a Stripe message, signed the way Stripe signs it.
     *
     * @param  array<string, mixed>  $event
     */
    private function webhook(array $event, ?string $secret = null): TestResponse
    {
        $payload = (string) json_encode($event);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret ?? self::SECRET);

        return $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ], $payload);
    }

    /**
     * A checkout session Stripe has taken money for.
     *
     * @return array<string, mixed>
     */
    private function paidSession(StudentRecord $enrollment, int $amount, string $reference): array
    {
        return [
            'id' => 'evt_'.fake()->uuid(),
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => $reference,
                    'object' => 'checkout.session',
                    'payment_status' => 'paid',
                    'amount_total' => money_minor($amount),
                    'metadata' => [
                        'student_record_id' => $enrollment->id,
                        'reference' => $reference,
                    ],
                ],
            ],
        ];
    }

    private function enrollment(): StudentRecord
    {
        $school = $this->workingSchool();

        FinancialPeriod::query()->firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Term one'],
            [
                'starts_on' => now()->startOfYear()->toDateString(),
                'ends_on' => now()->endOfYear()->toDateString(),
            ],
        );

        return StudentRecord::factory()->create(['school_id' => $school->id])->fresh();
    }

    /**
     * @param  array<int, array{amount: int}>  $lines
     */
    private function invoiceFor(StudentRecord $enrollment, array $lines): FeeInvoice
    {
        $category = FeeCategory::factory()->create(['school_id' => $this->workingSchool()->id]);

        $records = [];

        foreach ($lines as $index => $line) {
            $fee = Fee::factory()->create([
                'fee_category_id' => $category->id,
                'name' => "Fee $index ".fake()->unique()->word(),
            ]);

            $records[] = [
                'fee_id' => $fee->id,
                'amount' => $line['amount'],
                'waiver' => 0,
                'fine' => 0,
            ];
        }

        app(FeeInvoiceService::class)->storeFeeInvoice([
            'issue_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'student_records' => [$enrollment->id],
            'records' => $records,
        ]);

        return FeeInvoice::withoutGlobalScopes()
            ->where('user_id', $enrollment->user_id)
            ->latest('id')
            ->firstOrFail();
    }
}
