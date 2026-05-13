<?php
 
namespace App\Http\Controllers\Master;
 
use App\Http\Controllers\Controller;
use App\Models\VendorTransporter;
use App\Services\SlotService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\UniqueConstraintViolationException;
 
class VendorTransporterController extends Controller
{
    public function __construct(
        private readonly SlotService $slotService
    ) {}
 
    public function index(Request $request)
    {
        $pageSizeAllowed = ['10', '25', '50', 'all'];
 
        $transporters = VendorTransporter::orderBy('name')->get();
 
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
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('md_vendor_transporters', 'name')->whereNull('deleted_at'),
            ],
            'is_active' => 'nullable|boolean',
        ]);
 
        try {
            $name = trim($request->input('name', ''));
            $isActive = (bool) $request->input('is_active', true);
 
            // Handle soft-deleted duplicates
            $existing = VendorTransporter::withTrashed()->where('name', $name)->first();
            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                    $existing->update(['is_active' => $isActive]);
 
                    $this->slotService->logActivity(
                        null,
                        'insert',
                        "Restored Vendor Transporter: {$name}",
                        null,
                        $existing->toArray(),
                        feature: 'Vendor Transporters'
                    );
 
                    return redirect()->route('master.transporters.index')
                        ->with('success', "Vendor Transporter '{$name}' was restored from deleted records.");
                }
                return back()->withInput()->with('error', "Vendor Transporter '{$name}' already exists.");
            }
 
            $transporter = VendorTransporter::create([
                'name' => $name,
                'is_active' => $isActive,
            ]);
 
            $this->slotService->logActivity(
                null,
                'insert',
                "Created Vendor Transporter: {$name}",
                null,
                $transporter->toArray(),
                feature: 'Vendor Transporters'
            );
 
            return redirect()->route('master.transporters.index')->with('success', 'Vendor Transporter created successfully.');
        } catch (UniqueConstraintViolationException $e) {
            return back()->withInput()->with('error', 'Gagal: Data ini sudah ada (mungkin sudah dihapus). Silakan periksa kembali.');
        }
    }
 
    public function edit(int $id)
    {
        $transporter = VendorTransporter::find($id);
        if (! $transporter) {
            return redirect()->route('master.transporters.index')->with('error', 'Data not found.');
        }
 
        return view('master.transporters.edit', compact('transporter'));
    }
 
    public function update(Request $request, int $id)
    {
        $transporter = VendorTransporter::find($id);
        if (! $transporter) {
            return redirect()->route('master.transporters.index')->with('error', 'Data not found.');
        }
 
        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('md_vendor_transporters', 'name')->ignore($id, 'id_vendor_transporters')->whereNull('deleted_at'),
            ],
            'is_active' => 'nullable|boolean',
        ]);
 
        try {
            $oldData = $transporter->toArray();
            $transporter->update([
                'name' => trim($request->input('name', '')),
                'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : true,
            ]);
 
            $this->slotService->logActivity(
                null,
                'update',
                "Updated Vendor Transporter: {$transporter->name}",
                $oldData,
                $transporter->toArray(),
                feature: 'Vendor Transporters'
            );
 
            return redirect()->route('master.transporters.index')->with('success', 'Vendor Transporter updated successfully.');
        } catch (UniqueConstraintViolationException $e) {
            return back()->withInput()->with('error', 'Gagal: Nama transporter ini sudah digunakan oleh data lain.');
        }
    }
 
    public function destroy(int $id)
    {
        $transporter = VendorTransporter::find($id);
        if (! $transporter) {
            return redirect()->route('master.transporters.index')->with('error', 'Data not found.');
        }
 
        $oldData = $transporter->toArray();
        $name = $transporter->name;
        $transporter->delete();
 
        $this->slotService->logActivity(
            null,
            'delete',
            "Deleted Vendor Transporter: {$name}",
            $oldData,
            null,
            feature: 'Vendor Transporters'
        );
 
        return redirect()->route('master.transporters.index')->with('success', 'Vendor Transporter deleted successfully.');
    }
}
