@extends('vendor.layouts.vendor')

@section('title', 'My Bookings - Vendor Portal')

@section('page_class', 'vendor-page--layout vendor-page--bookings-index')

@section('content')

@php
    $currentStatus = request('status', '');
    $tabCounts = is_array($counts ?? null) ? $counts : [];
@endphp

<!-- Status Tabs -->
<div class="mb-container">
    <div class="mb-scroll-container">
        <div class="mb-tabs" title="My Bookings shows all requests and their latest status. Use the tabs and filters below to narrow down.">
            <a href="{{ route('vendor.bookings.index', array_merge(request()->except('page'), ['status' => 'pending'])) }}"
               class="mb-tab mb-tab--pending {{ $currentStatus === 'pending' ? 'mb-tab--active' : '' }}"
               title="Bookings waiting for approval from admin.">
                <span class="mb-tab__count">{{ ($tabCounts['pending'] ?? 0) }}</span>
                <span>Pending</span>
            </a>
            <a href="{{ route('vendor.bookings.index', array_merge(request()->except('page'), ['status' => 'approved'])) }}"
               class="mb-tab mb-tab--completed {{ $currentStatus === 'approved' ? 'mb-tab--active' : '' }}"
               title="Approved bookings that already have a confirmed schedule.">
                <span class="mb-tab__count">{{ ($tabCounts['approved'] ?? 0) }}</span>
                <span>Approved</span>
            </a>
            <a href="{{ route('vendor.bookings.index', array_merge(request()->except('page'), ['status' => 'cancelled'])) }}"
               class="mb-tab mb-tab--cancelled {{ $currentStatus === 'cancelled' ? 'mb-tab--active' : '' }}"
               title="Bookings cancelled by vendor or admin.">
                <span class="mb-tab__count">{{ ($tabCounts['cancelled'] ?? 0) }}</span>
                <span>Cancelled</span>
            </a>
            <a href="{{ route('vendor.bookings.index', array_merge(request()->except('page'), ['status' => 'rejected'])) }}"
               class="mb-tab mb-tab--action {{ $currentStatus === 'rejected' ? 'mb-tab--active' : '' }}"
               title="Bookings that were rejected during approval.">
                <span class="mb-tab__count">{{ ($tabCounts['rejected'] ?? 0) }}</span>
                <span>Rejected</span>
            </a>
            <a href="{{ route('vendor.bookings.index', array_merge(request()->except('page', 'status'), ['status' => ''])) }}"
               class="mb-tab mb-tab--all {{ $currentStatus === '' ? 'mb-tab--active' : '' }}"
               title="All your bookings, regardless of status.">
                <span class="mb-tab__count">{{ ($tabCounts['all'] ?? $bookings->total()) }}</span>
                <span>All</span>
            </a>
        </div>

        <!-- Content Container -->
        <div class="mb-content-container">
            <div class="mb-content">
                <!-- Search Bar -->
                <form method="GET" action="{{ route('vendor.bookings.index') }}" class="mb-search">
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                    <input type="text" name="search" class="mb-search__input" placeholder="Search ticket, vehicle, PO..." value="{{ request('search') }}">
                    <div id="vendor_reportrange" class="mb-search__input date-range-input" data-auto-submit="false">
                        <div class="date-range-input__left">
                            <i class="fas fa-calendar date-range-icon vendor-icon"></i>
                            <span></span>
                        </div>
                        <i class="fa fa-caret-down"></i>
                    </div>
                    <input type="hidden" name="date_from" id="date_from" value="{{ request('date_from') }}">
                    <input type="hidden" name="date_to" id="date_to" value="{{ request('date_to') }}">
                    <button type="submit" class="vendor-btn vendor-btn--primary vendor-btn--sm">Search</button>
                    @if(request()->hasAny(['search', 'date_from', 'date_to']))
                    <a href="{{ route('vendor.bookings.index', ['status' => $currentStatus]) }}" class="vendor-btn vendor-btn--secondary vendor-btn--sm">Reset</a>
                    @endif
                </form>

    <!-- Booking Rows -->
    @if($bookings->count() > 0)
        @foreach($bookings as $booking)
        @php
            $statusClass = match($booking->status) {
                'pending' => 'pending',
                // Use completed style (green) for approved bookings
                'approved' => 'completed',
                'rejected' => 'rejected',
                'cancelled' => 'cancelled',
                default => 'cancelled'
            };
            $statusLabel = match($booking->status) {
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'cancelled' => 'Cancelled',
                default => ucfirst(str_replace('_', ' ', $booking->status))
            };

            $isRescheduled = ($booking->convertedSlot?->approval_action ?? null) === \App\Models\Slot::APPROVAL_RESCHEDULED;

            // Arrival status logic - selalu tampilkan
            $arrivalStatus = '-';
            $arrivalColor = 'secondary';
            if($booking->actual_arrival && $booking->planned_start) {
                $arrivalDiff = $booking->actual_arrival->diffInMinutes($booking->planned_start, false);
                if($arrivalDiff > 15) {
                    $arrivalStatus = 'Late';
                    $arrivalColor = 'danger';
                } elseif($arrivalDiff >= -15 && $arrivalDiff <= 15) {
                    $arrivalStatus = 'On-Time';
                    $arrivalColor = 'success';
                } else {
                    $arrivalStatus = 'Early';
                    $arrivalColor = 'info';
                }
            }
        @endphp
        <div class="mb-row">
            <span class="mb-row__ticket">{{ $booking->request_number ?? ('REQ-' . $booking->id) }}</span>
            <span class="mb-row__time">
                <i class="fas fa-calendar mb-row__icon vendor-icon"></i>
                {{ $booking->planned_start?->format('d M Y H:i') ?? '-' }}
            </span>
            <span class="mb-row__status mb-row__status--{{ $statusClass }}">{{ $statusLabel }}</span>
            @if($isRescheduled)
                <span class="mb-row__status-tag vendor-badge vendor-badge--info">Rescheduled</span>
            @endif
            @if($arrivalStatus !== '-')
                <span class="mb-row__status mb-row__status--{{ $arrivalColor }} mb-row__status--arrival">
                    <i class="fas fa-clock mb-row__status-icon"></i>{{ $arrivalStatus }}
                </span>
            @endif
            <div class="mb-row__actions">
				<a href="{{ route('vendor.bookings.show', $booking->id) }}" class="mb-row__btn mb-row__btn--view" title="View">
					<i class="fas fa-eye"></i>
				</a>
				@if(in_array($booking->status, ['pending']))
				<form method="POST" action="{{ route('vendor.bookings.cancel', $booking->id) }}" class="vendor-inline-form" onsubmit="return confirm('Cancel this booking?');">
					@csrf
					<input type="hidden" name="reason" value="Cancelled by vendor">
					<button type="submit" class="mb-row__btn mb-row__btn--cancel" title="Cancel">
						<i class="fas fa-times"></i>
					</button>
				</form>
				@endif
			</div>
        </div>
        @endforeach

        <!-- Pagination -->
        <div class="mb-pagination">
            {{ $bookings->withQueryString()->links() }}
        </div>
    @else
        <div class="mb-empty">
            <div class="mb-empty__icon"><i class="fas fa-inbox"></i></div>
            <p class="mb-empty__title">No bookings found</p>
            <p class="mb-empty__subtitle">Try adjusting your filters or create a new booking</p>
            <a href="{{ route('vendor.bookings.create') }}" class="vendor-btn vendor-btn--primary mb-empty__action">Create Booking</a>
        </div>
    @endif
            </div>
        </div>
    </div>

    <!-- Footer Container -->
    <div class="mb-footer-container">
        <div class="mb-footer-text">
            Â© {{ date('Y') }} Slot Time Management. All rights reserved.
        </div>
    </div>
</div>
@endsection

@push('scripts')
@vite(['resources/js/pages/vendor-bookings-index.js'])
@endpush

