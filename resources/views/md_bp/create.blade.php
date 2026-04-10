@extends('layouts.app')

@section('title', 'Tambah Business Partner - e-Docking Control System')
@section('page_title', 'Tambah Business Partner')

@section('content')
    <div class="st-card">
        <form method="POST" action="{{ route('md_bp.store') }}">
            @csrf

            @if ($errors->any())
                <div class="st-alert st-alert--error st-alert--autodismiss">
                    <span class="st-alert__icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <div class="st-alert__text">
                        <div class="st-font-semibold st-mb-2">Validation Error</div>
                        <div class="st-text--sm">
                            <ul class="st-ml-16">
                                @foreach ($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <button type="button" class="st-alert__close" onclick="this.parentElement.remove()">&times;</button>
                </div>
            @endif

            <div class="st-form-row st-form-field--mb-12 st-form-row--grid-3">
                <div class="st-form-field">
                    <label class="st-label">Kode BP <span class="st-text--danger-dark">*</span></label>
                    <input type="text" name="bp_code"
                           class="st-input{{ $errors->has('bp_code') ? ' st-input--invalid' : '' }}"
                           value="{{ old('bp_code') }}" placeholder="Contoh: V001, C001" required maxlength="20"
                           style="text-transform:uppercase">
                    <div class="st-text--xs st-text--muted st-mt-1">Kode unik, akan otomatis dijadikan huruf kapital.</div>
                    @error('bp_code')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Nama <span class="st-text--danger-dark">*</span></label>
                    <input type="text" name="bp_name"
                           class="st-input{{ $errors->has('bp_name') ? ' st-input--invalid' : '' }}"
                           value="{{ old('bp_name') }}" placeholder="Nama vendor / customer" required maxlength="200">
                    @error('bp_name')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Tipe <span class="st-text--danger-dark">*</span></label>
                    <select name="bp_type" class="st-select{{ $errors->has('bp_type') ? ' st-input--invalid' : '' }}" required>
                        <option value="">Pilih Tipe...</option>
                        <option value="vendor"   {{ old('bp_type') === 'vendor'   ? 'selected' : '' }}>Vendor</option>
                        <option value="customer" {{ old('bp_type') === 'customer' ? 'selected' : '' }}>Customer</option>
                    </select>
                    @error('bp_type')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12 st-form-row--grid-3">
                <div class="st-form-field">
                    <label class="st-label">NPWP</label>
                    <input type="text" name="npwp"
                           class="st-input{{ $errors->has('npwp') ? ' st-input--invalid' : '' }}"
                           value="{{ old('npwp') }}" placeholder="XX.XXX.XXX.X-XXX.XXX" maxlength="30">
                    @error('npwp')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Kota</label>
                    <input type="text" name="city"
                           class="st-input{{ $errors->has('city') ? ' st-input--invalid' : '' }}"
                           value="{{ old('city') }}" placeholder="Jakarta, Surabaya..." maxlength="100">
                    @error('city')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Telepon</label>
                    <input type="text" name="phone"
                           class="st-input{{ $errors->has('phone') ? ' st-input--invalid' : '' }}"
                           value="{{ old('phone') }}" placeholder="021-xxxxxxxx" maxlength="50">
                    @error('phone')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12 st-form-row--grid-3">
                <div class="st-form-field">
                    <label class="st-label">Email</label>
                    <input type="email" name="email"
                           class="st-input{{ $errors->has('email') ? ' st-input--invalid' : '' }}"
                           value="{{ old('email') }}" placeholder="email@company.com" maxlength="150">
                    @error('email')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">Nama PIC</label>
                    <input type="text" name="pic_name"
                           class="st-input{{ $errors->has('pic_name') ? ' st-input--invalid' : '' }}"
                           value="{{ old('pic_name') }}" placeholder="Nama person in charge" maxlength="100">
                    @error('pic_name')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="st-form-field">
                    <label class="st-label">No HP PIC</label>
                    <input type="text" name="pic_phone"
                           class="st-input{{ $errors->has('pic_phone') ? ' st-input--invalid' : '' }}"
                           value="{{ old('pic_phone') }}" placeholder="08xxxxxxxxxx" maxlength="50">
                    @error('pic_phone')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12 st-form-row--grid-1">
                <div class="st-form-field">
                    <label class="st-label">Alamat</label>
                    <input type="text" name="address"
                           class="st-input{{ $errors->has('address') ? ' st-input--invalid' : '' }}"
                           value="{{ old('address') }}" placeholder="Jl. ..." maxlength="500">
                    @error('address')
                        <div class="st-text--small st-text--danger st-mt-1">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="st-form-row st-form-field--mb-12 st-form-row--grid-1">
                <div class="st-form-field">
                    <label class="st-label st-flex st-gap-8 st-items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                        <span>Aktif</span>
                    </label>
                </div>
            </div>

            <div class="st-form-actions st-mt-4">
                <button type="submit" class="st-btn">Simpan</button>
                <a href="{{ route('md_bp.index') }}" class="st-btn st-btn--outline-primary">Batal</a>
            </div>
        </form>
    </div>
@endsection
