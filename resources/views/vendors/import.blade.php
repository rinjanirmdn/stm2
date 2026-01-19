@extends('layouts.app')

@section('title', 'Import Vendors - Slot Time Management')
@section('page_title', 'Import Vendors')

@section('content')
    <section class="st-row">
        <div class="st-col-12">
            <div class="st-card" style="padding:16px;max-width:860px;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
                    <div>
                        <h2 class="st-card__title" style="margin:0 0 6px 0;">Import Vendors</h2>
                        <div class="st-card__subtitle">CSV format: Code, Name (header optional)</div>
                    </div>
                    <a href="{{ route('vendors.index') }}" class="st-btn st-btn--secondary">Kembali</a>
                </div>

                <div class="st-text--small st-text--muted" style="margin-top:12px;">
                    <div style="font-weight:600;margin-bottom:6px;">Format file (CSV)</div>
                    <div>Kolom 1: <strong>Code</strong></div>
                    <div>Kolom 2: <strong>Name</strong></div>
                    <div style="margin-top:6px;">Baris pertama boleh header (Code, Name) atau langsung data.</div>
                </div>

                <form method="POST" action="{{ route('vendors.import.store') }}" enctype="multipart/form-data" style="margin-top:14px;">
                    @csrf

                    <div class="st-form-field" style="margin-bottom:10px;max-width:260px;">
                        <label class="st-label">Tipe Vendor</label>
                        <select name="vendor_type" class="st-select" required>
                            <option value="supplier">Supplier</option>
                            <option value="customer">Customer</option>
                        </select>
                    </div>

                    <div class="st-form-field" style="margin-bottom:14px;max-width:520px;">
                        <label class="st-label">File CSV</label>
                        <input type="file" name="file" class="st-input" accept=".csv" required>
                    </div>

                    <button type="submit" class="st-btn st-btn--primary">Import</button>
                </form>
            </div>
        </div>
    </section>
@endsection
