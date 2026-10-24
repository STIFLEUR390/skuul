@extends('layouts.app', ['breadcrumbs' => [
    ['href' => route('dashboard'), 'text' => 'Dashboard'],
    ['href' => route('academic-levels.index'), 'text' => school_terms('class_level', 'Classes')],
    ['href' => route('academic-levels.create'), 'text' => 'Add', 'active'],
]])

@section('title', __('Add '.strtolower(school_term('class_level', 'class'))))
@section('page_heading', __('Add '.strtolower(school_term('class_level', 'class'))))

@section('content')
    <april:card class="mx-auto max-w-3xl">
        <slot:title>Add {{ with_indefinite_article(strtolower(school_term('class_level', 'class'))) }} this school teaches</slot:title>
        <slot:description>
            Add one reusable level or level group. You will add this year’s {{ strtolower(school_term('section', 'section')) }} later.
        </slot:description>
        <slot:content>
            <div class="mb-6 flex items-start gap-3 rounded-lg border border-blue-500/30 bg-blue-500/10 p-4 text-sm text-blue-950 dark:text-blue-100">
                <x-lucide-git-branch class="mt-0.5 size-5 shrink-0 text-blue-700 dark:text-blue-300" />
                <div class="space-y-2">
                    <p class="font-semibold">{{ __('How to structure levels') }}</p>
                    <p>{{ __('Use your school’s own names. The names below are only an example:') }}</p>
                    <ol class="list-decimal space-y-1 pl-4">
                        <li>{{ __('Example group: create') }} <strong>{{ __('Kindergarten') }}</strong> {{ __('and leave') }} <strong>{{ __('Level group') }}</strong> {{ __('blank.') }}</li>
                        <li>{{ __('Example levels: create') }} <strong>KG 1</strong> {{ __('and') }} <strong>KG 2</strong>{{ __(', choosing') }} <strong>{{ __('Kindergarten') }}</strong> {{ __('as their level group.') }}</li>
                        <li>{{ __('Example section: in this year’s setup, add') }} <strong>{{ __('Blue') }}</strong> {{ __('under') }} <strong>KG 1</strong>.</li>
                    </ol>
                    <p>{{ __('Without groups, create a standalone level such as') }} <strong>{{ __('Primary 4') }}</strong> {{ __('and leave') }} <strong>{{ __('Level group') }}</strong> {{ __('blank. This is also an example.') }}</p>
                </div>
            </div>
            @if ($preselectedParent)
                <div class="mb-5 rounded-md border bg-muted/30 p-3 text-sm">
                    {{ __('Adding a level under') }} <span class="font-semibold">{{ $preselectedParent->name }}</span>{{ __('. You can change the level group below.') }}
                </div>
            @endif
            <livewire:academic-level-form
                :setup="request()->boolean('setup')"
                :school-setup="request()->boolean('school_setup')"
                :academic-year-id="request()->integer('academic_year_id') ?: null"
                :preselected-parent-id="$preselectedParent?->id" />
        </slot:content>
    </april:card>
@endsection
