@extends('layouts.app')

@section('title', 'Forbidden - Slot Time Management')
@section('page_title', 'Forbidden')

@section('content')
    <section class="st-row">
        <div class="st-col-12">
            <div class="st-card" style="padding:16px;">
                <h2 class="st-card__title" style="margin:0 0 6px 0;">403 - Forbidden</h2>
                <div class="st-card__subtitle">Kamu tidak punya akses ke halaman ini.</div>

                <div style="margin-top:12px;display:flex;gap:8px;">
                    <a href="{{ route('dashboard') }}" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary);">Back to Dashboard</a>
                    <a href="{{ route('slots.index') }}" class="st-btn st-btn--ghost">Go to Slots</a>
                </div>
            </div>
        </div>
    </section>
@endsection
