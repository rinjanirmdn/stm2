@extends('layouts.app')

@section('title', 'Forbidden - Slot Time Management')
@section('page_title', 'Forbidden')

@section('content')
    <section class="st-row">
        <div class="st-col-12">
            <div class="st-card st-p-16">
                <h2 class="st-card__title st-mb-6">403 - Forbidden</h2>
                <div class="st-card__subtitle">Kamu tidak punya akses ke halaman ini.</div>

                <div class="st-mt-12 st-flex st-gap-8">
                    <a href="{{ route('dashboard') }}" class="st-btn st-btn--outline-primary">Back to Dashboard</a>
                    <a href="{{ route('slots.index') }}" class="st-btn st-btn--ghost">Go to Slots</a>
                </div>
            </div>
        </div>
    </section>
@endsection
