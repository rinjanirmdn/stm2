<?php

namespace App\Http\Controllers\Admin;

use App\Exports\OfflineTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\OfflineTxImport;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        try {
            $request->validate([
                'file' => 'required|mimes:xlsx,xls,csv|max:10240',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed: '.implode(', ', $e->errors())], 422);
        }

        try {
            $import = new OfflineTxImport();
            Excel::import($import, $request->file('file'));

            $successCount = $import->getSuccessCount();
            $errorCount = $import->getErrorCount();
            $errors = $import->getErrors();

            if ($successCount > 0) {
                $message = "Berhasil mengimpor {$successCount} data.";
                if ($errorCount > 0) {
                    $message .= " {$errorCount} data gagal diimpor.";
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                    'errors' => $errors,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data yang berhasil diimpor. '.($errorCount > 0 ? implode('; ', $errors) : ''),
                    'errors' => $errors,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to import data: '.$e->getMessage()], 500);
        }
    }
}
