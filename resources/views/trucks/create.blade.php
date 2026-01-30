@extends('layouts.app')

@section('title', 'Add Truck - Slot Time Management')
@section('page_title', 'Add Truck')

@section('content')
    <section class="st-row">
        <div class="st-col-12">
            <div class="st-card st-p-16 st-maxw-760">
                <div class="st-flex st-justify-between st-align-center st-gap-10">
                    <div>
                        <h2 class="st-card__title st-mb-6">Add Truck</h2>
                    </div>
                </div>

                <form method="POST" action="{{ route('trucks.store') }}" class="st-mt-14">
                    @csrf

                    <div class="st-form-field st-mb-10">
                        <label class="st-label">Truck Type</label>
                        <input type="text" name="truck_type" class="st-input" maxlength="100" value="{{ old('truck_type') }}" required>
                    </div>

                    <div class="st-form-field st-mb-14">
                        <label class="st-label">Duration (minutes)</label>
                        <input type="number" name="target_duration_minutes" class="st-input" min="1" max="1440" value="{{ old('target_duration_minutes') }}" required>
                    </div>

                    <div class="st-flex st-gap-8 st-flex-wrap">
                        <button type="submit" class="st-btn st-btn--primary">Save</button>
                        <a href="{{ route('trucks.index') }}" class="st-btn st-btn--outline-primary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
