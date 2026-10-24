<april:card>
    <slot:title>Edit {{ $academicPeriod->display_name }}</slot:title>
    <slot:description>{{ __('Update the name, dates, or type before this period is closed.') }}</slot:description>
    <slot:content>
        <form action="{{ route('academic-periods.update', $academicPeriod) }}" method="POST" class="max-w-2xl space-y-4">
            <x-display-validation-errors/>
            <april:input-group id="name" name="name" label="{{ __('Period name') }}" placeholder="{{ __('Enter a period name') }}" value="{{ old('name', $academicPeriod->name) }}" />
            <div class="grid gap-4 md:grid-cols-2">
                <april:input-group id="starts-on" name="starts_on" type="date" label="{{ __('Starts on') }}" value="{{ $academicPeriod->starts_on?->toDateString() }}" />
                <april:input-group id="ends-on" name="ends_on" type="date" label="{{ __('Ends on') }}" value="{{ $academicPeriod->ends_on?->toDateString() }}" />
            </div>
            <div class="flex w-full flex-col gap-2">
                <april:label for="type">{{ __('Period type') }}</april:label>
                <select id="type" name="type" class="h-10 rounded-md border border-input bg-background px-3 text-sm" {{ field_error_bindings('type') }}>
                    @foreach (\App\Enums\AcademicPeriodType::cases() as $type)
                        <option value="{{ $type->value }}" {{ old('type', $academicPeriod->type->value) === $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                    @endforeach
                </select>
                <x-field-error name="type" />
            </div>
            @csrf
            @method('PUT')
            <april:button type="submit" class="w-full md:w-1/2">
                {{ __('Save changes') }}
            </april:button>
        </form>
    </slot:content>
</april:card>
