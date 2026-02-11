@extends('layouts.app')

@section('title', 'Dashboard - Slot Time Management')
@section('page_title', 'Dashboard')
@section('body_class', 'st-app--dashboard')

@push('styles')
    @vite(['resources/css/dashboard-react.css'])
@endpush

@section('content')
<div id="react-dashboard" style="width:100%; padding: 0 4px;">
    <div id="react-loading" style="display:flex;align-items:center;justify-content:center;height:300px;flex-direction:column;gap:12px;">
        <div style="width:32px;height:32px;border:3px solid #e2e8f0;border-top-color:#0284c7;border-radius:50%;animation:spin 0.8s linear infinite;"></div>
        <span style="font-size:13px;color:#64748b;">Loading Dashboard...</span>
        <style>@keyframes spin{to{transform:rotate(360deg)}}</style>
    </div>
</div>

<script>
    window.__DASHBOARD_DATA__ = @json($dashboardData);
    setTimeout(function() {
        var el = document.getElementById('react-loading');
        if (el && el.parentNode) {
            el.innerHTML = '<div style="padding:24px;text-align:center;color:#dc2626;font-size:13px;"><strong>React failed to load.</strong><br><span style="color:#64748b;font-size:11px;">Check browser console (F12) for errors.</span></div>';
        }
    }, 8000);
</script>
@endsection

@push('scripts')
    @vite(['resources/js/react/dashboard.jsx'])
@endpush
