@extends('layouts.app')

@section('title', 'Users - Slot Time Management')
@section('page_title', 'Users')

@section('content')
    <div class="st-card" style="margin-bottom:12px;">
        <form method="GET" action="{{ route('users.index') }}">
            <div style="padding:12px;">
                <div class="st-form-row" style="gap:4px;align-items:flex-end;">
                    <div class="st-form-field" style="max-width:200px;">
                        <label class="st-label">Search</label>
                        <input type="text" name="q" class="st-input" placeholder="NIK/Username or name" value="{{ $q ?? '' }}">
                    </div>
                    <div class="st-form-field" style="min-width:80px;flex:0 0 auto;display:flex;justify-content:flex-end;">
                        <a href="{{ route('users.index') }}" class="st-btn" style="background:transparent;color:var(--primary);border:1px solid var(--primary);">Reset</a>
                        <a href="{{ route('users.create') }}" class="st-btn st-btn--primary">Add User</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <section class="st-row" style="flex:1;">
        <div class="st-col-12" style="flex:1;display:flex;flex-direction:column;">
            <div class="st-card" style="margin-bottom:0;flex:1;display:flex;flex-direction:column;position:relative;">
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
                <div class="st-table-wrapper" style="min-height: 400px;">
                    <table class="st-table">
                        <thead>
                            <tr>
                                <th style="width:60px;">#</th>
                                <th style="width:180px;">
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">NIK/Username</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="nik" data-type="text" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="nik" title="Filter">⏷</button>
                                        </span>
                                        <div class="st-filter-panel" data-filter-panel="nik" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">NIK/Username Filter</div>
                                            <input type="text" name="nik" form="user-filter-form" class="st-input" placeholder="Search NIK/Username..." value="{{ $nik ?? '' }}">
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm" style="background:transparent;color:var(--primary);border:1px solid var(--primary); st-filter-clear" data-filter="nik">Clear</button>
                                            </div>
                                        </div>
                                        <div class="st-sort-panel" data-sort-panel="nik" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Sort NIK/Username</div>
                                            <button type="button" class="st-sort-option" data-sort="nik" data-dir="asc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;margin-bottom:2px;">
                                                A-Z
                                            </button>
                                            <button type="button" class="st-sort-option" data-sort="nik" data-dir="desc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;">
                                                Z-A
                                            </button>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Full Name</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="full_name" data-type="text" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="full_name" title="Filter">⏷</button>
                                        </span>
                                        <div class="st-filter-panel" data-filter-panel="full_name" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Full Name Filter</div>
                                            <input type="text" name="full_name" form="user-filter-form" class="st-input" placeholder="Search name..." value="{{ $full_name ?? '' }}">
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm" style="background:transparent;color:var(--primary);border:1px solid var(--primary); st-filter-clear" data-filter="full_name">Clear</button>
                                            </div>
                                        </div>
                                        <div class="st-sort-panel" data-sort-panel="full_name" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Sort Full Name</div>
                                            <button type="button" class="st-sort-option" data-sort="full_name" data-dir="asc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;margin-bottom:2px;">
                                                A-Z
                                            </button>
                                            <button type="button" class="st-sort-option" data-sort="full_name" data-dir="desc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;">
                                                Z-A
                                            </button>
                                        </div>
                                    </div>
                                </th>
                                <th style="width:140px;">
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Role</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="role" data-type="text" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="role" title="Filter">⏷</button>
                                        </span>
                                        <div class="st-filter-panel" data-filter-panel="role" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Role Filter</div>
                                            <select name="role" form="user-filter-form" class="st-select">
                                                <option value="">All Roles</option>
                                                <option value="admin" {{ ($role ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                                                <option value="section_head" {{ ($role ?? '') === 'section_head' ? 'selected' : '' }}>Section Head</option>
                                                <option value="operator" {{ ($role ?? '') === 'operator' ? 'selected' : '' }}>Operator</option>
                                            </select>
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm" style="background:transparent;color:var(--primary);border:1px solid var(--primary); st-filter-clear" data-filter="role">Clear</button>
                                            </div>
                                        </div>
                                        <div class="st-sort-panel" data-sort-panel="role" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Sort Role</div>
                                            <button type="button" class="st-sort-option" data-sort="role" data-dir="asc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;margin-bottom:2px;">
                                                A-Z
                                            </button>
                                            <button type="button" class="st-sort-option" data-sort="role" data-dir="desc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;">
                                                Z-A
                                            </button>
                                        </div>
                                    </div>
                                </th>
                                <th style="width:120px;">
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Status</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="is_active" data-type="text" title="Sort">⇅</button>
                                            <button type="button" class="st-colhead__icon st-filter-trigger" data-filter="is_active" title="Filter">⏷</button>
                                        </span>
                                        <div class="st-filter-panel" data-filter-panel="is_active" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:240px;max-height:220px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Status Filter</div>
                                            <select name="is_active" form="user-filter-form" class="st-select">
                                                <option value="">All Status</option>
                                                <option value="1" {{ ($is_active ?? '') === '1' ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ ($is_active ?? '') === '0' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                            <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                                                <button type="button" class="st-btn st-btn--sm" style="background:transparent;color:var(--primary);border:1px solid var(--primary); st-filter-clear" data-filter="is_active">Clear</button>
                                            </div>
                                        </div>
                                        <div class="st-sort-panel" data-sort-panel="is_active" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Sort Status</div>
                                            <button type="button" class="st-sort-option" data-sort="is_active" data-dir="asc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;margin-bottom:2px;">
                                                A-Z
                                            </button>
                                            <button type="button" class="st-sort-option" data-sort="is_active" data-dir="desc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;">
                                                Z-A
                                            </button>
                                        </div>
                                    </div>
                                </th>
                                <th style="width:190px;">
                                    <div class="st-colhead">
                                        <span class="st-colhead__label">Created</span>
                                        <span class="st-colhead__icons">
                                            <button type="button" class="st-colhead__icon st-sort-trigger" data-sort="created_at" data-type="date" title="Sort">⇅</button>
                                        </span>
                                        <div class="st-sort-panel" data-sort-panel="created_at" style="display:none;position:absolute;top:100%;left:0;margin-top:4px;z-index:20;background:#ffffff;border:1px solid #e5e7eb;border-radius:6px;padding:8px;min-width:200px;box-shadow:0 8px 16px rgba(15,23,42,0.12);font-size:12px;">
                                            <div style="font-weight:600;margin-bottom:6px;">Sort Created</div>
                                            <button type="button" class="st-sort-option" data-sort="created_at" data-dir="desc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;margin-bottom:2px;">
                                                Newest
                                            </button>
                                            <button type="button" class="st-sort-option" data-sort="created_at" data-dir="asc" style="display:block;width:100%;text-align:left;padding:6px 8px;border:none;background:none;cursor:pointer;border-radius:4px;">
                                                Oldest
                                            </button>
                                        </div>
                                    </div>
                                </th>
                                <th style="width:260px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($users as $u)
                            @php
                                $isActive = (int)($u->is_active ?? 0) === 1;
                                $isCurrentUser = auth()->check() && (int)(auth()->user()->id ?? 0) === (int)$u->id;
                                $toggleLabel = $isActive ? 'Deactivate' : 'Activate';
                                $roleText = (string) ($u->role_name ?? '');
                                $roleVal = $roleText !== '' ? strtolower(str_replace(' ', '_', $roleText)) : (string) ($u->role ?? 'operator');
                                $roleText = $roleText !== '' ? $roleText : ($roleVal === 'admin' ? 'Admin' : ($roleVal === 'section_head' ? 'Section Head' : 'Operator'));
                                $toggleConfirmMsg = 'Are you sure you want to ' . strtolower($toggleLabel) . ' this user?';
                                $deleteConfirmMsg = 'Are you sure you want to delete this user?';
                            @endphp
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td style="font-weight:600;">{{ $u->nik ?? '-' }}</td>
                                <td>{{ $u->full_name ?? '-' }}</td>
                                <td>
                                    <span class="st-badge st-badge--{{ $roleVal }}">{{ $roleText }}</span>
                                </td>
                                <td>
                                    @if ($isActive)
                                        <span class="st-badge st-badge--active">Active</span>
                                    @else
                                        <span class="st-badge st-badge--inactive">Inactive</span>
                                    @endif
                                </td>
                                <td>
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
                                <td>
                                    <div class="tw-actionbar">
                                        <a href="{{ route('users.edit', ['userId' => $u->id]) }}" class="tw-action" data-tooltip="Edit" aria-label="Edit">
                                            <i class="fa-solid fa-pencil"></i>
                                        </a>

                                        @if (! $isCurrentUser)
                                            <form method="POST" action="{{ route('users.toggle', ['userId' => $u->id]) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="tw-action {{ $isActive ? 'tw-action--danger' : 'tw-action--primary' }}" data-tooltip="{{ $toggleLabel }}" aria-label="{{ $toggleLabel }}" onclick="return confirm('{{ $toggleConfirmMsg }}');">
                                                    <i class="fa-solid {{ $isActive ? 'fa-ban' : 'fa-check' }}"></i>
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('users.delete', ['userId' => $u->id]) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="tw-action tw-action--danger" data-tooltip="Delete" aria-label="Delete" onclick="return confirm('{{ $deleteConfirmMsg }}');">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span style="font-size:12px;color:#6b7280;">(current)</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align:center;color:#6b7280;padding:16px 8px;">No users found</td>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form on input change
    const userFilterForm = document.getElementById('user-filter-form');
    if (userFilterForm) {
        // Auto-submit on select change
        userFilterForm.addEventListener('change', function(e) {
            if (e.target.tagName === 'SELECT') {
                userFilterForm.submit();
            }
        });

        // Auto-submit on input with debounce for text inputs
        const textInputs = userFilterForm.querySelectorAll('input[type="text"]');
        textInputs.forEach(function(input) {
            let timeout;
            input.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    userFilterForm.submit();
                }, 500); // 500ms debounce
            });

            // Submit on Enter key
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    clearTimeout(timeout);
                    userFilterForm.submit();
                }
            });
        });
    }
});
</script>
@endpush
