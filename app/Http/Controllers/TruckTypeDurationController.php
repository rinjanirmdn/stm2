<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\TruckTypeDurationStoreRequest;
use App\Http\Requests\TruckTypeDurationUpdateRequest;

class TruckTypeDurationController extends Controller
{
    public function index(Request $request)
    {
        $pageSizeAllowed = ['10', '25', '50', 'all'];

        $rows = DB::table('md_truck')
            ->select(['id', 'truck_type', 'target_duration_minutes', 'created_at'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return view('trucks.index', [
            'rows' => $rows,
            'pageSizeAllowed' => $pageSizeAllowed,
        ]);
    }

    public function create(Request $request)
    {
        return view('trucks.create');
    }

    public function store(TruckTypeDurationStoreRequest $request)
    {
        $validated = $request->validated();

        $truckType = trim($validated['truck_type']);
        $targetMinutes = $validated['target_duration_minutes'];

        // Get next ID manually to avoid PostgreSQL sequence issues
        $maxId = DB::table('md_truck')->max('id') ?? 0;
        $nextId = $maxId + 1;

        DB::table('md_truck')->insert([
            'id' => $nextId,
            'truck_type' => $truckType,
            'target_duration_minutes' => $targetMinutes,
        ]);

        return redirect()->route('trucks.index')->with('success', 'Truck Type duration created successfully');
    }

    public function edit(Request $request, int $truckTypeDurationId)
    {
        $row = DB::table('md_truck')->where('id', $truckTypeDurationId)->first();
        if (! $row) {
            return redirect()->route('trucks.index')->with('error', 'Truck Type duration not found');
        }

        return view('trucks.edit', [
            'row' => $row,
        ]);
    }

    public function update(TruckTypeDurationUpdateRequest $request, int $truckTypeDurationId)
    {
        $row = DB::table('md_truck')->where('id', $truckTypeDurationId)->first();
        if (! $row) {
            return redirect()->route('trucks.index')->with('error', 'Truck Type duration not found');
        }

        $validated = $request->validated();

        $truckType = trim($validated['truck_type']);
        $targetMinutes = $validated['target_duration_minutes'];

        DB::table('md_truck')->where('id', $truckTypeDurationId)->update([
            'truck_type' => $truckType,
            'target_duration_minutes' => $targetMinutes,
        ]);

        return redirect()->route('trucks.index')->with('success', 'Truck Type duration updated successfully');
    }

    public function destroy(Request $request, int $truckTypeDurationId)
    {
        $row = DB::table('md_truck')->where('id', $truckTypeDurationId)->first();
        if (! $row) {
            return redirect()->route('trucks.index')->with('error', 'Truck Type duration not found');
        }

        DB::table('md_truck')->where('id', $truckTypeDurationId)->delete();
        return redirect()->route('trucks.index')->with('success', 'Truck Type duration deleted successfully');
    }
}
