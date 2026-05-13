<?php

namespace App\Http\Controllers;

use App\Http\Requests\TruckTypeDurationStoreRequest;
use App\Http\Requests\TruckTypeDurationUpdateRequest;
use App\Models\TruckTypeDuration;
use App\Services\SlotService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TruckTypeDurationController extends Controller
{
    public function __construct(
        private readonly SlotService $slotService
    ) {}

    public function index(Request $request)
    {
        $pageSizeAllowed = ['10', '25', '50', 'all'];

        $rows = TruckTypeDuration::orderByDesc('created_at')
            ->orderByDesc('id_truck')
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
        try {
            $validated = $request->validated();
            $truckType = trim($validated['truck_type']);
            $targetMinutes = $validated['target_duration_minutes'];

            // Check if truck type already exists (including soft-deleted)
            $existing = TruckTypeDuration::withTrashed()->where('truck_type', $truckType)->first();

            if ($existing) {
                if ($existing->trashed()) {
                    // Restore soft-deleted record and update it
                    $existing->restore();
                    $existing->update([
                        'target_duration_minutes' => $targetMinutes,
                    ]);

                    $this->slotService->logActivity(
                        null,
                        'insert',
                        "Restored Truck Type: {$truckType}",
                        null,
                        $existing->toArray(),
                        feature: 'Truck Type'
                    );

                    return redirect()->route('trucks.index')
                        ->with('success', "Truck Type '{$truckType}' was previously deleted and has been restored with the new duration.");
                }

                return back()->withInput()->with('error', "Truck Type '{$truckType}' already exists.");
            }

            // Create new record
            $maxId = TruckTypeDuration::withTrashed()->max('id_truck') ?? 0;
            $nextId = $maxId + 1;

            $newRecord = TruckTypeDuration::create([
                'id_truck' => $nextId,
                'truck_type' => $truckType,
                'target_duration_minutes' => $targetMinutes,
            ]);

            $this->slotService->logActivity(
                null,
                'insert',
                "Created Truck Type: {$truckType}",
                null,
                $newRecord->toArray(),
                feature: 'Truck Type'
            );

            return redirect()->route('trucks.index')->with('success', 'Truck Type duration created successfully');
        } catch (UniqueConstraintViolationException $e) {
            return back()->withInput()->with('error', 'Gagal menambahkan data: Tipe Truk tersebut sudah ada dalam sistem (mungkin sudah dihapus sebelumnya). Silakan gunakan fitur pencarian atau hubungi administrator.');
        } catch (\Throwable $e) {
            Log::error('TruckTypeDuration Store Error: '.$e->getMessage());

            return back()->withInput()->with('error', 'An unexpected error occurred. Please try again.');
        }
    }

    public function edit(Request $request, int $truckTypeDurationId)
    {
        $row = TruckTypeDuration::find($truckTypeDurationId);
        if (! $row) {
            return redirect()->route('trucks.index')->with('error', 'Truck Type duration not found');
        }

        return view('trucks.edit', [
            'row' => $row,
        ]);
    }

    public function update(TruckTypeDurationUpdateRequest $request, int $truckTypeDurationId)
    {
        $row = TruckTypeDuration::find($truckTypeDurationId);
        if (! $row) {
            return redirect()->route('trucks.index')->with('error', 'Truck Type duration not found');
        }

        try {
            $validated = $request->validated();
            $oldData = $row->toArray();

            $row->update([
                'truck_type' => trim($validated['truck_type']),
                'target_duration_minutes' => $validated['target_duration_minutes'],
            ]);

            $this->slotService->logActivity(
                null,
                'update',
                "Updated Truck Type: {$row->truck_type}",
                $oldData,
                $row->toArray(),
                feature: 'Truck Type'
            );

            return redirect()->route('trucks.index')->with('success', 'Truck Type duration updated successfully');
        } catch (UniqueConstraintViolationException $e) {
            return back()->withInput()->with('error', 'Gagal memperbarui data: Nama Tipe Truk sudah digunakan oleh data lain.');
        }
    }

    public function destroy(Request $request, int $truckTypeDurationId)
    {
        $row = TruckTypeDuration::find($truckTypeDurationId);
        if (! $row) {
            return redirect()->route('trucks.index')->with('error', 'Truck Type duration not found');
        }

        $oldData = $row->toArray();
        $type = $row->truck_type;
        $row->delete();

        $this->slotService->logActivity(
            null,
            'delete',
            "Deleted Truck Type: {$type}",
            $oldData,
            null,
            feature: 'Truck Type'
        );

        return redirect()->route('trucks.index')->with('success', 'Truck Type duration deleted successfully');
    }
}
