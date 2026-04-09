<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MdBpController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->query('q', ''));
        $type = trim($request->query('type', ''));
        $status = trim($request->query('status', ''));
        $pageSize = in_array($request->query('page_size'), ['10', '25', '50', '100'], true)
            ? $request->query('page_size')
            : '25';

        $query = DB::table('md_bp')->orderBy('bp_type')->orderBy('bp_name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('bp_code', 'ilike', "%{$search}%")
                    ->orWhere('bp_name', 'ilike', "%{$search}%")
                    ->orWhere('city', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if (in_array($type, ['vendor', 'customer'], true)) {
            $query->where('bp_type', $type);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $bps = $pageSize === 'all' ? $query->get() : $query->paginate((int) $pageSize)->appends($request->query());

        return view('md_bp.index', compact('bps', 'search', 'type', 'status', 'pageSize'));
    }

    public function create()
    {
        return view('md_bp.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'bp_code' => 'required|string|max:20|unique:md_bp,bp_code',
            'bp_name' => 'required|string|max:200',
            'bp_type' => 'required|in:vendor,customer',
            'npwp' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'pic_name' => 'nullable|string|max:100',
            'pic_phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        DB::table('md_bp')->insert([
            'bp_code' => strtoupper(trim($request->input('bp_code', ''))),
            'bp_name' => trim($request->input('bp_name', '')),
            'bp_type' => $request->input('bp_type', 'vendor'),
            'npwp' => trim($request->input('npwp', '')) ?: null,
            'address' => trim($request->input('address', '')) ?: null,
            'city' => trim($request->input('city', '')) ?: null,
            'phone' => trim($request->input('phone', '')) ?: null,
            'email' => trim($request->input('email', '')) ?: null,
            'pic_name' => trim($request->input('pic_name', '')) ?: null,
            'pic_phone' => trim($request->input('pic_phone', '')) ?: null,
            'is_active' => (bool) $request->input('is_active', true),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('md_bp.index')->with('success', 'Business Partner berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        $bp = DB::table('md_bp')->where('id', $id)->first();
        if (! $bp) {
            return redirect()->route('md_bp.index')->with('error', 'Data tidak ditemukan.');
        }

        return view('md_bp.edit', compact('bp'));
    }

    public function update(Request $request, int $id)
    {
        $bp = DB::table('md_bp')->where('id', $id)->first();
        if (! $bp) {
            return redirect()->route('md_bp.index')->with('error', 'Data tidak ditemukan.');
        }

        $request->validate([
            'bp_code' => "required|string|max:20|unique:md_bp,bp_code,{$id}",
            'bp_name' => 'required|string|max:200',
            'bp_type' => 'required|in:vendor,customer',
            'npwp' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'pic_name' => 'nullable|string|max:100',
            'pic_phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        DB::table('md_bp')->where('id', $id)->update([
            'bp_code' => strtoupper(trim($request->input('bp_code', ''))),
            'bp_name' => trim($request->input('bp_name', '')),
            'bp_type' => $request->input('bp_type', 'vendor'),
            'npwp' => trim($request->input('npwp', '')) ?: null,
            'address' => trim($request->input('address', '')) ?: null,
            'city' => trim($request->input('city', '')) ?: null,
            'phone' => trim($request->input('phone', '')) ?: null,
            'email' => trim($request->input('email', '')) ?: null,
            'pic_name' => trim($request->input('pic_name', '')) ?: null,
            'pic_phone' => trim($request->input('pic_phone', '')) ?: null,
            'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : true,
            'updated_by' => Auth::id(),
            'updated_at' => now(),
        ]);

        return redirect()->route('md_bp.index')->with('success', 'Business Partner berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        $bp = DB::table('md_bp')->where('id', $id)->first();
        if (! $bp) {
            return redirect()->route('md_bp.index')->with('error', 'Data tidak ditemukan.');
        }

        DB::table('md_bp')->where('id', $id)->delete();

        return redirect()->route('md_bp.index')->with('success', 'Business Partner berhasil dihapus.');
    }

    /**
     * AJAX endpoint — returns BP list as JSON for search/select dropdowns.
     */
    public function ajaxSearch(Request $request)
    {
        $q = trim($request->query('q', ''));
        $type = trim($request->query('type', ''));

        $query = DB::table('md_bp')
            ->where('is_active', true)
            ->orderBy('bp_name')
            ->select(['id', 'bp_code', 'bp_name', 'bp_type', 'city']);

        if ($q !== '') {
            $query->where(function ($sq) use ($q) {
                $sq->where('bp_code', 'ilike', "%{$q}%")
                    ->orWhere('bp_name', 'ilike', "%{$q}%");
            });
        }

        if (in_array($type, ['vendor', 'customer'], true)) {
            $query->where('bp_type', $type);
        }

        $results = $query->limit(30)->get();

        return response()->json(['data' => $results]);
    }
}
