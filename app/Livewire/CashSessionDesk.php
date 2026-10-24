<?php

namespace App\Livewire;

use App\Actions\Finance\CloseCashSession;
use App\Actions\Finance\OpenCashSession;
use App\Exceptions\InvalidValueException;
use App\Models\CashSession;
use App\Models\StudentPayment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * The cashier's till: open the day, then count the drawer and close it.
 */
class CashSessionDesk extends Component
{
    public string $openingFloat = '';

    public string $countedAmount = '';

    public string $note = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('read cash session') === true, 403);

        $this->openingFloat = '0';
    }

    public function open(OpenCashSession $openSession): void
    {
        abort_unless(auth()->user()?->can('manage cash session') === true, 403);

        $this->validate([
            'openingFloat' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100000000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [], ['openingFloat' => 'float']);

        try {
            $openSession->open(auth()->user(), (float) $this->openingFloat, $this->note === '' ? null : $this->note);
        } catch (InvalidValueException $exception) {
            $this->addError('openingFloat', $exception->getMessage());

            return;
        }

        $this->reset('openingFloat', 'note');
        $this->openingFloat = '0';
    }

    public function close(CloseCashSession $closeSession): void
    {
        abort_unless(auth()->user()?->can('manage cash session') === true, 403);

        $session = $this->openSession();

        if ($session === null) {
            return;
        }

        $this->validate([
            'countedAmount' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100000000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [], ['countedAmount' => 'counted amount']);

        try {
            $closeSession->close($session, (float) $this->countedAmount, auth()->user(), $this->note === '' ? null : $this->note);
        } catch (InvalidValueException $exception) {
            $this->addError('countedAmount', $exception->getMessage());

            return;
        }

        $this->reset('countedAmount', 'note');
    }

    /**
     * The till this cashier has not counted yet.
     */
    public function openSession(): ?CashSession
    {
        return CashSession::query()
            ->where('opened_by', auth()->id())
            ->open()
            ->latest('opened_at')
            ->first();
    }

    /**
     * The cash the counter took since the till was opened.
     *
     * @return Collection<int, StudentPayment>
     */
    public function payments(): Collection
    {
        $session = $this->openSession();

        return $session === null
            ? collect()
            : $session->payments()->with('studentRecord.user')->orderBy('id')->get();
    }

    public function render(): View
    {
        $session = $this->openSession();

        return view('livewire.cash-session-desk', [
            'session' => $session,
            'payments' => $this->payments(),
            'expected' => $session?->expected(),
        ]);
    }
}
