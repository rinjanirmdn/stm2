@extends('layouts.app')

@section('title', 'Master Vendor Transporter - e-Docking Control System')
@section('page_title', 'Master Vendor Transporter')

@section('content')

    @if (session('success'))
        <div class="st-alert st-alert--success st-alert--autodismiss">
            <span class="st-alert__icon"><i class="fa-solid fa-circle-check"></i></span>
            <div class="st-alert__text">{{ session('success') }}</div>
            <button type="button" class="st-alert__close" onclick="this.parentElement.remove()">&times;</button>
        </div>
    @endif
    @if (session('error'))
        <div class="st-alert st-alert--error st-alert--autodismiss">
            <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
            <div class="st-alert__text">{{ session('error') }}</div>
            <button type="button" class="st-alert__close" onclick="this.parentElement.remove()">&times;</button>
        </div>
    @endif

    {{-- Filter bar --}}
    <div class="st-card st-mb-12">
        <div class="st-p-12">
            <form method="GET" action="{{ route('master.transporters.index') }}" autocomplete="off" class="st-form-row st-gap-4 st-align-end">
                <div class="st-form-field st-maxw-260">
                    <label class="st-label">Search</label>
                    <input type="text" name="q" class="st-input" placeholder="Nama Transporter..." value="{{ $search }}">
                </div>
                <div class="st-form-field st-maxw-160">
                    <label class="st-label">Status</label>
                    <select name="status" class="st-select">
                        <option value="">Semua</option>
                        <option value="active"   {{ $status === 'active'   ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
                <div class="st-form-field st-maxw-120">
                    <label class="st-label">Show</label>
                    <select name="page_size" class="st-select">
                        @foreach (['10','25','50','100'] as $ps)
                            <option value="{{ $ps }}" {{ $pageSize === $ps ? 'selected' : '' }}>{{ $ps }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="st-form-field st-minw-80 st-flex st-flex-0 st-justify-end st-gap-8">
                    <a href="{{ route('master.transporters.index') }}" class="st-btn st-btn--outline-primary">Reset</a>
                    <button type="submit" class="st-btn st-btn--outline-primary">Filter</button>
                    <a href="{{ route('master.transporters.create') }}" class="st-btn st-btn--primary">+ Tambah</a>
                </div>
            </form>
        </div>
    </div>

    <section class="st-row st-flex-1 st-minh-0">
        <div class="st-col-12 st-flex-1 st-flex st-flex-col st-minh-0">
            <div class="st-card st-mb-0 st-flex st-flex-col st-flex-1 st-minh-0">
                <div class="st-table-wrapper st-table-wrapper--minh-400 st-flex-1 st-maxh-none st-minh-0">
                    <table class="st-table">
                        <thead>
                            <tr>
                                <th class="st-table-col-40">#</th>
                                <th>Nama Transporter</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @php $list = $transporters instanceof \Illuminate\Pagination\LengthAwarePaginator ? $transporters->items() : $transporters->all(); @endphp
                        @forelse ($list as $i => $t)
                            <tr class="st-table-row">
                                <td class="st-table-cell">{{ $i + 1 }}</td>
                                <td class="st-table-cell"><strong>{{ $t->name }}</strong></td>
                                <td class="st-table-cell">
                                    @if ($t->is_active)
                                        <span class="st-table__status-badge st-status-on-time">Aktif</span>
                                    @else
                                        <span class="st-table__status-badge st-status-late">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="st-table-cell">
                                    <div class="st-action-dropdown">
                                        <button type="button" class="st-btn st-btn--ghost st-action-trigger">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="st-action-menu">
                                            <a href="{{ route('master.transporters.edit', $t->id) }}" class="st-action-item">Edit</a>
                                            <form method="POST" action="{{ route('master.transporters.destroy', $t->id) }}"
                                                  onsubmit="return confirm('Hapus Transporter {{ addslashes($t->name) }}?')" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="st-action-item st-action-item--danger" style="width:100%;text-align:left;">Hapus</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="st-table-empty st-text-center st-text--muted st-py-16">
                                    Belum ada data Vendor Transporter.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($transporters instanceof \Illuminate\Pagination\LengthAwarePaginator && $transporters->hasPages())
                    <div class="st-p-12">
                        {{ $transporters->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
