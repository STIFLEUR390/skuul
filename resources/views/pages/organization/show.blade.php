@extends('layouts.app', ['breadcrumbs' => [
    ['href' => route('dashboard'), 'text' => 'Dashboard'],
    ['href' => route('organizations.index'), 'text' => 'Organizations'],
    ['href' => route('organizations.show', $organization), 'text' => $organization->name, 'active'],
]])

@section('title', $organization->name)

@section('page_heading', $organization->name)

@section('content')
    <april:card>
        <slot:title>{{ $organization->name }}</slot:title>
        <slot:description>{{ $organization->code }}</slot:description>
        <slot:content class="space-y-6">
            <div class="flex flex-wrap justify-end gap-2">
                @can('viewReports', $organization)
                    <april:button-link href="{{ route('organizations.dashboard', $organization) }}" variant="outline">{{ __('Organization overview') }}</april:button-link>
                @endcan
                @can('manageMembers', $organization)
                    <april:button-link href="{{ route('organizations.members.index', $organization) }}" variant="outline">{{ __('Members') }}</april:button-link>
                @endcan
                @can('manageDomains', $organization)
                    <april:button-link href="{{ route('organizations.domains.index', $organization) }}" variant="outline">{{ __('Web addresses') }}</april:button-link>
                @endcan
                @can('manageDomains', $organization)
                    <april:button-link href="{{ route('organizations.billing-groups.index', $organization) }}" variant="outline">{{ __('Billing groups') }}</april:button-link>
                @endcan
                @can('manageCampuses', $organization)
                    <april:button-link href="{{ route('organizations.boarding-residences.index', $organization) }}" variant="outline">{{ __('Shared residences') }}</april:button-link>
                @endcan
                @can('manageCalendar', $organization)
                    <april:button-link href="{{ route('organizations.calendar-templates.index', $organization) }}" variant="outline">{{ __('Calendar templates') }}</april:button-link>
                @endcan
                @can('update', $organization)
                    <april:button-link href="{{ route('organizations.edit', $organization) }}">{{ __('Organization settings') }}</april:button-link>
                @endcan
            </div>
            <div>
                <h2 class="font-semibold">{{ __('Campuses') }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">{{ __('An organization administrator can manage campus setup. Operational access still needs a school membership and school role.') }}</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($organization->schools as $school)
                    @can('update', $school)
                        <a href="{{ route('schools.edit', $school) }}" class="rounded-lg border p-5 transition hover:bg-muted/50">
                            <p class="font-semibold">{{ $school->name }}</p>
                            <p class="text-sm text-muted-foreground">{{ $school->address ?: '—' }}</p>
                        </a>
                    @else
                        <div class="rounded-lg border p-5">
                            <p class="font-semibold">{{ $school->name }}</p>
                            <p class="text-sm text-muted-foreground">{{ $school->address ?: '—' }}</p>
                        </div>
                    @endcan
                @empty
                    <p class="text-muted-foreground">{{ __('No campuses have been added.') }}</p>
                @endforelse
            </div>
            @can('createForOrganization', [\App\Models\School::class, $organization])
                <april:button-link href="{{ route('schools.create') }}" class="h-11 select-none">{{ __('Add campus') }}</april:button-link>
            @endcan
        </slot:content>
    </april:card>
@endsection
