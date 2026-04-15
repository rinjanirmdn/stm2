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
        $tempPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$fileName;

        $content = Excel::raw(new OfflineTemplateExport(), \Maatwebsite\Excel\Excel::XLSX);
        file_put_contents($tempPath, $content);

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
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
