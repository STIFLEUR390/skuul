<div class="grid min-w-0 w-full gap-4 sm:grid-cols-2 {{ $showNationality ? 'md:grid-cols-3' : '' }}">
    @if ($showNationality)
        <april:input-group id="nationality" name="nationality" label="{{ __('Nationality') }}" placeholder="{{ __("Person's nationality") }}" value="{{ old('nationality', $nationality) }}" />
    @endif

    <div class="flex w-full flex-col gap-2">
        <april:label for="country">{{ __('Country') }}</april:label>
        <april:combobox name="country" :value="$country" placeholder="{{ __('Search countries') }}" wire:model.live="country">
            <slot:trigger>
                <button id="country" type="button" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-left text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50">
                    <span class="truncate" x-text="selectedLabel() || 'Not specified'"></span>
                    <span aria-hidden="true" class="ml-2 text-muted-foreground">⌄</span>
                </button>
            </slot:trigger>
            <slot:empty>{{ __('No matching country.') }}</slot:empty>
            <april:combobox-option value="">{{ __('Not specified') }}</april:combobox-option>
            @foreach ($countries as $item)
                <april:combobox-option value="{{ $item }}">{{ $item }}</april:combobox-option>
            @endforeach
        </april:combobox>
        <x-field-error name="country" />
    </div>
    <div class="flex w-full flex-col gap-2">
        <april:label for="state">{{ __('State / Province') }}</april:label>
        <april:combobox name="state" :value="$state" placeholder="{{ __('Search states or provinces') }}" wire:init="loadInitialStates" wire:model.live="state">
            <slot:trigger>
                <button id="state" type="button" class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-left text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50">
                    <span class="truncate" x-text="selectedLabel() || 'Not specified'"></span>
                    <span aria-hidden="true" class="ml-2 text-muted-foreground">⌄</span>
                </button>
            </slot:trigger>
            <slot:empty>{{ __('No matching state or province.') }}</slot:empty>
            <april:combobox-option value="">{{ __('Not specified') }}</april:combobox-option>
            @if (isset($states))
                @foreach ($states as $item)
                    <april:combobox-option value="{{ $item['name'] }}">{{ $item['name'] }}</april:combobox-option>
                @endforeach
            @endif
        </april:combobox>
        <x-field-error name="state" />
    </div>
</div>
