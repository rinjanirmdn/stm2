<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            $method = strtoupper((string) $request->getMethod());
            if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                return $response;
            }

            $userId = Auth::id();
            if (!$userId) {
                // Allow guest logging for forgot-password request by resolving user id from login
                $routeNameGuest = (string) ($request->route()?->getName() ?? '');
                if ($method === 'POST' && in_array($routeNameGuest, ['forgot-password.send', 'login.store'], true)) {
                    $login = trim((string) $request->input('login', $request->input('email', '')));
                    if ($login !== '') {
                        try {
                            $user = DB::table('md_users')
                                ->where('email', $login)
                                ->orWhere('username', $login)
                                ->orWhere('nik', $login)
                                ->select(['id'])
                                ->first();
                            $userId = $user ? (int) $user->id : null;
                        } catch (\Throwable $e) {
                            $userId = null;
                        }
                    }
                }

                if (!$userId) {
                    return $response;
                }
            }

            // Avoid logging activity log listing/filter actions themselves
            $routeName = (string) ($request->route()?->getName() ?? '');
            if ($routeName !== '' && str_starts_with($routeName, 'logs.')) {
                return $response;
            }

            $status = (int) $response->getStatusCode();
            if ($status < 200 || $status >= 400) {
                return $response;
            }

            $path = '/' . ltrim((string) $request->path(), '/');

            $routeParams = [];
            try {
                $routeParams = (array) ($request->route()?->parameters() ?? []);
            } catch (\Throwable $e) {
                $routeParams = [];
            }

            $payload = [];
            try {
                $payload = (array) $request->except([
                    '_token',
                    '_method',
                    'password',
                    'password_confirmation',
                    'current_password',
                ]);
            } catch (\Throwable $e) {
                $payload = [];
            }

            $location = '';
            try {
                $location = (string) ($response->headers->get('Location') ?? '');
            } catch (\Throwable $e) {
                $location = '';
            }

            $description = '';
            $targetId = null;
            foreach (['userId', 'id', 'slotId', 'slot_id', 'gateId'] as $key) {
                if (isset($routeParams[$key]) && is_numeric($routeParams[$key])) {
                    $targetId = (int) $routeParams[$key];
                    break;
                }
            }

            $templates = [
                'users.store' => 'User created',
                'users.update' => 'User updated',
                'users.delete' => 'User deleted',
                'users.toggle' => 'User status updated',
                'forgot-password.send' => 'Password reset requested',
                'profile.password-request' => 'Password reset requested',
                'gates.toggle' => 'Gate status updated',
                'slots.store' => 'Slot created',
                'slots.update' => 'Slot updated',
                'slots.delete' => 'Slot deleted',
                'unplanned.store' => 'Unplanned slot created',
                'unplanned.update' => 'Unplanned slot updated',
                'unplanned.delete' => 'Unplanned slot deleted',
                'bookings.approve' => 'Booking approved',
                'bookings.reject' => 'Booking rejected',
                'vendor.bookings.store' => 'Booking created',
                'vendor.bookings.cancel' => 'Booking cancelled',
                'login.store' => 'User login',
                'logout' => 'User logout',
            ];

            if ($routeName !== '' && array_key_exists($routeName, $templates)) {
                $description = $templates[$routeName];
            }

            $extractTrailingId = static function (string $value): ?int {
                if ($value === '') {
                    return null;
                }
                $path = parse_url($value, PHP_URL_PATH);
                $path = is_string($path) ? $path : $value;
                if (preg_match('/(\d+)(?:\/)?$/', $path, $m) !== 1) {
                    return null;
                }
                return (int) $m[1];
            };

            $getString = static function (array $arr, string $key): string {
                if (!array_key_exists($key, $arr)) {
                    return '';
                }
                $v = $arr[$key];
                if (is_array($v) || is_object($v)) {
                    return '';
                }
                return trim((string) $v);
            };

            if ($method === 'POST') {
                if ($routeName === 'users.store') {
                    $nik = $getString($payload, 'nik');
                    $email = $getString($payload, 'email');
                    $name = $getString($payload, 'name');

                    $parts = [];
                    if ($nik !== '') {
                        $parts[] = 'NIK ' . $nik;
                    }
                    if ($email !== '') {
                        $parts[] = $email;
                    }
                    if ($name !== '') {
                        $parts[] = $name;
                    }
                    if (!empty($parts)) {
                        $description = 'User created (' . implode(' - ', $parts) . ')';
                    }
                } elseif ($routeName === 'slots.store') {
                    $po = $getString($payload, 'po_number');
                    if ($po !== '') {
                        $description = 'Slot created (PO/DO ' . $po . ')';
                    }
                } elseif ($routeName === 'unplanned.store') {
                    $po = $getString($payload, 'po_number');
                    if ($po !== '') {
                        $description = 'Unplanned slot created (PO/DO ' . $po . ')';
                    }
                } elseif ($routeName === 'vendor.bookings.store') {
                    $po = $getString($payload, 'po_number');
                    $createdId = $extractTrailingId($location);
                    if ($po !== '' && $createdId) {
                        $description = 'Booking created (Request #' . $createdId . ' - PO/DO ' . $po . ')';
                    } elseif ($createdId) {
                        $description = 'Booking created (Request #' . $createdId . ')';
                    } elseif ($po !== '') {
                        $description = 'Booking created (PO/DO ' . $po . ')';
                    }
                } elseif ($routeName === 'login.store') {
                    $login = trim((string) $request->input('login', $request->input('email', '')));
                    if ($login !== '') {
                        $description = 'User login (' . $login . ')';
                    }
                }
            }

            if ($description === '') {
                $action = '';
                if ($method === 'POST') {
                    $action = 'created';
                } elseif (in_array($method, ['PUT', 'PATCH'], true)) {
                    $action = 'updated';
                } elseif ($method === 'DELETE') {
                    $action = 'deleted';
                } else {
                    $action = 'changed';
                }

                $entity = '';
                if ($routeName !== '') {
                    $parts = explode('.', $routeName);
                    $entity = trim((string) ($parts[0] ?? ''));
                }
                if ($entity !== '') {
                    $entity = str_replace(['-', '_'], ' ', $entity);
                    if (strlen($entity) > 1 && substr($entity, -1) === 's') {
                        $entity = substr($entity, 0, -1);
                    }
                    $entity = ucwords($entity);
                    $description = $entity . ' ' . $action;
                } else {
                    $description = 'Data ' . $action;
                }

                $detailKeys = [
                    'ticket_number',
                    'request_number',
                    'po_number',
                    'mat_doc',
                    'vendor_name',
                    'name',
                    'email',
                    'nik',
                    'gate_number',
                ];
                $details = [];
                foreach ($detailKeys as $k) {
                    $v = $getString($payload, $k);
                    if ($v === '') {
                        continue;
                    }
                    $label = ucwords(str_replace('_', ' ', $k));
                    $details[] = $label . ' ' . $v;
                    if (count($details) >= 2) {
                        break;
                    }
                }
                if (!empty($details)) {
                    $description .= ' (' . implode(' - ', $details) . ')';
                }
            }

            if ($targetId !== null) {
                $description .= ' (ID: ' . $targetId . ')';
            }
            if (strlen($description) > 1900) {
                $description = substr($description, 0, 1900) . '...';
            }

            $slotId = null;
            foreach (['slotId', 'slot_id', 'id'] as $key) {
                if (isset($routeParams[$key]) && is_numeric($routeParams[$key])) {
                    $slotId = (int) $routeParams[$key];
                    break;
                }
            }

            // Cache column list once per process to avoid ~10 DB queries per request
            static $columns = null;
            if ($columns === null) {
                try {
                    $columns = Schema::getColumnListing('activity_logs');
                } catch (\Throwable $e) {
                    $columns = [];
                }
            }

            $has = static fn(string $col): bool => in_array($col, $columns, true);

            $insert = [];

            $logType = 'crud';
            if (in_array($routeName, ['login.store', 'logout'], true)) {
                $logType = 'auth';
            }

            if ($has('activity_type')) {
                $insert['activity_type'] = $logType;
            } elseif ($has('type')) {
                $insert['type'] = $logType;
            }

            if ($has('description')) {
                $insert['description'] = $description;
            }

            if ($has('slot_id')) {
                $insert['slot_id'] = $slotId;
            }

            if ($has('created_by')) {
                $insert['created_by'] = (int) $userId;
            } elseif ($has('user_id')) {
                $insert['user_id'] = (int) $userId;
            }

            if ($has('mat_doc')) {
                $insert['mat_doc'] = $getString($payload, 'mat_doc') ?: null;
            }

            if ($has('po_number')) {
                $insert['po_number'] = $getString($payload, 'po_number') ?: null;
            }

            if (($has('mat_doc') || $has('po_number')) && $slotId !== null && ($insert['mat_doc'] ?? null) === null && ($insert['po_number'] ?? null) === null) {
                try {
                    $slotRow = DB::table('slots')->where('id', $slotId)->select(['mat_doc', 'po_number'])->first();
                    if ($slotRow) {
                        if ($has('mat_doc') && ($insert['mat_doc'] ?? null) === null) {
                            $insert['mat_doc'] = $slotRow->mat_doc ?? null;
                        }
                        if ($has('po_number') && ($insert['po_number'] ?? null) === null) {
                            $insert['po_number'] = $slotRow->po_number ?? null;
                        }
                    }
                } catch (\Throwable $e) {
                    // no-op
                }
            }

            if ($has('old_value')) {
                $insert['old_value'] = null;
            }

            if ($has('new_value')) {
                $insert['new_value'] = null;
            }

            if ($has('created_at')) {
                $insert['created_at'] = now();
            }

            if ($has('updated_at')) {
                $insert['updated_at'] = now();
            }

            // Insert only if minimum fields exist
            if (!empty($insert) && isset($insert['description'])) {
                DB::table('activity_logs')->insert($insert);
            }
        } catch (\Throwable $e) {
            // swallow - logging must never break user flow
        }

        return $response;
    }
}
