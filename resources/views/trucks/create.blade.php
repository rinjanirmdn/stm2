@extends('layouts.app')

@section('title', 'Add Truck - Slot Time Management')
@section('page_title', 'Add Truck')

@section('content')
    <section class="st-row">
        <div class="st-col-12">
            <div class="st-card" style="padding:16px;max-width:760px;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                    <div>
                        <h2 class="st-card__title" style="margin:0 0 6px 0;">Add Truck</h2>
                    </div>
                </div>

                <form method="POST" action="{{ route('trucks.store') }}" style="margin-top:14px;">
                    @csrf

                    <div class="st-form-field" style="margin-bottom:10px;">
                        <label class="st-label">Truck Type</label>
                        <input type="text" name="truck_type" class="st-input" maxlength="100" value="{{ old('truck_type') }}" required>
                    </div>

                    <div class="st-form-field" style="margin-bottom:14px;">
                        <label class="st-label">Duration (minutes)</label>
                        <input type="number" name="target_duration_minutes" class="st-input" min="1" max="1440" value="{{ old('target_duration_minutes') }}" required>
                    </div>

                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="submit" class="st-btn st-btn--primary">Save</button>
                        <a href="{{ route('trucks.index') }}" class="st-btn st-btn--secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
