<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VendorTransporterController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->query('q', ''));
        $status = trim($request->query('status', ''));
        $pageSize = in_array($request->query('page_size'), ['10', '25', '50', '100'], true)
            ? $request->query('page_size')
            : '25';

        $query = DB::table('md_vendor_transporters')->orderBy('name');

        if ($search !== '') {
            $query->where('name', 'ilike', "%{$search}%");
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $transporters = $pageSize === 'all' ? $query->get() : $query->paginate((int) $pageSize)->appends($request->query());

        return view('master.transporters.index', compact('transporters', 'search', 'status', 'pageSize'));
    }

    public function create()
    {
        return view('master.transporters.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:md_vendor_transporters,name',
            'is_active' => 'nullable|boolean',
        ]);

        DB::table('md_vendor_transporters')->insert([
            'name' => trim($request->input('name', '')),
            'is_active' => (bool) $request->input('is_active', true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('master.transporters.index')->with('success', 'Vendor Transporter berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $transporter = DB::table('md_vendor_transporters')->where('id', $id)->first();
        if (! $transporter) {
            return redirect()->route('master.transporters.index')->with('error', 'Data tidak ditemukan.');
        }

        return view('master.transporters.edit', compact('transporter'));
    }

    public function update(Request $request, int $id)
    {
        $transporter = DB::table('md_vendor_transporters')->where('id', $id)->first();
        if (! $transporter) {
            return redirect()->route('master.transporters.index')->with('error', 'Data tidak ditemukan.');
        }

        $request->validate([
            'name' => "required|string|max:255|unique:md_vendor_transporters,name,{$id}",
            'is_active' => 'nullable|boolean',
        ]);

        DB::table('md_vendor_transporters')->where('id', $id)->update([
            'name' => trim($request->input('name', '')),
            'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : true,
            'updated_at' => now(),
        ]);

        return redirect()->route('master.transporters.index')->with('success', 'Vendor Transporter berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $transporter = DB::table('md_vendor_transporters')->where('id', $id)->first();
        if (! $transporter) {
            return redirect()->route('master.transporters.index')->with('error', 'Data tidak ditemukan.');
        }

        // Check if it's used in slots
        $usedCount = DB::table('slots')->where('vendor_transporter_id', $id)->count();
        if ($usedCount > 0) {
            return redirect()->route('master.transporters.index')->with('error', 'Data tidak dapat dihapus karena sedang digunakan dalam transaksi.');
        }

        DB::table('md_vendor_transporters')->where('id', $id)->delete();

        return redirect()->route('master.transporters.index')->with('success', 'Vendor Transporter berhasil dihapus.');
    }
}
