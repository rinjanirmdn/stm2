@extends('layouts.app')

@section('title', 'Dashboard - e-Docking Control System')
@section('page_title', 'Dashboard')
@section('body_class', 'st-app--dashboard')

@push('styles')
    @vite(['resources/css/dashboard-react.css'])
@endpush

@section('content')
<div id="react-dashboard" class="st-dashboard-wrap">
    <div id="react-loading" class="st-react-loading">
        <div class="st-react-spinner"></div>
        <span class="st-react-loading-text">Loading Dashboard...</span>
    </div>
</div>

<script>
    window.__DASHBOARD_DATA__ = @json($dashboardData);
    setTimeout(function() {
        var el = document.getElementById('react-loading');
        if (el && el.parentNode) {
            el.innerHTML = '<div class="st-react-error"><strong>React failed to load.</strong><br><span class="st-react-error-hint">Check browser console (F12) for errors.</span></div>';
        }
    }, 8000);
</script>
@endsection

@push('scripts')
    @vite(['resources/js/react/dashboard.jsx'])
@endpush
