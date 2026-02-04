@extends('vendor.layouts.vendor')

@section('title', 'Create Booking - Vendor Portal')

@section('page_class', 'vendor-page--layout vendor-page--bookings-create')

@section('content')

<div class="cb-container">
    <div class="cb-scroll-container">
        <div class="cb-content-container">
            <div class="cb-header">
                <h1 class="cb-header__title">
                    <i class="fas fa-plus-circle"></i>
                    Create New Booking
                </h1>
                <a href="{{ route('vendor.bookings.index') }}" class="cb-btn cb-btn--secondary">
                    <i class="fas fa-arrow-left"></i> Back to Bookings
                </a>
            </div>

            @if(session('error'))
                <div class="cb-alert cb-alert--error">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('vendor.bookings.store') }}" enctype="multipart/form-data" id="booking-form">
                @csrf

                <div class="cb-form">
                    @include('vendor.bookings.partials._po_selection')
                    @include('vendor.bookings.partials._schedule_availability')
                    @include('vendor.bookings.partials._vehicle_documents_notes')
                </div>

                <div class="cb-actions">
                    <a href="{{ route('vendor.bookings.index') }}" class="cb-btn cb-btn--secondary">
                        Cancel
                    </a>
                    <button type="submit" class="cb-btn cb-btn--primary" id="submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        Submit Booking Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script type="application/json" id="vendor_booking_create_config">{!! json_encode([
    'poSearchUrl' => route('vendor.ajax.po_search'),
    'poDetailUrl' => url('vendor/ajax/po'),
    'availableSlotsUrl' => route('vendor.ajax.available_slots'),
]) !!}</script>
@endpush
@endsection

