@extends('layouts.app')

@section('title', (string) $title . ' - Slot Time Management')
@section('page_title', (string) $title)

@section('content')
    <section class="st-row">
        <div class="st-col-12">
            <div class="st-card st-p-16">
                <h2 class="st-card__title st-mb-6">{{ $title }}</h2>
                <div class="st-card__subtitle">Halaman ini sedang dimigrasikan ke Laravel.</div>
            </div>
        </div>
    </section>
@endsection
