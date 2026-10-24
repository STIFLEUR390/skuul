@extends('layouts.app', ['breadcrumbs' => [
    ['href'=> route('dashboard'), 'text'=> 'Dashboard'],
    ['href'=> route('profile.show'), 'text'=> 'Profile' , 'active']
]])

@section('title',  __('Profile'))

@section('page_heading',  __('Profile'))

@section('content')
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6">
        @if (Laravel\Fortify\Features::canUpdateProfileInformation())
            @livewire('profile.update-profile-information-form')
        @endif

        <x-partials.action-section>
            <x-slot name="title">{{ __('Language') }}</x-slot>
            <x-slot name="description">{{ __('Choose the language this account reads the interface in. It applies to you only.') }}</x-slot>
            <x-slot name="content">
                <form method="POST" action="{{ route('user.language.update') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    @csrf
                    <label for="locale" class="sr-only">{{ __('Language') }}</label>
                    <select id="locale" name="locale" class="h-11 w-full rounded-md border border-input bg-background px-3 text-sm sm:max-w-xs">
                        @foreach (config('app.supported_locales', []) as $localeCode => $localeName)
                            <option value="{{ $localeCode }}" @selected((auth()->user()->locale ?? config('app.locale')) === $localeCode)>{{ $localeName }}</option>
                        @endforeach
                    </select>
                    <april:button type="submit" class="h-11 select-none">{{ __('Save') }}</april:button>
                </form>
                <x-field-error name="locale" class="mt-1" />
            </x-slot>
        </x-partials.action-section>

        @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
            <div id="update-password">
                @livewire('profile.update-password-form')
            </div>
        @endif

        @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
            @livewire('profile.two-factor-authentication-form')
        @endif

        @livewire('profile.logout-other-browser-sessions-form')

        @if (current_school() !== null)
            <x-partials.action-section>
                <x-slot name="title">{{ __('Notice email') }}</x-slot>
                <x-slot name="description">{{ __('Choose whether this school emails you its notices.') }}</x-slot>
                <x-slot name="content">
                    <april:button-link variant="outline" href="{{ route('notice-preferences.edit') }}">{{ __('Open notice email settings') }}</april:button-link>
                </x-slot>
            </x-partials.action-section>
        @endif
    </div>
@endsection
