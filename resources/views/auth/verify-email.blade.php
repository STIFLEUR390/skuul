@extends('layouts.guest')

@section('title', 'Verify Email')

@section('body')
    <x-partials.authentication-card title="{{ __('Verify your email') }}">
        <x-display-validation-errors />
        <livewire:auth.verify-email-form />
    </x-partials.authentication-card>
@endsection
