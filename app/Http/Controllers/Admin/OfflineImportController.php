<?php

namespace App\Http\Controllers\Admin;

use App\Exports\OfflineTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\OfflineTxImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class OfflineImportController extends Controller
{
    public function downloadTemplate()
    {
        $fileName = 'offline_import_template.xlsx';
        $tempPath = storage_path('app/' . $fileName);

        Excel::store(new OfflineTemplateExport(), $fileName, 'local', \Maatwebsite\Excel\Excel::XLSX);

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ])->deleteFileAfterSend(true);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new OfflineTxImport(), $request->file('file'));

            return response()->json(['success' => true, 'message' => 'Data offline berhasil diimpor.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengimpor data: '.$e->getMessage()], 500);
        }
    }
}
