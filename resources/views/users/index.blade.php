@extends('layouts.app')

@section('title', 'Users - Slot Time Management')
@section('page_title', 'Users')

@section('content')
    <div class="st-card st-mb-12">
        <form method="GET" action="{{ route('users.index') }}">
            <div class="st-p-12">
                <div class="st-form-row st-gap-4 st-align-end">
                    <div class="st-form-field st-maxw-200">
                        <label class="st-label">Search</label>
                        <input type="text" name="q" class="st-input" placeholder="Email or name" value="{{ $q ?? '' }}">
                    </div>
                    <div class="st-form-field st-minw-80 st-flex st-flex-0 st-justify-end st-gap-8">
                        <a href="{{ route('users.index') }}" class="st-btn st-btn--outline-primary">Reset</a>
                        <a href="{{ route('users.create') }}" class="st-btn st-btn--primary">Add User</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <section class="st-row st-flex-1">
        <div class="st-col-12 st-flex st-flex-1 st-flex-col">
            <div class="st-card st-card--fill">
                <form method="GET" id="user-filter-form" data-multi-sort="1" action="{{ route('users.index') }}">
                @php
                    $sortsArr = isset($sorts) && is_array($sorts) ? $sorts : [];
                    $dirsArr = isset($dirs) && is_array($dirs) ? $dirs : [];
                @endphp
                @foreach ($sortsArr as $i => $s)
                    @php $d = $dirsArr[$i] ?? 'asc'; @endphp
                    <input type="hidden" name="sort[]" value="{{ $s }}">
                    <input type="hidden" name="dir[]" value="{{ $d }}">
                @endforeach
                <div class="st-table-wrapper st-minh-400">
                    <table class="st-table">
                        <thead>
                            <tr>
                                <th class="st-w-60">#</th>
                                <th class="st-w-180">
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">NIK</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="nik" data-type="text" title="Sort">â‡…</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="nik" title="Filter">â·</button>
                                        </span>
                                        <div class="st-filter-panel st-panel st-panel--wide st-panel--scroll" data-filter-panel="nik">
                                            <div class="st-panel__title">NIK Filter</div>
                                            <input type="text" name="nik" form="user-filter-form" class="st-input" placeholder="Search NIK..." value="{{ $nik ?? '' }}">
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="nik">Clear</button>
                                            </div>
                                        </div>
                                        <div class="st-sort-panel st-panel st-panel--medium" data-sort-panel="nik">
                                            <div class="st-panel__title">Sort NIK</div>
                                            <button type="button" class="st-sort-option st-sort-option--compact" data-sort="nik" data-dir="asc">
                                                A-Z
                                            </button>
                                            <button type="button" class="st-sort-option st-sort-option--compact" data-sort="nik" data-dir="desc">
                                                Z-A
                                            </button>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Full Name</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="name" data-type="text" title="Sort">â‡…</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="full_name" title="Filter">â·</button>
                                        </span>
                                        <div class="st-filter-panel st-panel st-panel--wide st-panel--scroll" data-filter-panel="full_name">
                                            <div class="st-panel__title">Full Name Filter</div>
                                            <input type="text" name="full_name" form="user-filter-form" class="st-input" placeholder="Search name..." value="{{ $full_name ?? '' }}">
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="full_name">Clear</button>
                                            </div>
                                        </div>
                                        <div class="st-sort-panel st-panel st-panel--medium" data-sort-panel="name">
                                            <div class="st-panel__title">Sort Name</div>
                                            <button type="button" class="st-sort-option st-sort-option--compact" data-sort="name" data-dir="asc">
                                                A-Z
                                            </button>
                                            <button type="button" class="st-sort-option st-sort-option--compact" data-sort="name" data-dir="desc">
                                                Z-A
                                            </button>
                                        </div>
                                    </div>
                                </th>
                                <th class="st-w-180 st-th-center">
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Email</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="email" data-type="text" title="Sort">â‡…</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="email" title="Filter">â·</button>
                                        </span>
                                        <div class="st-filter-panel st-panel st-panel--wide st-panel--scroll" data-filter-panel="email">
                                            <div class="st-panel__title">Email Filter</div>
                                            <input type="text" name="email" form="user-filter-form" class="st-input" placeholder="Search Email..." value="{{ $email ?? '' }}">
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="email">Clear</button>
                                            </div>
                                        </div>
                                        <div class="st-sort-panel st-panel st-panel--medium" data-sort-panel="email">
                                            <div class="st-panel__title">Sort Email</div>
                                            <button type="button" class="st-sort-option st-sort-option--compact" data-sort="email" data-dir="asc">
                                                A-Z
                                            </button>
                                            <button type="button" class="st-sort-option st-sort-option--compact" data-sort="email" data-dir="desc">
                                                Z-A
                                            </button>
                                        </div>
                                    </div>
                                </th>
                                <th class="st-w-140 st-th-center">
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Role</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="role" data-type="text" title="Sort">â‡…</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="role" title="Filter">â·</button>
                                        </span>
                                        <div class="st-filter-panel st-panel st-panel--wide st-panel--scroll" data-filter-panel="role">
                                            <div class="st-panel__title">Role Filter</div>
                                            <select name="role" form="user-filter-form" class="st-select">
                                                <option value="">All Roles</option>
                                                <option value="admin" {{ ($role ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                                                <option value="section_head" {{ ($role ?? '') === 'section_head' ? 'selected' : '' }}>Section Head</option>
                                                <option value="operator" {{ ($role ?? '') === 'operator' ? 'selected' : '' }}>Operator</option>
                                                <option value="vendor" {{ ($role ?? '') === 'vendor' ? 'selected' : '' }}>Vendor</option>
                                            </select>
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="role">Clear</button>
                                            </div>
                                        </div>
                                        <div class="st-sort-panel st-panel st-panel--medium" data-sort-panel="role">
                                            <div class="st-panel__title">Sort Role</div>
                                            <button type="button" class="st-sort-option st-sort-option--compact" data-sort="role" data-dir="asc">
                                                A-Z
                                            </button>
                                            <button type="button" class="st-sort-option st-sort-option--compact" data-sort="role" data-dir="desc">
                                                Z-A
                                            </button>
                                        </div>
                                    </div>
                                </th>
                                <th class="st-w-120">
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Status</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="is_active" data-type="text" title="Sort">â‡…</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="is_active" title="Filter">â·</button>
                                        </span>
                                        <div class="st-filter-panel st-panel st-panel--wide st-panel--scroll" data-filter-panel="is_active">
                                            <div class="st-panel__title">Status Filter</div>
                                            <select name="is_active" form="user-filter-form" class="st-select">
                                                <option value="">All Status</option>
                                                <option value="1" {{ ($is_active ?? '') === '1' ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ ($is_active ?? '') === '0' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                            <div class="st-panel__actions">
                                                <button type="button" class="st-btn st-btn--sm st-btn--outline-primary st-filter-clear" data-filter="is_active">Clear</button>
                                            </div>
                                        </div>
                                        <div class="st-sort-panel st-panel st-panel--medium" data-sort-panel="is_active">
                                            <div class="st-panel__title">Sort Status</div>
                                            <button type="button" class="st-sort-option st-sort-option--compact" data-sort="is_active" data-dir="asc">
                                                A-Z
                                            </button>
                                            <button type="button" class="st-sort-option st-sort-option--compact" data-sort="is_active" data-dir="desc">
                                                Z-A
                                            </button>
                                        </div>
                                    </div>
                                </th>
                                <th class="st-w-190 st-th-center">
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Created</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="created_at" data-type="date" title="Sort">â‡…</button>
                                        </span>
                                        <div class="st-sort-panel st-panel st-panel--medium" data-sort-panel="created_at">
                                            <div class="st-panel__title">Sort Created</div>
                                            <button type="button" class="st-sort-option st-sort-option--compact" data-sort="created_at" data-dir="desc">
                                                Newest
                                            </button>
                                            <button type="button" class="st-sort-option st-sort-option--compact" data-sort="created_at" data-dir="asc">
                                                Oldest
                                            </button>
                                        </div>
                                    </div>
                                </th>
                                <th class="st-w-260 st-th-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($users as $u)
                            @php
                                $isCurrentUser = auth()->check() && (int)(auth()->user()->id ?? 0) === (int)$u->id;
                                $roleText = (string) ($u->role_name ?? '');
                                $roleVal = $roleText !== '' ? strtolower(str_replace(' ', '_', $roleText)) : (string) ($u->role ?? 'operator');
                                $roleText = $roleText !== '' ? $roleText : ($roleVal === 'admin' ? 'Admin' : ($roleVal === 'section_head' ? 'Section Head' : 'Operator'));
                                $deleteConfirmMsg = 'Are you sure you want to delete this user?';
                            @endphp
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td class="st-font-semibold">{{ $u->nik ?? '-' }}</td>
                                <td>{{ $u->full_name ?? '-' }}</td>
                                <td class="st-td-center">{{ $u->email ?? '-' }}</td>
                                <td class="st-td-center">
                                    <span class="st-badge st-badge--{{ $roleVal }}">{{ $roleText }}</span>
                                </td>
                                <td class="st-td-center">
                                    @if($u->is_active)
                                        <span class="st-badge st-badge--success">Active</span>
                                    @else
                                        <span class="st-badge st-badge--danger">Inactive</span>
                                    @endif
                                </td>
                                <td class="st-td-center">
                                    @php
                                        $createdAt = $u->created_at ?? null;
                                        if ($createdAt) {
                                            try {
                                                $date = new \DateTime($createdAt);
                                                echo $date->format('d M Y H:i');
                                            } catch (\Exception $e) {
                                                echo '-';
                                            }
                                        } else {
                                            echo '-';
                                        }
                                    @endphp
                                </td>
                                <td class="st-td-center">
                                    <div class="tw-actionbar">
                                        <a href="{{ route('users.edit', ['userId' => $u->id]) }}" class="tw-action" data-tooltip="Edit" aria-label="Edit">
                                            <i class="fa-solid fa-pencil"></i>
                                        </a>

                                        @if (! $isCurrentUser)
                                            <form method="POST" action="{{ route('users.delete', ['userId' => $u->id]) }}" class="st-inline-form">
                                                @csrf
                                                <button type="submit" class="tw-action tw-action--danger" data-tooltip="Delete" aria-label="Delete" onclick="return confirm('{{ $deleteConfirmMsg }}');">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="st-text--muted st-text--sm">(current)</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="st-table-empty st-text-center st-text--muted st-py-16">No users found</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
@vite(['resources/js/pages/users-index.js'])
@endpush

