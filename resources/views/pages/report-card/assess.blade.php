@extends('layouts.app', ['breadcrumbs' => [
    ['href' => route('dashboard'), 'text' => 'Dashboard'],
    ['href' => route('report-cards.index'), 'text' => 'Report cards'],
    ['text' => 'Competency assessment', 'active'],
]])

@section('title', __('Competency assessment'))
@section('page_heading', __('Competency assessment'))

@section('content')
    @livewire('assess-competencies', ['studentRecord' => $studentRecord])
@endsection
