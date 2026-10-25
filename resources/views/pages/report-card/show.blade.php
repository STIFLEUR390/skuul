@extends('layouts.app', ['breadcrumbs' => [
    ['href' => route('dashboard'), 'text' => 'Dashboard'],
    ['href' => route('report-cards.index'), 'text' => 'Report cards'],
    ['text' => 'Issued card', 'active'],
]])

@section('title', 'Issued report card')
@section('page_heading', 'Issued report card')

@section('page_actions')
    <april:button-link href="{{ route('report-cards.index') }}" variant="outline">
        <x-lucide-arrow-left class="mr-2 size-4" />
        {{ __('Back to report cards') }}
    </april:button-link>
@endsection

@php
    $results = $reportCardSnapshot->payload['results'] ?? [];
    $summary = $reportCardSnapshot->payload['summary'] ?? [];
    $domains = $reportCardSnapshot->payload['competencies'] ?? [];
    $competencyMode = ($reportCardSnapshot->payload['assessment_mode'] ?? 'marks') === 'competency';
    $latestRevision = $revisions->max('revision');
    $isSuperseded = $reportCardSnapshot->revision < $latestRevision;
@endphp

@section('content')
    <div class="space-y-6">
        @if ($isSuperseded)
            <april:alert variant="destructive">
                <slot:title>{{ __('A later version exists') }}</slot:title>
                <slot:description>
                    This is revision {{ $reportCardSnapshot->revision }}. The school has since issued revision {{ $latestRevision }}.
                    This page stays exactly as it was issued.
                </slot:description>
            </april:alert>
        @endif

        <april:card>
            <slot:title>{{ $reportCardSnapshot->studentRecord->user?->name ?? $reportCardSnapshot->studentRecord->admission_number }}</slot:title>
            <slot:description>{{ $reportCardSnapshot->studentRecord->admission_number }}</slot:description>
            <slot:content>
                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-lg border p-4">
                        <dt class="text-sm text-muted-foreground">{{ $competencyMode ? __('Competencies assessed') : __('Overall') }}</dt>
                        <dd class="text-2xl font-semibold">
                            @if ($competencyMode)
                                {{ $summary['competencies_assessed'] ?? 0 }}
                            @else
                                {{ $summary['average_out_of_20'] === null ? '—' : \Illuminate\Support\Number::format($summary['average_out_of_20'], 2).'/20' }}
                            @endif
                        </dd>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ $competencyMode ? __('This level is assessed by competency, so no mark out of 20 is given.') : __('Weighted by coefficient across the subjects below') }}
                        </p>
                    </div>
                    <div class="rounded-lg border p-4">
                        <dt class="text-sm text-muted-foreground">{{ school_term('period', 'Period') }}</dt>
                        <dd class="text-lg font-semibold">{{ $reportCardSnapshot->academicPeriod->label ?? $reportCardSnapshot->academicPeriod->name }}</dd>
                    </div>
                    <div class="rounded-lg border p-4">
                        <dt class="text-sm text-muted-foreground">{{ __('Academic year') }}</dt>
                        <dd class="text-lg font-semibold">{{ $reportCardSnapshot->academicYear?->name ?? 'Unknown year' }}</dd>
                    </div>
                    <div class="rounded-lg border p-4">
                        <dt class="text-sm text-muted-foreground">{{ __('Revision') }}</dt>
                        <dd class="text-lg font-semibold">{{ $reportCardSnapshot->revision }} of {{ $latestRevision }}</dd>
                        @if (filled($reportCardSnapshot->reason))
                            <p class="mt-1 text-xs text-muted-foreground">{{ $reportCardSnapshot->reason }}</p>
                        @endif
                    </div>
                    <div class="rounded-lg border p-4">
                        <dt class="text-sm text-muted-foreground">{{ __('Issued') }}</dt>
                        <dd class="text-lg font-semibold">{{ school_time($reportCardSnapshot->published_at)?->translatedFormat('j M Y') }}</dd>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ $reportCardSnapshot->publishedBy?->name ? 'By '.$reportCardSnapshot->publishedBy->name : 'Publisher not recorded' }}
                        </p>
                    </div>
                </dl>
            </slot:content>
        </april:card>

        <april:card>
            <slot:title>{{ $competencyMode ? __('Competency levels') : __('Subject results') }}</slot:title>
            <slot:description>
                {{ $competencyMode ? __('Each domain lists the competencies the teacher assessed, with the mastery level reached.') : __('Each row names the result version this card copied, so anyone can trace a mark back to its source.') }}
            </slot:description>
            <slot:content>
                @if ($competencyMode)
                    @if ($domains === [])
                        <x-empty-state icon="lucide-list-checks" title="{{ __('No competency assessed') }}"
                            description="The card was issued before any competency was assessed for the period." />
                    @else
                        <div class="space-y-6">
                            @foreach ($domains as $domain)
                                <div>
                                    <h3 class="mb-2 font-semibold">{{ $domain['domain'] }}</h3>
                                    <april:data-table>
                                        <slot:header>
                                            <april:data-table-row>
                                                <april:data-table-head>{{ __('Competency') }}</april:data-table-head>
                                                <april:data-table-head>{{ __('Mastery level') }}</april:data-table-head>
                                                <april:data-table-head>{{ __('Teacher comment') }}</april:data-table-head>
                                            </april:data-table-row>
                                        </slot:header>
                                        <slot:body>
                                            @foreach ($domain['competencies'] as $competency)
                                                <april:data-table-row>
                                                    <april:data-table-cell class="font-medium">{{ $competency['name'] }}</april:data-table-cell>
                                                    <april:data-table-cell>{{ __($competency['level_label']) }}</april:data-table-cell>
                                                    <april:data-table-cell class="text-muted-foreground">{{ $competency['comment'] ?? '—' }}</april:data-table-cell>
                                                </april:data-table-row>
                                            @endforeach
                                        </slot:body>
                                    </april:data-table>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @elseif ($results === [])
                    <x-empty-state icon="lucide-book-open" title="{{ __('No subject results on this card') }}"
                        description="The card was issued before any subject result was published for the period." />
                @else
                    <april:data-table>
                        <slot:header>
                            <april:data-table-row>
                                <april:data-table-head>{{ __('Subject') }}</april:data-table-head>
                                <april:data-table-head>{{ __('Coefficient') }}</april:data-table-head>
                                <april:data-table-head>{{ __('Mark /20') }}</april:data-table-head>
                                <april:data-table-head>{{ __('Source revision') }}</april:data-table-head>
                            </april:data-table-row>
                        </slot:header>
                        <slot:body>
                            @foreach ($results as $result)
                                <april:data-table-row>
                                    <april:data-table-cell class="font-medium">{{ $result['subject']['name'] }}</april:data-table-cell>
                                    <april:data-table-cell class="text-muted-foreground">{{ \Illuminate\Support\Number::format($result['coefficient'] ?? 1, 2) }}</april:data-table-cell>
                                    <april:data-table-cell>
                                        {{ ($result['out_of_20'] ?? null) === null ? '—' : \Illuminate\Support\Number::format($result['out_of_20'], 2) }}
                                    </april:data-table-cell>
                                    <april:data-table-cell class="text-muted-foreground">Revision {{ $result['source_revision'] }}</april:data-table-cell>
                                </april:data-table-row>
                            @endforeach
                        </slot:body>
                    </april:data-table>
                @endif
            </slot:content>
        </april:card>

        <april:card>
            <slot:title>{{ __('Revision history') }}</slot:title>
            <slot:description>{{ __('A published card never changes. A correction is issued as the next revision, and every earlier one stays readable.') }}</slot:description>
            <slot:content>
                <ol class="space-y-3">
                    @foreach ($revisions as $revision)
                        <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border p-4 {{ $revision->is($reportCardSnapshot) ? 'border-primary' : '' }}">
                            <div>
                                <p class="font-medium">
                                    Revision {{ $revision->revision }}
                                    @if ($revision->is($reportCardSnapshot))
                                        <span class="ml-1 text-xs text-muted-foreground">{{ __('(you are reading this one)') }}</span>
                                    @endif
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{ school_time($revision->published_at)?->translatedFormat('j M Y') }}
                                    @if ($revision->publishedBy?->name)
                                        · {{ $revision->publishedBy->name }}
                                    @endif
                                    @if (filled($revision->reason))
                                        · {{ $revision->reason }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm text-muted-foreground">
                                    {{ ($revision->payload['summary']['average_out_of_20'] ?? null) === null ? '—' : \Illuminate\Support\Number::format($revision->payload['summary']['average_out_of_20'], 2).'/20' }}
                                </span>
                                @unless ($revision->is($reportCardSnapshot))
                                    <april:button-link href="{{ route('report-cards.show', $revision) }}" variant="outline" size="sm" aria-label="Open revision {{ $revision->revision }}">{{ __('Open') }}</april:button-link>
                                @endunless
                            </div>
                        </li>
                    @endforeach
                </ol>
            </slot:content>
        </april:card>
    </div>
@endsection
