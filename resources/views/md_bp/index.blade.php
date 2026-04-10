@extends('layouts.app')

@section('title', 'Master Data BP - e-Docking Control System')
@section('page_title', 'Master Data Business Partner')
@section('body_class', 'st-page--md-bp')

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
            <form method="GET" id="md-bp-filter-form" action="{{ route('md_bp.index') }}" autocomplete="off"
                  class="st-form-row st-gap-4 st-align-end">
                <div class="st-form-field st-maxw-260">
                    <label class="st-label">Search</label>
                    <input type="text" name="q" class="st-input" placeholder="Kode / Nama / Kota / Email"
                           value="{{ $search }}">
                </div>
                <div class="st-form-field st-maxw-160">
                    <label class="st-label">Tipe</label>
                    <select name="type" class="st-select">
                        <option value="">Semua</option>
                        <option value="vendor"   {{ $type === 'vendor'   ? 'selected' : '' }}>Vendor</option>
                        <option value="customer" {{ $type === 'customer' ? 'selected' : '' }}>Customer</option>
                    </select>
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
                    <a href="{{ route('md_bp.index') }}" class="st-btn st-btn--outline-primary">Reset</a>
                    <button type="submit" class="st-btn st-btn--outline-primary">Filter</button>
                    <a href="{{ route('md_bp.create') }}" class="st-btn st-btn--primary">+ Tambah BP</a>
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
                                <th>Kode BP</th>
                                <th>Nama</th>
                                <th>Tipe</th>
                                <th>Kota</th>
                                <th>Telepon</th>
                                <th>Email</th>
                                <th>PIC</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @php $list = $bps instanceof \Illuminate\Pagination\LengthAwarePaginator ? $bps->items() : $bps->all(); @endphp
                        @forelse ($list as $i => $bp)
                            <tr class="st-table-row">
                                <td class="st-table-cell">{{ $i + 1 }}</td>
                                <td class="st-table-cell"><strong>{{ $bp->bp_code }}</strong></td>
                                <td class="st-table-cell">{{ $bp->bp_name }}</td>
                                <td class="st-table-cell st-td-center">
                                    @if ($bp->bp_type === 'vendor')
                                        <span class="st-badge-modern st-badge-modern--inbound">Vendor</span>
                                    @else
                                        <span class="st-badge-modern st-badge-modern--outbound">Customer</span>
                                    @endif
                                </td>
                                <td class="st-table-cell">{{ $bp->city ?? '-' }}</td>
                                <td class="st-table-cell">{{ $bp->phone ?? '-' }}</td>
                                <td class="st-table-cell">{{ $bp->email ?? '-' }}</td>
                                <td class="st-table-cell">
                                    {{ $bp->pic_name ?? '-' }}
                                    @if(!empty($bp->pic_phone))
                                        <div class="st-text--xs st-text--muted">{{ $bp->pic_phone }}</div>
                                    @endif
                                </td>
                                <td class="st-table-cell st-td-center">
                                    @if ($bp->is_active)
                                        <span class="st-table__status-badge st-status-on-time">Aktif</span>
                                    @else
                                        <span class="st-table__status-badge st-status-late">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="st-table-cell st-td-center">
                                    <div class="st-action-dropdown">
                                        <button type="button" class="st-btn st-btn--ghost st-action-trigger">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="st-action-menu">
                                            <a href="{{ route('md_bp.edit', $bp->id) }}" class="st-action-item">Edit</a>
                                            <form method="POST" action="{{ route('md_bp.destroy', $bp->id) }}"
                                                  onsubmit="return confirm('Hapus BP {{ addslashes($bp->bp_name) }}?')" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="st-action-item st-action-item--danger" style="width:100%;text-align:left;">Hapus</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="st-table-empty st-text-center st-text--muted st-py-16">
                                    Belum ada data Business Partner.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($bps instanceof \Illuminate\Pagination\LengthAwarePaginator && $bps->hasPages())
                    <div class="st-p-12">
                        {{ $bps->links() }}
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
