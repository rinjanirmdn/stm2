<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $type = trim((string) $request->query('type', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        // Per-column filters
        $fDesc = trim((string) $request->query('desc', ''));
        $fMatDoc = trim((string) $request->query('mat_doc', ''));
        $fPo = trim((string) $request->query('po', ''));
        $fUser = trim((string) $request->query('user', ''));

        // Sorting (supports multi-sort via sort[]/dir[])
        $rawSort = $request->query('sort', []);
        $rawDir = $request->query('dir', []);

        $sorts = is_array($rawSort) ? $rawSort : [trim((string) $rawSort)];
        $dirs = is_array($rawDir) ? $rawDir : [trim((string) $rawDir)];

        $allowedTypes = [
            'status_change',
            'gate_activation',
            'gate_deactivation',
        ];

        $allowedSorts = [
            'created_at' => 'al.created_at',
            'activity_type' => 'al.activity_type',
            'description' => 'al.description',
            'mat_doc' => 's.mat_doc',
            'po' => 't.po_number',
            'user' => 'u.nik',
        ];

        $sorts = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $sorts), fn ($v) => $v !== ''));
        $dirs = array_values(array_map(function ($v) {
            $v = strtolower(trim((string) $v));
            return in_array($v, ['asc', 'desc'], true) ? $v : 'desc';
        }, $dirs));

        $validatedSorts = [];
        $validatedDirs = [];
        foreach ($sorts as $i => $s) {
            if (! array_key_exists($s, $allowedSorts)) {
                continue;
            }
            $validatedSorts[] = $s;
            $validatedDirs[] = $dirs[$i] ?? 'desc';
        }
        $sorts = $validatedSorts;
        $dirs = $validatedDirs;

        // Backward-compatible single sort/dir
        $sort = $sorts[0] ?? 'created_at';
        $dir = $dirs[0] ?? 'desc';

        $logsQ = DB::table('activity_logs as al')
            ->leftJoin('users as u', 'al.created_by', '=', 'u.id')
            ->leftJoin('slots as s', 'al.slot_id', '=', 's.id')
            ->leftJoin('po as t', 's.po_id', '=', 't.id')
            ->select([
                'al.id',
                'al.slot_id',
                'al.activity_type',
                'al.description',
                'al.old_value',
                'al.new_value',
                'al.created_by',
                'al.created_at',
                'u.nik as created_by_nik',
                's.mat_doc as slot_mat_doc',
                't.po_number as slot_po_number',
            ]);

        if ($q !== '') {
            $like = '%' . $q . '%';
            $logsQ->where(function ($sub) use ($like) {
                $sub
                    ->where('al.description', 'like', $like)
                    ->orWhere('s.mat_doc', 'like', $like)
                    ->orWhere('t.po_number', 'like', $like);
            });
        }

        if ($type !== '' && in_array($type, $allowedTypes, true)) {
            $logsQ->where('al.activity_type', $type);
        } else {
            $type = '';
        }

        if ($dateFrom !== '' && $dateTo !== '') {
            $logsQ->whereBetween('al.created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        } elseif ($dateFrom !== '') {
            $logsQ->whereDate('al.created_at', '>=', $dateFrom);
        } elseif ($dateTo !== '') {
            $logsQ->whereDate('al.created_at', '<=', $dateTo);
        }

        // Per-column filters
        if ($fDesc !== '') {
            $logsQ->where('al.description', 'like', '%' . $fDesc . '%');
        }
        if ($fMatDoc !== '') {
            $logsQ->where('s.mat_doc', 'like', '%' . $fMatDoc . '%');
        }
        if ($fPo !== '') {
            $logsQ->where('t.po_number', 'like', '%' . $fPo . '%');
        }
        if ($fUser !== '') {
            $logsQ->where('u.nik', 'like', '%' . $fUser . '%');
        }

        if (count($sorts) > 0) {
            foreach ($sorts as $i => $s) {
                $d = $dirs[$i] ?? 'desc';
                $logsQ->orderBy($allowedSorts[$s], $d);
            }
        } else {
            $logsQ->orderBy('al.created_at', 'desc');
        }

        $logs = $logsQ
            ->orderBy('al.id', ($dir === 'asc') ? 'asc' : 'desc')
            ->limit(200)
            ->get();

        return view('logs.index', [
            'logs' => $logs,
            'q' => $q,
            'type' => $type,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            // expose per-column and sorting state
            'f_desc' => $fDesc,
            'f_mat_doc' => $fMatDoc,
            'f_po' => $fPo,
            'f_user' => $fUser,
            'sort' => $sort,
            'dir' => $dir,
            'sorts' => $sorts,
            'dirs' => $dirs,
            'allowedTypes' => $allowedTypes,
        ]);
    }
}
