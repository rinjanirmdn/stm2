@extends('layouts.app')

@section('title', 'Profile - Slot Time Management')
@section('page_title', 'Profile')

@section('content')
    <h1 class="h3 mb-3">Profile</h1>

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="text" class="form-control" value="{{ $user->email ?? '' }}" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" value="{{ $user->name ?? '' }}" disabled>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <input type="text" class="form-control" value="{{ ucfirst($user->getRoleNames()->first() ?? '') }}" disabled>
            </div>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
@endsection
