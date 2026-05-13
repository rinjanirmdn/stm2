<?php

namespace App\Http\Middleware;

use App\Jobs\LogActivityJob;
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
        // Capture old values BEFORE the request is processed (for edit/update tracking)
        $oldRowData = null;
        try {
            $method = strtoupper((string) $request->getMethod());
            if (in_array($method, ['PUT', 'PATCH', 'DELETE'], true)) {
                $oldRowData = $this->captureOldValues($request);
            }
        } catch (\Throwable $e) {
            // swallow
        }

        $response = $next($request);

        try {
            $method = strtoupper((string) $request->getMethod());
            if (! in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                return $response;
            }

            $userId = Auth::id();
            if (! $userId) {
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
                                ->select(['id_users'])
                                ->first();
                            $userId = $user ? (int) $user->id_users : null;
                        } catch (\Throwable $e) {
                            $userId = null;
                        }
                    }
                }

                if (! $userId) {
                    return $response;
                }
            }

            // Skip routes that should NOT be auto-logged:
            // - Routes already manually logged in their controllers (slots lifecycle, unplanned, gates, security)
            // - System/internal routes (ajax, livewire generated, debugbar, broadcasting, notifications)
            // - Activity log page itself
            $routeName = (string) ($request->route()?->getName() ?? '');

            // Skip generated/system routes (Livewire, debugbar, broadcasting)
            if ($routeName === '' || str_starts_with($routeName, 'generated::') || str_starts_with($routeName, 'debugbar.') || $routeName === 'broadcasting.auth') {
                return $response;
            }

            // Skip ajax, logs, notifications, and routes with manual logging in controllers
            $skipPrefixes = ['logs.', 'notifications.'];
            foreach ($skipPrefixes as $prefix) {
                if (str_starts_with($routeName, $prefix)) {
                    return $response;
                }
            }
            if (str_contains($routeName, '.ajax.')) {
                return $response;
            }

            // Routes that already have their own manual activity logging in controllers
            $manuallyLoggedRoutes = [
                'security.scan',
                'security.confirm_arrival',
                'slots.arrival.store',     // SlotLifecycleController logs arrival
                'slots.start.store',       // SlotLifecycleController logs start
                'slots.complete.store',    // SlotLifecycleController logs completion
                'slots.cancel.store',      // SlotController logs cancellation
                'unplanned.complete.store', // SlotLifecycleController logs unplanned complete
                'unplanned.start.store',   // SlotLifecycleController logs unplanned start
                'gates.toggle',            // ReportController logs gate activation/deactivation
            ];
            if (in_array($routeName, $manuallyLoggedRoutes, true)) {
                return $response;
            }

            $status = (int) $response->getStatusCode();
            if ($status < 200 || $status >= 400) {
                return $response;
            }

            $path = '/'.ltrim((string) $request->path(), '/');

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
                // Auth
                'login.store' => 'User logged in',
                'logout' => 'User logged out',
                'forgot-password.send' => 'Password reset requested',
                'profile.password-request' => 'Password reset requested from profile',
                'password.force-change.store' => 'Password changed',
                'profile.update' => 'Profile updated',

                // User management
                'users.store' => 'User account created',
                'users.update' => 'User account updated',
                'users.delete' => 'User account deleted',
                'users.toggle' => 'User account activated/deactivated',

                // Planned slots
                'slots.store' => 'Scheduled slot created',
                'slots.update' => 'Scheduled slot updated',
                'slots.delete' => 'Scheduled slot deleted',

                // Unplanned slots
                'unplanned.store' => 'Unplanned slot created',
                'unplanned.update' => 'Unplanned slot updated',
                'unplanned.delete' => 'Unplanned slot deleted',

                // Booking requests (vendor)
                'bookings.approve' => 'Booking request approved',
                'bookings.reject' => 'Booking request rejected',
                'bookings.reschedule.store' => 'Booking request rescheduled',
                'vendor.bookings.store' => 'Booking request submitted',
                'vendor.bookings.cancel' => 'Booking request cancelled',

                // Truck types
                'trucks.store' => 'Truck type added',
                'trucks.update' => 'Truck type updated',
                'trucks.delete' => 'Truck type deleted',
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
                if (! array_key_exists($key, $arr)) {
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
                        $parts[] = 'NIK '.$nik;
                    }
                    if ($email !== '') {
                        $parts[] = $email;
                    }
                    if ($name !== '') {
                        $parts[] = $name;
                    }
                    if (! empty($parts)) {
                        $description = 'User account created ('.implode(' - ', $parts).')';
                    }
                } elseif ($routeName === 'slots.store') {
                    $po = $getString($payload, 'po_number');
                    $vendor = $getString($payload, 'vendor_name');
                    $detail = array_filter([$vendor, $po !== '' ? 'PO/SO '.$po : '']);
                    if (! empty($detail)) {
                        $description = 'Scheduled slot created ('.implode(' - ', $detail).')';
                    }
                } elseif ($routeName === 'unplanned.store') {
                    $po = $getString($payload, 'po_number');
                    $vendor = $getString($payload, 'vendor_name');
                    $detail = array_filter([$vendor, $po !== '' ? 'PO/SO '.$po : '']);
                    if (! empty($detail)) {
                        $description = 'Unplanned slot created ('.implode(' - ', $detail).')';
                    }
                } elseif ($routeName === 'vendor.bookings.store') {
                    $po = $getString($payload, 'po_number');
                    $createdId = $extractTrailingId($location);
                    if ($po !== '' && $createdId) {
                        $description = 'Booking request submitted (Request #'.$createdId.' - PO/SO '.$po.')';
                    } elseif ($createdId) {
                        $description = 'Booking request submitted (Request #'.$createdId.')';
                    } elseif ($po !== '') {
                        $description = 'Booking request submitted (PO/SO '.$po.')';
                    }
                } elseif ($routeName === 'login.store') {
                    $login = trim((string) $request->input('login', $request->input('email', '')));
                    if ($login !== '') {
                        $description = 'User logged in ('.$login.')';
                    }
                }
            }

            // If no template matched, this is an unknown/unmapped route.
            // Skip it rather than generating a vague/confusing description.
            if ($description === '') {
                return $response;
            }

            // Append useful identifiers only if description doesn't already contain them
            if ($targetId !== null && strpos($description, '(') === false) {
                // Try to find a meaningful identifier from payload instead of raw ID
                $identifierAdded = false;
                $identifierKeys = ['ticket_number', 'po_number', 'name', 'email', 'nik'];
                foreach ($identifierKeys as $k) {
                    $v = $getString($payload, $k);
                    if ($v !== '') {
                        $label = ucwords(str_replace('_', ' ', $k));
                        $description .= ' ('.$label.': '.$v.')';
                        $identifierAdded = true;
                        break;
                    }
                }
            }
            if (strlen($description) > 1900) {
                $description = substr($description, 0, 1900).'...';
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

            $has = static fn (string $col): bool => in_array($col, $columns, true);

            $insert = [];

            [$logType, $logFeature] = $this->resolveActivityTypeAndFeature($routeName, $method);

            if ($has('activity_type')) {
                $insert['activity_type'] = $logType;
            } elseif ($has('type')) {
                $insert['type'] = $logType;
            }

            if ($has('feature')) {
                $insert['feature'] = $logFeature;
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

            if ($has('sj_no')) {
                $insert['sj_no'] = $getString($payload, 'sj_no') ?: ($getString($payload, 'mat_doc') ?: null);
            }

            if ($has('po_number')) {
                $insert['po_number'] = $getString($payload, 'po_number') ?: null;
            }

            if ($has('old_value')) {
                $insert['old_value'] = null;
                if ($oldRowData !== null && !empty($oldRowData)) {
                    $insert['old_value'] = json_encode($oldRowData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }

            if ($has('new_value')) {
                $insert['new_value'] = null;
                if (in_array($logType, ['insert', 'update'], true) && !empty($payload)) {
                    $newData = array_filter($payload, fn ($k) => !in_array($k, ['_token', '_method', 'password', 'password_confirmation', 'current_password'], true), ARRAY_FILTER_USE_KEY);
                    if (!empty($newData)) {
                        $insert['new_value'] = json_encode($newData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                }
            }

            if ($has('created_at')) {
                $insert['created_at'] = now();
            }

            if ($has('updated_at')) {
                $insert['updated_at'] = now();
            }

            // Dispatch to queue for async insert (removes ~10-30ms latency per request)
            if (! empty($insert) && isset($insert['description'])) {
                try {
                    LogActivityJob::dispatch($insert, $slotId);
                } catch (\Throwable $e) {
                    // Fallback: sync insert if queue dispatch fails
                    try {
                        DB::table('activity_logs')->insert($insert);
                    } catch (\Throwable $e2) {
                        // swallow
                    }
                }
            }
        } catch (\Throwable $e) {
            // swallow - logging must never break user flow
        }

        return $response;
    }

    /**
     * Resolve the activity type and feature module based on route name and HTTP method.
     * Returns [type, feature] where type is 'insert', 'update', 'delete', or 'auth'.
     *
     * @return array{0: string, 1: string}
     */
    private function resolveActivityTypeAndFeature(string $routeName, string $method): array
    {
        // Route → [type, feature] mapping
        $routeMap = [
            // Auth
            'login.store'              => ['auth', 'Auth'],
            'logout'                   => ['auth', 'Auth'],
            'forgot-password.send'     => ['update', 'Auth'],
            'profile.password-request' => ['update', 'Auth'],
            'password.force-change.store' => ['update', 'Auth'],
            'profile.update'           => ['update', 'Profile'],

            // User management
            'users.store'              => ['insert', 'User Management'],
            'users.update'             => ['update', 'User Management'],
            'users.delete'             => ['delete', 'User Management'],
            'users.toggle'             => ['update', 'User Management'],

            // Planned slots
            'slots.store'              => ['insert', 'Planned'],
            'slots.update'             => ['update', 'Planned'],
            'slots.delete'             => ['delete', 'Planned'],

            // Unplanned slots
            'unplanned.store'          => ['insert', 'Unplanned'],
            'unplanned.update'         => ['update', 'Unplanned'],
            'unplanned.delete'         => ['delete', 'Unplanned'],

            // Booking requests
            'bookings.approve'         => ['update', 'Booking Requests'],
            'bookings.reject'          => ['update', 'Booking Requests'],
            'bookings.reschedule.store' => ['update', 'Booking Requests'],
            'vendor.bookings.store'    => ['insert', 'Booking Requests'],
            'vendor.bookings.cancel'   => ['update', 'Booking Requests'],

            // Truck types
            'trucks.store'             => ['insert', 'Truck Type'],
            'trucks.update'            => ['update', 'Truck Type'],
            'trucks.delete'            => ['delete', 'Truck Type'],
        ];

        if (isset($routeMap[$routeName])) {
            return $routeMap[$routeName];
        }

        // Fallback by HTTP method
        if ($method === 'DELETE') {
            return ['delete', 'System'];
        }

        return ['update', 'System'];
    }

    /**
     * Capture old row values before an update/delete operation for change tracking.
     */
    private function captureOldValues(Request $request): ?array
    {
        $routeName = (string) ($request->route()?->getName() ?? '');
        $routeParams = (array) ($request->route()?->parameters() ?? []);

        // Resolve table and row ID based on route
        $table = null;
        $rowId = null;

        // Slot update/delete
        if (str_starts_with($routeName, 'slots.') || str_starts_with($routeName, 'unplanned.')) {
            $table = 'slots';
            $rowId = $routeParams['slotId'] ?? $routeParams['slot_id'] ?? $routeParams['id'] ?? null;
        }
        // User update/delete/toggle
        elseif (str_starts_with($routeName, 'users.')) {
            $table = 'md_users';
            $rowId = $routeParams['userId'] ?? $routeParams['id'] ?? null;
        }
        // Truck type update/delete
        elseif (str_starts_with($routeName, 'trucks.')) {
            $table = 'md_truck';
            $rowId = $routeParams['id'] ?? $routeParams['truckId'] ?? null;
        }

        if ($table === null || $rowId === null || !is_numeric($rowId)) {
            return null;
        }

        try {
            $pkMap = [
                'slots' => 'id_slots',
                'md_users' => 'id_users',
                'md_truck' => 'id_truck',
                'md_warehouse' => 'id_wh',
                'md_gates' => 'id_gates',
                'booking_requests' => 'id_booking_requests',
                'md_roles' => 'id_roles',
                'md_permissions' => 'id_permission',
                'md_vendor_transporters' => 'id_vendor_transporters',
                'activity_logs' => 'id_activity_logs',
                'notifications' => 'id_notifications'
            ];
            $pk = $pkMap[$table] ?? 'id';
            $row = DB::table($table)->where($pk, (int) $rowId)->first();
            if (!$row) {
                return null;
            }

            $data = (array) $row;

            // Remove sensitive fields
            unset($data['password'], $data['remember_token']);

            return $data;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
