<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorTransporterController extends Controller
{
    public function index(Request $request)
    {
        $pageSizeAllowed = ['10', '25', '50', 'all'];

        $transporters = DB::table('md_vendor_transporters')->orderBy('name')->get();

        return view('master.transporters.index', [
            'transporters' => $transporters,
            'pageSizeAllowed' => $pageSizeAllowed,
        ]);
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

        return redirect()->route('master.transporters.index')->with('success', 'Vendor Transporter created successfully.');
    }

    public function edit(int $id)
    {
        $transporter = DB::table('md_vendor_transporters')->where('id', $id)->first();
        if (! $transporter) {
            return redirect()->route('master.transporters.index')->with('error', 'Data not found.');
        }

        return view('master.transporters.edit', compact('transporter'));
    }

    public function update(Request $request, int $id)
    {
        $transporter = DB::table('md_vendor_transporters')->where('id', $id)->first();
        if (! $transporter) {
            return redirect()->route('master.transporters.index')->with('error', 'Data not found.');
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

        return redirect()->route('master.transporters.index')->with('success', 'Vendor Transporter updated successfully.');
    }

    public function destroy(int $id)
    {
        $transporter = DB::table('md_vendor_transporters')->where('id', $id)->first();
        if (! $transporter) {
            return redirect()->route('master.transporters.index')->with('error', 'Data not found.');
        }

        // Check if it's used in slots
        $usedCount = DB::table('slots')->where('vendor_transporter_id', $id)->count();
        if ($usedCount > 0) {
            return redirect()->route('master.transporters.index')->with('error', 'Data cannot be deleted because it is currently in use.');
        }

        DB::table('md_vendor_transporters')->where('id', $id)->delete();

        return redirect()->route('master.transporters.index')->with('success', 'Vendor Transporter deleted successfully.');
    }
}
