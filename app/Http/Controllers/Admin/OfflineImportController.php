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

        // Ensure exports directory exists
        $exportsDir = public_path('exports');
        if (! file_exists($exportsDir)) {
            mkdir($exportsDir, 0755, true);
        }

        // Generate the file directly into public/exports/ so Apache serves it as a static file
        $publicPath = $exportsDir.'/'.$fileName;
        $export = new OfflineTemplateExport();
        $writer = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        file_put_contents($publicPath, $writer);

        // Return JSON with the static download URL
        return response()->json([
            'download_url' => '/exports/'.$fileName,
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            Excel::import(new OfflineTxImport(), $request->file('file'));

            return response()->json(['success' => true, 'message' => 'Offline data successfully imported.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to import data: '.$e->getMessage()], 500);
        }
    }
}
