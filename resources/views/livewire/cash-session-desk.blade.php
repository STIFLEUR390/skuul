<div class="flex flex-col gap-6">
    @php
        $controlClasses = 'h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring';
    @endphp

    @if ($session === null)
        <form wire:submit="open" class="flex max-w-xl flex-col gap-4" aria-label="{{ __('Open the till') }}">
            <div>
                <label for="cash-opening-float" class="mb-1.5 block text-sm font-medium">{{ __('Float in the till') }}</label>
                <input id="cash-opening-float" type="number" step="0.01" min="0" inputmode="decimal" autocomplete="off"
                    wire:model="openingFloat" class="{{ $controlClasses }}" {{ field_error_bindings('openingFloat') }}>
                <x-field-error name="openingFloat" class="mt-1" />
            </div>

            <div>
                <label for="cash-opening-note" class="mb-1.5 block text-sm font-medium">{{ __('Note') }}</label>
                <input id="cash-opening-note" type="text" wire:model="note" placeholder="{{ __('Optional') }}" class="{{ $controlClasses }}">
            </div>

            <div>
                <button type="submit" class="inline-flex h-11 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground">
                    {{ __('Open the till') }}
                </button>
            </div>
        </form>
    @else
        <dl class="grid grid-cols-2 gap-x-6 gap-y-4 border-y py-4 sm:grid-cols-4">
            <div>
                <dt class="text-sm text-muted-foreground">{{ __('Opened') }}</dt>
                <dd class="font-medium">{{ $session->opened_at->translatedFormat('j M Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ __('Float') }}</dt>
                <dd class="font-medium tabular-nums">{{ money_text($session->opening_float) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ __('Cash received') }}</dt>
                <dd class="font-medium tabular-nums">{{ money_text($expected - $session->opening_float) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted-foreground">{{ __('Expected in the till') }}</dt>
                <dd class="text-2xl font-semibold tabular-nums tracking-tight" id="cash-expected">{{ money_text($expected) }}</dd>
            </div>
        </dl>

        @if ($payments->isEmpty())
            <p class="text-sm text-muted-foreground">{{ __('No cash taken yet.') }}</p>
        @else
            <ul class="divide-y border-y" aria-label="{{ __('Cash taken') }}">
                @foreach ($payments as $payment)
                    <li class="flex items-center justify-between gap-4 py-3">
                        <div class="min-w-0 text-sm">
                            <p class="font-medium">{{ $payment->studentRecord?->user?->name ?? '—' }}</p>
                            <p class="truncate text-muted-foreground">
                                {{ __('Receipt') }} #{{ $payment->receipt_number ?? '—' }}
                                · {{ $payment->created_at?->translatedFormat('H:i') }}
                            </p>
                        </div>
                        <span class="shrink-0 text-sm font-medium tabular-nums">{{ $payment->amount->formatToLocale(app()->getLocale()) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <form wire:submit="close" class="flex max-w-xl flex-col gap-4" aria-label="{{ __('Count and close the till') }}">
            <div>
                <label for="cash-counted-amount" class="mb-1.5 block text-sm font-medium">{{ __('Counted in the till') }}</label>
                <input id="cash-counted-amount" type="number" step="0.01" min="0" inputmode="decimal" autocomplete="off"
                    wire:model="countedAmount" placeholder="0.00" class="{{ $controlClasses }} text-base font-medium" {{ field_error_bindings('countedAmount') }}>
                <x-field-error name="countedAmount" class="mt-1" />
            </div>

            <div>
                <label for="cash-closing-note" class="mb-1.5 block text-sm font-medium">{{ __('Note') }}</label>
                <input id="cash-closing-note" type="text" wire:model="note" placeholder="{{ __('Say what explains a gap') }}" class="{{ $controlClasses }}">
            </div>

            <div>
                <button type="submit" class="inline-flex h-11 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground">
                    {{ __('Count and close') }}
                </button>
            </div>
        </form>
    @endif
</div>
