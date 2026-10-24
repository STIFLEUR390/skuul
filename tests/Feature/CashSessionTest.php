<?php

namespace Tests\Feature;

use App\Actions\Finance\CloseCashSession;
use App\Actions\Finance\OpenCashSession;
use App\Actions\Finance\ReceivePayment;
use App\Exceptions\InvalidValueException;
use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\FeeInvoice;
use App\Models\FinancialPeriod;
use App\Models\StudentRecord;
use App\Services\Fee\FeeInvoiceService;
use App\Traits\FeatureTestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The cashier's till: what it took, and what should be in the drawer.
 */
class CashSessionTest extends TestCase
{
    use FeatureTestTrait;
    use RefreshDatabase;

    public function test_cash_payments_join_the_open_till_and_are_numbered(): void
    {
        $this->authorized_user([]);
        $enrollment = $this->enrollment();
        $this->invoiceFor($enrollment, [['amount' => 50_000]]);

        $session = app(OpenCashSession::class)->open(auth()->user(), 10_000);

        $first = app(ReceivePayment::class)->receive($enrollment, 20_000);
        $second = app(ReceivePayment::class)->receive($enrollment, 5_000);

        $this->assertSame($session->id, $first->cash_session_id);
        $this->assertSame($session->id, $second->cash_session_id);
        $this->assertSame(1, $first->receipt_number);
        $this->assertSame(2, $second->receipt_number);
        $this->assertSame(35_000.0, $session->fresh()->expected());
    }

    public function test_the_same_cashier_cannot_open_two_tills(): void
    {
        $this->authorized_user([]);
        app(OpenCashSession::class)->open(auth()->user());

        $this->expectException(InvalidValueException::class);

        app(OpenCashSession::class)->open(auth()->user());
    }

    public function test_counting_the_till_records_the_gap_and_closes_it(): void
    {
        $this->authorized_user([]);
        $enrollment = $this->enrollment();
        $this->invoiceFor($enrollment, [['amount' => 50_000]]);

        $session = app(OpenCashSession::class)->open(auth()->user(), 5_000);
        app(ReceivePayment::class)->receive($enrollment, 20_000);

        $closed = app(CloseCashSession::class)->close($session, 25_500, auth()->user());

        $this->assertFalse($closed->isOpen());
        $this->assertSame(25_500.0, $closed->counted_amount);
        $this->assertSame(500.0, $closed->variance());
    }

    public function test_a_closed_till_cannot_be_counted_twice(): void
    {
        $this->authorized_user([]);

        $session = app(OpenCashSession::class)->open(auth()->user());
        app(CloseCashSession::class)->close($session, 0.0, auth()->user());

        $this->expectException(InvalidValueException::class);

        app(CloseCashSession::class)->close($session->fresh(), 0.0, auth()->user());
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

        $enrollment = StudentRecord::factory()->create(['school_id' => $school->id]);
        $this->memberOf($school, $enrollment->user);

        return $enrollment->fresh();
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

        return FeeInvoice::where('user_id', $enrollment->user_id)->latest('id')->firstOrFail();
    }
}
