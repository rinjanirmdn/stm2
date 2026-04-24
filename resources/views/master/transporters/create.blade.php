@extends('layouts.app')

@section('title', 'Add Transporter - e-Docking Control System')
@section('page_title', 'Add Vendor Transporter')

@section('content')
    <div class="st-card st-maxw-600">
        <form method="POST" action="{{ route('master.transporters.store') }}">
            @csrf

            <div class="st-form-field">
                <label class="st-label">Transporter Name <span class="st-text--danger-dark">*</span></label>
                <input type="text" name="name" class="st-input{{ $errors->has('name') ? ' st-input--invalid' : '' }}" required value="{{ old('name') }}">
                @error('name')
                    <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                @enderror
            </div>

            <div class="st-form-field">
                <label class="st-label st-flex st-align-center st-gap-8 st-cursor-pointer" style="display:inline-flex;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <span>Active</span>
                </label>
            </div>

            <div class="st-form-actions st-mt-16">
                <button type="submit" class="st-btn st-btn--primary">Save</button>
                <a href="{{ route('master.transporters.index') }}" class="st-btn st-btn--outline-primary">Cancel</a>
            </div>
        </form>
    </div>
@endsection
