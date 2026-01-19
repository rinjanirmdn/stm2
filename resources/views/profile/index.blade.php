@extends('layouts.app')

@section('title', 'Profile - Slot Time Management')
@section('page_title', 'Profile')

@section('content')
    <h1 class="h3 mb-3">Profile</h1>

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="{{ $user->username ?? '' }}" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" value="{{ $user->full_name ?? '' }}" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <input type="text" class="form-control" value="{{ ucfirst($user->role ?? '') }}" disabled>
            </div>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
@endsection
