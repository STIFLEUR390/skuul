@extends('layouts.app', ['breadcrumbs' => [
    ['href' => route('dashboard'), 'text' => 'Dashboard'],
    ['href' => route('fee-invoices.index'), 'text' => 'Finance'],
    ['text' => 'Cash sessions', 'active'],
]])

@section('title', 'Cash sessions')
@section('page_heading', 'Cash sessions')

@section('content')
    <div class="flex flex-col gap-8">
        @livewire('cash-session-desk')

        <section>
            <h2 class="mb-3 text-lg font-semibold">{{ __('Closed sessions') }}</h2>

            @if ($sessions->isEmpty())
                <p class="py-6 text-sm text-muted-foreground">{{ __('No cash sessions yet') }}</p>
            @else
                <ul class="divide-y border-y" aria-label="{{ __('Cash sessions') }}">
                    @foreach ($sessions as $session)
                        <li class="flex items-center justify-between gap-4 py-3">
                            <div class="min-w-0 text-sm">
                                <p class="font-medium">{{ $session->opened_at->translatedFormat('j M Y H:i') }}</p>
                                <p class="truncate text-muted-foreground">
                                    {{ $session->openedBy?->name ?? '—' }}
                                    @if ($session->isOpen())
                                        · {{ __('Open') }}
                                    @else
                                        · {{ __('Closed') }} {{ $session->closed_at->translatedFormat('H:i') }}
                                    @endif
                                </p>
                            </div>
                            <div class="shrink-0 text-right text-sm tabular-nums">
                                <p>{{ money_text($session->counted_amount) }}</p>
                                @if ($session->variance() !== null && $session->variance() != 0.0)
                                    <p class="text-destructive">{{ money_text($session->variance()) }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4">{{ $sessions->links('components.pagination-links-view') }}</div>
            @endif
        </section>
    </div>
@endsection
