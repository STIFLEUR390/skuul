{{-- The rule a plan or stage uses to count its items. The caller names the component properties and passes their values. --}}
@php
    $countsCredits = $operator === 'at_least_credits' || ($creditsToggleProperty !== null && $isCountingCredits);
@endphp
<details wire:ignore.self class="border-y py-1" @if ($operator !== 'all' || $countsCredits) open @endif>
    <summary class="min-h-11 cursor-pointer select-none py-3 text-sm font-medium">Advanced rules (optional)</summary>
    <div class="grid gap-3 pb-3 sm:grid-cols-2">
        <div>
            <label for="{{ $operatorProperty }}" class="text-sm text-muted-foreground">{{ __('How the items count') }}</label>
            <select id="{{ $operatorProperty }}" wire:model.live="{{ $operatorProperty }}" class="{{ $controlClasses }}" {{ field_error_bindings($operatorProperty) }}>
                <option value="all">{{ __('All of them') }}</option>
                <option value="any">{{ __('Any one of them') }}</option>
                <option value="at_least">{{ __('A number of them') }}</option>
                <option value="at_least_credits">{{ __('A number of credits') }}</option>
            </select>
            <x-field-error :name="$operatorProperty" class="mt-1" />
        </div>
        @if ($operator === 'at_least')
            <div>
                <label for="{{ $countProperty }}" class="text-sm text-muted-foreground">{{ __('How many are needed') }}</label>
                <input id="{{ $countProperty }}" type="number" inputmode="numeric" min="1" max="1000" wire:model="{{ $countProperty }}" placeholder="4" class="{{ $controlClasses }}" {{ field_error_bindings($countProperty) }}>
                <x-field-error :name="$countProperty" class="mt-1" />
            </div>
        @endif
        @if ($creditsToggleProperty !== null && $operator !== 'at_least_credits')
            <label class="flex min-h-11 select-none items-center gap-3 text-sm sm:col-span-2">
                <input type="checkbox" wire:model.live="{{ $creditsToggleProperty }}" class="size-4 rounded border-input">
                {{ __('Count credits as well') }}
            </label>
        @endif
        @if ($countsCredits)
            <div>
                <label for="{{ $creditsProperty }}" class="text-sm text-muted-foreground">{{ __('Credits needed') }}</label>
                <input id="{{ $creditsProperty }}" type="number" inputmode="numeric" min="1" max="1000" wire:model="{{ $creditsProperty }}" placeholder="24" class="{{ $controlClasses }}" {{ field_error_bindings($creditsProperty) }}>
                <x-field-error :name="$creditsProperty" class="mt-1" />
            </div>
        @endif
    </div>
</details>
