<div wire:init="loadDashboard" class="space-y-6">
    @if ($loaded)
        <section class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <p class="text-xs font-semibold uppercase text-muted-foreground">{{ __('Organization overview') }}</p>
                <h2 class="mt-1 text-2xl font-semibold tracking-tight">{{ __('Campus health at a glance') }}</h2>
                <p class="mt-2 max-w-3xl text-sm text-muted-foreground">Aggregate operational signals across {{ $organization->name }}. Individual people and school records remain in their campus context.</p>
            </div>
            <april:button wire:click="loadDashboard" wire:loading.attr="disabled" wire:target="loadDashboard" variant="outline" class="w-fit">
                <x-lucide-refresh-cw wire:loading.remove wire:target="loadDashboard" class="mr-2 size-4" />
                <x-lucide-loader-circle wire:loading wire:target="loadDashboard" class="mr-2 size-4 animate-spin" />
                {{ __('Refresh') }}
            </april:button>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <april:card>
                <slot:title class="flex items-center justify-between gap-3 text-base">
                    {{ __('Campuses') }}
                    <span class="flex size-9 items-center justify-center rounded-md bg-muted text-foreground"><x-lucide-school class="size-4" /></span>
                </slot:title>
                <slot:content><p class="text-3xl font-semibold tracking-tight">{{ number_format($campusCount) }}</p><p class="mt-1 text-sm text-muted-foreground">{{ __('Campuses in this organization') }}</p></slot:content>
            </april:card>
            <april:card>
                <slot:title class="flex items-center justify-between gap-3 text-base">
                    {{ __('Active students') }}
                    <span class="flex size-9 items-center justify-center rounded-md bg-muted text-foreground"><x-lucide-users class="size-4" /></span>
                </slot:title>
                <slot:content><p class="text-3xl font-semibold tracking-tight">{{ number_format($activeStudents) }}</p><p class="mt-1 text-sm text-muted-foreground">{{ __('Current enrollment total') }}</p></slot:content>
            </april:card>
            <april:card>
                <slot:title class="flex items-center justify-between gap-3 text-base">
                    {{ __('Campus access') }}
                    <span class="flex size-9 items-center justify-center rounded-md bg-muted text-foreground"><x-lucide-briefcase-business class="size-4" /></span>
                </slot:title>
                <slot:content><p class="text-3xl font-semibold tracking-tight">{{ number_format($campusAccessHolders) }}</p><p class="mt-1 text-sm text-muted-foreground">{{ __('People who can work in a campus') }}</p></slot:content>
            </april:card>
            <april:card>
                <slot:title class="flex items-center justify-between gap-3 text-base">
                    {{ __('Academic setup') }}
                    <span class="flex size-9 items-center justify-center rounded-md bg-muted text-foreground"><x-lucide-calendar-range class="size-4" /></span>
                </slot:title>
                <slot:content><p class="text-3xl font-semibold tracking-tight">{{ number_format($campusesMissingAcademicSetup) }}</p><p class="mt-1 text-sm text-muted-foreground">{{ __('Campuses needing setup') }}</p></slot:content>
            </april:card>
        </section>

        <april:card>
            <slot:title>{{ __('Campus health') }}</slot:title>
            <slot:description>{{ __('Totals only. No student, staff, or campus record is read from here.') }}</slot:description>
            <slot:content class="space-y-4">
                @forelse ($campuses as $campus)
                    @php($hasAcademicSetup = $this->hasRequiredAcademicSetup($campus))
                    <div wire:key="campus-health-{{ $campus->id }}" class="grid gap-4 rounded-lg border p-4 lg:grid-cols-[minmax(12rem,1.4fr)_repeat(4,minmax(0,1fr))_auto] lg:items-center">
                        <div>
                            <p class="font-semibold">{{ $campus->name }}</p>
                            <p class="mt-1 text-sm text-muted-foreground">{{ __('Campus operational summary') }}</p>
                        </div>
                        <div><p class="text-xs font-medium uppercase text-muted-foreground">{{ __('Students') }}</p><p class="mt-1 font-semibold">{{ number_format($campus->active_students_count) }} active</p></div>
                        <div><p class="text-xs font-medium uppercase text-muted-foreground">{{ __('Campus access') }}</p><p class="mt-1 font-semibold">{{ number_format($campus->campus_access_count) }} people</p></div>
                        <div><p class="text-xs font-medium uppercase text-muted-foreground">{{ __('Academic year') }}</p><p class="mt-1 font-semibold">{{ $campus->academicYear?->name ?? 'Not set' }}</p><p class="text-sm text-muted-foreground">{{ $this->academicPeriodStatus($campus->academicYear?->status) }}</p></div>
                        <div><p class="text-xs font-medium uppercase text-muted-foreground">{{ school_term('period', 'Academic period') }}</p><p class="mt-1 font-semibold">{{ $campus->academicPeriod?->name ?? 'Not set' }}</p><p class="text-sm text-muted-foreground">{{ $this->academicPeriodStatus($campus->academicPeriod?->status) }}</p></div>
                        <div class="flex items-center justify-between gap-3 lg:flex-col lg:items-end">
                            <april:badge :variant="$hasAcademicSetup ? 'secondary' : 'destructive'">{{ $hasAcademicSetup ? 'Ready' : 'Setup needed' }}</april:badge>
                            <april:button-link href="{{ route('schools.show', $campus) }}" variant="link" size="none" class="gap-1 p-0">{{ __('Open campus') }} <span aria-hidden="true">→</span></april:button-link>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-start gap-3 rounded-lg border border-dashed p-6 sm:flex-row sm:items-center">
                        <span class="flex size-10 items-center justify-center rounded-md bg-muted text-muted-foreground"><x-lucide-school class="size-5" /></span>
                        <div><p class="font-semibold">{{ __('No campuses yet') }}</p><p class="mt-1 text-sm text-muted-foreground">{{ __('Add a campus to begin tracking organization-level health.') }}</p></div>
                    </div>
                @endforelse
            </slot:content>
        </april:card>
    @else
        <div wire:loading.remove wire:target="loadDashboard" class="space-y-6" aria-live="polite">
            <div class="space-y-2"><div class="h-3 w-40 animate-pulse rounded bg-muted"></div><div class="h-8 w-72 animate-pulse rounded bg-muted"></div><div class="h-4 max-w-2xl animate-pulse rounded bg-muted"></div></div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">@for ($index = 0; $index < 4; $index++) <div class="h-36 animate-pulse rounded-xl border bg-muted/40" wire:key="dashboard-card-placeholder-{{ $index }}"></div> @endfor</div>
            <div class="h-72 animate-pulse rounded-xl border bg-muted/40"></div>
        </div>
        <div wire:loading wire:target="loadDashboard" class="space-y-6" aria-live="polite" aria-label="{{ __('Loading organization dashboard') }}">
            <div class="space-y-2"><div class="h-3 w-40 animate-pulse rounded bg-muted"></div><div class="h-8 w-72 animate-pulse rounded bg-muted"></div><div class="h-4 max-w-2xl animate-pulse rounded bg-muted"></div></div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">@for ($index = 0; $index < 4; $index++) <div class="h-36 animate-pulse rounded-xl border bg-muted/40" wire:key="dashboard-loading-placeholder-{{ $index }}"></div> @endfor</div>
            <div class="h-72 animate-pulse rounded-xl border bg-muted/40"></div>
        </div>
    @endif
</div>
