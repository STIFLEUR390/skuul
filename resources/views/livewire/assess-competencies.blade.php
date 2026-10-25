<div class="mx-auto flex w-full max-w-4xl flex-col gap-8">
    @php
        $controlClasses = 'h-11 w-full rounded-md border border-input bg-background px-3 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-50';
    @endphp

    @if (session('success'))
        <april:alert>
            <slot:title>{{ session('success') }}</slot:title>
        </april:alert>
    @endif

    @if ($assessmentLevel?->assessment_mode !== \App\Enums\AssessmentMode::Competency)
        <april:alert variant="destructive">
            <slot:title>{{ __('This level is not assessed by competency') }}</slot:title>
            <slot:description>{{ __('Marks out of 20 are recorded in the gradebook instead. Set the level to competency based to use this screen.') }}</slot:description>
        </april:alert>
    @endif

    <form wire:submit="save" class="flex flex-col gap-6" aria-label="{{ __('Assess competencies') }}">
        <div class="flex flex-col gap-2">
            <label for="academic-period" class="text-sm font-medium">{{ school_term('period', 'Academic period') }}</label>
            <select id="academic-period" wire:model.live="academicPeriodId" class="{{ $controlClasses }}" {{ field_error_bindings('academicPeriodId') }}>
                <option value="">{{ __('Choose') }}</option>
                @foreach ($periods as $period)
                    <option value="{{ $period->id }}">{{ $period->label ?? $period->name }}</option>
                @endforeach
            </select>
            <x-field-error name="academicPeriodId" />
        </div>

        @forelse ($domains as $domain)
            <april:card>
                <slot:title>{{ $domain->name }}</slot:title>
                <slot:description>{{ $domain->name_en }}</slot:description>
                <slot:content>
                    <div class="space-y-4">
                        @foreach ($domain->competencies as $competency)
                            <div class="rounded-lg border p-4">
                                <p class="font-medium">{{ $competency->name }}</p>
                                @if (filled($competency->name_en))
                                    <p class="text-sm text-muted-foreground">{{ $competency->name_en }}</p>
                                @endif
                                <div class="mt-3 flex flex-wrap gap-4">
                                    @foreach ($levels as $choice)
                                        <label class="flex items-center gap-2 text-sm">
                                            <input type="radio" wire:model="levels.{{ $competency->id }}" value="{{ $choice->value }}">
                                            {{ __($choice->label()) }}
                                        </label>
                                    @endforeach
                                    <button type="button" wire:click="$set('levels.{{ $competency->id }}', '')" class="text-sm underline underline-offset-4">
                                        {{ __('Not assessed') }}
                                    </button>
                                </div>
                                <div class="mt-3 flex flex-col gap-2">
                                    <label for="comment-{{ $competency->id }}" class="text-sm font-medium">{{ __('Teacher comment') }}</label>
                                    <textarea id="comment-{{ $competency->id }}" wire:model="comments.{{ $competency->id }}" rows="2" maxlength="500" class="{{ $controlClasses }} h-auto py-2"></textarea>
                                    <x-field-error name="comments.{{ $competency->id }}" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </slot:content>
            </april:card>
        @empty
            <x-empty-state icon="lucide-list-checks" title="{{ __('No competency for this level') }}"
                description="Add the domains and competencies this level is assessed on, then come back to this screen." />
        @endforelse

        <div class="flex justify-end">
            <april:button type="submit">{{ __('Save the competency levels') }}</april:button>
        </div>
    </form>
</div>
