@extends('layouts.app')

@section('title', 'Forbidden - e-Docking Control System')
@section('page_title', 'Forbidden')

@section('content')
    <section class="st-row">
        <div class="st-col-12">
            <div class="st-card st-p-16">
                <h2 class="st-card__title st-mb-6">403 - Forbidden</h2>
                <div class="st-card__subtitle">You do not have access to this page.</div>

                <div class="st-mt-12 st-flex st-gap-8">
                    <a href="{{ url('/') }}" class="st-btn st-btn--outline-primary">Return Home</a>
                </div>
            </div>
        </div>
    </section>
@endsection
