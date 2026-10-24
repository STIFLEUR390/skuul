@php
    $chosen = $academicLevel !== null || $section !== null || $cohort !== null;
    $groupName = $cohort?->name ?? $section?->name ?? $academicLevel?->name;
@endphp

<div class="space-y-6">
    @if ($error !== null)
        <april:alert variant="destructive">
            <slot:title>{{ __('No order was worked out') }}</slot:title>
            <slot:description>{{ $error }}</slot:description>
        </april:alert>
    @endif

    <april:card>
        <slot:title>{{ __('Choose a class or group') }}</slot:title>
        <slot:description>
            {{ __('Start with who you want to compare. We only offer a subject when every learner in that selection takes it.') }}
        </slot:description>
        <slot:content>
            <div class="space-y-6">
                <fieldset class="space-y-3">
                    <legend class="text-sm font-medium">{{ __('What do you want to compare?') }}</legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-md border border-input p-3 transition-colors hover:bg-accent/40 has-[:checked]:border-primary/50 has-[:checked]:bg-primary/5">
                            <input type="radio" name="selection_mode" value="class" wire:model.live="selectionMode" class="mt-1 size-4 shrink-0 accent-primary">
                            <span class="flex min-w-0 flex-col gap-1">
                                <span class="text-sm font-semibold">{{ school_term('class_level', 'Class') }}</span>
                                <span class="text-xs text-muted-foreground">{{ __('Compare learners in one class and its sections.') }}</span>
                            </span>
                        </label>
                        @if ($academicLevels->where('is_group', true)->isNotEmpty())
                            <label class="flex cursor-pointer items-start gap-3 rounded-md border border-input p-3 transition-colors hover:bg-accent/40 has-[:checked]:border-primary/50 has-[:checked]:bg-primary/5">
                                <input type="radio" name="selection_mode" value="group" wire:model.live="selectionMode" class="mt-1 size-4 shrink-0 accent-primary">
                                <span class="flex min-w-0 flex-col gap-1">
                                    <span class="text-sm font-semibold">{{ __('Whole group') }}</span>
                                    <span class="text-xs text-muted-foreground">{{ __('Compare learners across the group’s child classes.') }}</span>
                                </span>
                            </label>
                        @endif
                    </div>
                </fieldset>

                <div class="grid gap-4 md:grid-cols-2">
                    @if ($selectionMode === 'class')
                        <div class="flex flex-col gap-2" wire:key="class-selection">
                            <div class="flex min-h-10 items-start">
                                <april:label for="academic_level_id">{{ school_term('class_level', 'Class') }}</april:label>
                            </div>
                            <april:native-select id="academic_level_id" wire:model.live="academicLevelId" class="w-full min-w-0">
                                <option value="">Choose {{ with_indefinite_article(strtolower(school_term('class_level', 'class'))) }}</option>
                                <optgroup label="{{ school_terms('class_level', 'Classes') }}">
                                    @foreach ($academicLevels->where('is_group', false) as $option)
                                        <option value="{{ $option->id }}">
                                            {{ $option->parent?->name ? $option->parent->name.' → ' : '' }}{{ $option->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            </april:native-select>
                            <p class="text-xs text-muted-foreground">{{ __('Sections will appear after you choose a class.') }}</p>
                        </div>
                    @else
                        <div class="flex flex-col gap-2" wire:key="group-selection">
                            <div class="flex min-h-10 items-start">
                                <april:label for="group_academic_level_id">{{ __('Whole group') }}</april:label>
                            </div>
                            <april:native-select id="group_academic_level_id" wire:model.live="groupAcademicLevelId" class="w-full min-w-0">
                                <option value="">{{ __('Choose a group') }}</option>
                                @foreach ($academicLevels->where('is_group', true) as $option)
                                    <option value="{{ $option->id }}">
                                        {{ $option->parent?->name ? $option->parent->name.' → ' : '' }}{{ $option->name }}
                                    </option>
                                @endforeach
                            </april:native-select>
                            <p class="text-xs text-muted-foreground">{{ __('Includes every learner in the group’s child classes.') }}</p>
                        </div>
                    @endif
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <div class="flex min-h-10 items-start">
                            <april:label for="academic_cycle_section_id">{{ school_term('section', 'Section') }}</april:label>
                        </div>
                        <april:native-select id="academic_cycle_section_id" wire:model.live="academicCycleSectionId" class="w-full min-w-0" :disabled="$academicLevel === null">
                            <option value="">Every section in this {{ strtolower(school_term('class_level', 'class')) }}</option>
                            @foreach ($sections as $option)
                                <option value="{{ $option->id }}">
                                    {{ $option->academicLevel?->name }} · {{ $option->label ?? $option->name }}
                                </option>
                            @endforeach
                        </april:native-select>
                        <p class="text-xs text-muted-foreground">{{ __('Optional: narrow the comparison to one section.') }}</p>
                    </div>

                    <div class="flex flex-col gap-2">
                        <div class="flex min-h-10 items-start">
                            <april:label for="cohort_id">{{ __('Learner group') }}</april:label>
                        </div>
                        <april:native-select id="cohort_id" wire:model.live="cohortId" class="w-full min-w-0">
                            <option value="">{{ __('No learner group') }}</option>
                            @foreach ($cohorts as $option)
                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                            @endforeach
                        </april:native-select>
                        <p class="text-xs text-muted-foreground">{{ __('Optional: compare a named set of learners instead.') }}</p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <div class="flex min-h-10 items-start">
                            <april:label for="academic_period_id">{{ school_term('period', 'Period') }}</april:label>
                        </div>
                        <april:native-select id="academic_period_id" wire:model.live="academicPeriodId" class="w-full min-w-0">
                            <option value="">Every {{ school_term('period', 'period') }}</option>
                            @foreach ($periods as $option)
                                <option value="{{ $option->id }}">{{ $option->label ?? $option->name }}</option>
                            @endforeach
                        </april:native-select>
                    </div>

                    <div class="flex flex-col gap-2">
                        <div class="flex min-h-10 items-start">
                            <april:label for="subject_id">{{ __('Subject') }}</april:label>
                        </div>
                        <april:native-select id="subject_id" wire:model.live="subjectId" class="w-full min-w-0" :disabled="$subjects->isEmpty()">
                            <option value="">{{ $subjects->isEmpty() ? 'No subject shared by everyone' : 'Every subject' }}</option>
                            @foreach ($subjects as $option)
                                <option value="{{ $option->id }}">{{ $option->name }}</option>
                            @endforeach
                        </april:native-select>
                        <p class="text-xs text-muted-foreground">{{ __('Only subjects shared by every learner in the selection are listed.') }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p wire:loading class="text-sm text-muted-foreground" aria-live="polite">{{ __('Updating rankings…') }}</p>
                    @if ($chosen)
                        <april:button type="button" variant="outline" wire:click="clearFilters" wire:loading.attr="disabled">{{ __('Clear') }}</april:button>
                    @endif
                </div>
            </div>
        </slot:content>
    </april:card>

    <div wire:loading.class="opacity-60" class="transition-opacity">
        <april:card>
            <slot:title>{{ $chosen ? $groupName : 'The order' }}</slot:title>
            <slot:description>
                {{ __('Two equal averages share a position. A learner with no published result is not in the order at all.') }}
            </slot:description>
            <slot:content>
                @if (!$chosen)
                    <x-empty-state icon="lucide-list-ordered" title="{{ __('Choose a class or group first') }}"
                        description="Pick a class, {{ with_indefinite_article(strtolower(school_term('section', 'section'))) }}, or a group above, then the order will update." />
                @elseif ($rows->isEmpty())
                    <x-empty-state icon="lucide-search-x" title="{{ __('Nothing to put in order') }}"
                        description="Nobody in this group has a published result for what you chose." />
                @else
                    <april:data-table>
                        <slot:header>
                            <april:data-table-row>
                                <april:data-table-head>{{ __('Position') }}</april:data-table-head>
                                <april:data-table-head>{{ __('Learner') }}</april:data-table-head>
                                <april:data-table-head>{{ __('Average') }}</april:data-table-head>
                                <april:data-table-head class="text-right">{{ __('Subjects counted') }}</april:data-table-head>
                            </april:data-table-row>
                        </slot:header>
                        <slot:body>
                            @foreach ($rows as $row)
                                @php $learner = $learners->get($row['student_record_id']); @endphp
                                <april:data-table-row>
                                    <april:data-table-cell class="font-medium">{{ $row['position'] }}</april:data-table-cell>
                                    <april:data-table-cell>
                                        {{ $learner?->user?->name ?? 'Unnamed' }}
                                        <span class="block text-xs text-muted-foreground">{{ $learner?->admission_number }}</span>
                                    </april:data-table-cell>
                                    <april:data-table-cell>{{ \Illuminate\Support\Number::format($row['average'], 2) }}%</april:data-table-cell>
                                    <april:data-table-cell class="text-right text-muted-foreground">{{ $row['subjects'] }}</april:data-table-cell>
                                </april:data-table-row>
                            @endforeach
                        </slot:body>
                    </april:data-table>
                @endif
            </slot:content>
        </april:card>
    </div>
</div>
