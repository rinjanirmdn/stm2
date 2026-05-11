<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            'early_arrival',
            'late_arrival',
            'gate_activation',
            'gate_deactivation',
            'auth',
            'crud',
        ];

        $allowedSorts = [
            'created_at' => 'al.created_at',
            'activity_type' => 'al.activity_type',
            'description' => 'al.description',
            'mat_doc' => 's.mat_doc',
            'po' => 's.po_number',
            'user' => DB::raw('COALESCE(u.full_name, u.name, u.nik, u.email)'),
        ];

        // Cache Schema introspection results per-process to avoid 6+ DB queries per request
        static $cachedSchema = null;
        if ($cachedSchema === null) {
            $cachedSchema = [
                'activityTypeCol' => Schema::hasColumn('activity_logs', 'activity_type') ? 'activity_type' : 'type',
                'createdByCol' => Schema::hasColumn('activity_logs', 'created_by') ? 'created_by' : 'user_id',
                'usersTable' => Schema::hasTable('md_users') ? 'md_users' : 'users',
            ];
            $cachedSchema['hasNik'] = Schema::hasColumn($cachedSchema['usersTable'], 'nik');
            $cachedSchema['hasFullName'] = Schema::hasColumn($cachedSchema['usersTable'], 'full_name');
            $cachedSchema['hasName'] = Schema::hasColumn($cachedSchema['usersTable'], 'name');
            $cachedSchema['hasEmail'] = Schema::hasColumn($cachedSchema['usersTable'], 'email');
            $cachedSchema['hasOldValue'] = Schema::hasColumn('activity_logs', 'old_value');
            $cachedSchema['hasNewValue'] = Schema::hasColumn('activity_logs', 'new_value');
        }

        $activityTypeCol = $cachedSchema['activityTypeCol'];
        $createdByCol = $cachedSchema['createdByCol'];
        $usersTable = $cachedSchema['usersTable'];
        $hasNik = $cachedSchema['hasNik'];
        $hasFullName = $cachedSchema['hasFullName'];
        $hasName = $cachedSchema['hasName'];
        $hasEmail = $cachedSchema['hasEmail'];

        $nameColParts = [];
        if ($hasFullName) {
            $nameColParts[] = 'u.full_name';
        }
        if ($hasName) {
            $nameColParts[] = 'u.name';
        }
        if ($hasNik) {
            $nameColParts[] = 'u.nik';
        }
        if ($hasEmail) {
            $nameColParts[] = 'u.email';
        }
        $userNameExpr = ! empty($nameColParts)
            ? DB::raw('COALESCE('.implode(', ', $nameColParts).')')
            : DB::raw('NULL');

        $allowedSorts['activity_type'] = 'al.'.$activityTypeCol;
        $allowedSorts['user'] = $userNameExpr;

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
            ->leftJoin($usersTable.' as u', 'al.'.$createdByCol, '=', 'u.id')
            ->leftJoin('slots as s', 'al.slot_id', '=', 's.id')
            ->select([
                'al.id',
                'al.slot_id',
                DB::raw('al.'.$activityTypeCol.' as activity_type'),
                'al.description',
                DB::raw('NULL as old_value'),
                DB::raw('NULL as new_value'),
                DB::raw('al.'.$createdByCol.' as created_by'),
                'al.created_at',
                DB::raw(($hasNik ? 'u.nik' : 'NULL').' as created_by_nik'),
                DB::raw("CASE WHEN u.is_internal_vendor = 1 AND u.vendor_code IS NOT NULL AND u.vendor_code != '' THEN CONCAT(".($hasFullName ? 'u.full_name' : ($hasName ? 'u.name' : "'User'")).", ' (', UPPER(u.vendor_code), ')') ELSE ".($hasFullName ? 'u.full_name' : ($hasName ? 'u.name' : ($hasEmail ? 'u.email' : 'NULL'))).' END as created_by_name'),
                DB::raw(($hasEmail ? 'u.email' : 'NULL').' as created_by_email'),
                's.mat_doc as slot_mat_doc',
                's.po_number as slot_po_number',
            ]);

        if ($cachedSchema['hasOldValue']) {
            $logsQ->addSelect('al.old_value');
        }
        if ($cachedSchema['hasNewValue']) {
            $logsQ->addSelect('al.new_value');
        }

        if ($q !== '') {
            $like = '%'.strtolower($q).'%';
            $logsQ->where(function ($sub) use ($like, $nameColParts) {
                $sub
                    ->whereRaw('LOWER(al.description) like ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(s.mat_doc, cast(\'\'  as varchar))) like ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(s.po_number, cast(\'\'  as varchar))) like ?', [$like]);
                if (! empty($nameColParts)) {
                    $coalesce = 'LOWER(COALESCE('.implode(', ', $nameColParts).')) like ?';
                    $sub->orWhereRaw($coalesce, [$like]);
                }
            });
        }

        if ($type !== '' && in_array($type, $allowedTypes, true)) {
            $logsQ->where('al.'.$activityTypeCol, $type);
        } else {
            $type = '';
        }

        if ($dateFrom !== '' && $dateTo !== '') {
            $logsQ->whereBetween('al.created_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59']);
        } elseif ($dateFrom !== '') {
            $logsQ->whereDate('al.created_at', '>=', $dateFrom);
        } elseif ($dateTo !== '') {
            $logsQ->whereDate('al.created_at', '<=', $dateTo);
        }

        // Per-column filters
        if ($fDesc !== '') {
            $logsQ->where('al.description', 'like', '%'.$fDesc.'%');
        }
        if ($fMatDoc !== '') {
            $logsQ->where('s.mat_doc', 'like', '%'.$fMatDoc.'%');
        }
        if ($fPo !== '') {
            $logsQ->where('s.po_number', 'like', '%'.$fPo.'%');
        }
        if ($fUser !== '') {
            $logsQ->where(function ($q) use ($fUser) {
                $q->where('u.full_name', 'like', '%'.$fUser.'%')
                    ->orWhere('u.name', 'like', '%'.$fUser.'%')
                    ->orWhere('u.nik', 'like', '%'.$fUser.'%')
                    ->orWhere('u.email', 'like', '%'.$fUser.'%');
            });
        }

        if (count($sorts) > 0) {
            foreach ($sorts as $i => $s) {
                $d = $dirs[$i] ?? 'desc';
                $col = $allowedSorts[$s];
                if ($col instanceof Expression) {
                    $logsQ->orderByRaw($col->getValue(DB::connection()->getQueryGrammar()).' '.strtoupper($d));
                } else {
                    $logsQ->orderBy($col, $d);
                }
            }
        } else {
            $logsQ->orderBy('al.created_at', 'desc');
        }

        $logsQ
            ->orderBy('al.id', ($dir === 'asc') ? 'asc' : 'desc')
            ->limit(200);

        $logsCacheKey = 'logs:index:data:'.sha1(json_encode([
            'uid' => Auth::id(),
            'query' => $request->query(),
            'version' => (string) Cache::get('st_realtime_version', '0'),
        ]));
        $logs = Cache::remember($logsCacheKey, now()->addSeconds(10), function () use ($logsQ) {
            return $logsQ->get();
        });

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
