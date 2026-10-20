<?php

namespace App\Http\Controllers;

use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

use App\Models\Box;
use App\Models\Alert;

abstract class Controller
{
    protected function getBoxesQueryForUser($user): Builder
    {
        return Box::whereNull('deleted_at');
    }

    protected function getAlertsQueryForUser($user): Builder
    {
        return Alert::query();
    }

    protected function syncNotifications(): void
    {
        // 1. Get all active alerts
        $activeAlerts = Alert::whereNull('resolved_at')->get();
        $activeAlertIds = $activeAlerts->pluck('id')->toArray();

        // 2. Build map of boxes and locations
        $boxes = Box::whereNull('deleted_at')->get()->keyBy('id');
        $locations = \App\Models\Location::whereNull('deleted_at')->get()->keyBy('id');

        // 3. Sync active alerts to notifications
        foreach ($activeAlerts as $alert) {
            $box = $boxes->get($alert->box_id);
            if (!$box) {
                continue;
            }
            $location = $box->location_id ? $locations->get($box->location_id) : null;
            $locationText = $location ? " - {$location->name}" : "";
            
            $title = $alert->status;
            $body = "{$alert->drug_name} ในกล่อง " . ($box->name ?? $box->id) . $locationText;

            // Find or create notification
            $notification = \App\Models\Notification::find($alert->id);
            if ($notification) {
                $notification->title = $title;
                $notification->body = $body;
                $notification->severity = $alert->severity;
                $notification->target_view = 'alerts';
                $notification->save();
            } else {
                $notification = new \App\Models\Notification();
                $notification->id = $alert->id;
                $notification->title = $title;
                $notification->body = $body;
                $notification->severity = $alert->severity;
                $notification->target_view = 'alerts';
                $notification->read_by = json_encode([]);
                $notification->created_at = $alert->created_at ?? now();
                $notification->save();
            }
        }

        // 4. Delete notifications that are no longer active and have not been read by anyone
        $allNotifications = \App\Models\Notification::where('target_view', 'alerts')->get();
        foreach ($allNotifications as $notification) {
            if (in_array($notification->id, $activeAlertIds)) {
                continue;
            }
            
            $readBy = $notification->read_by;
            if (is_string($readBy)) {
                $decoded = json_decode($readBy, true);
                $readBy = is_array($decoded) ? $decoded : [];
            } else {
                $readBy = is_array($readBy) ? $readBy : [];
            }
            
            if (empty($readBy)) {
                $notification->delete();
            }
        }
    }

    protected function hasSoftDeleteColumn(string $modelClass): bool
    {
        return in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass));
    }

    protected function activeQuery(string $modelClass): Builder
    {
        /** @var Builder $query */
        $query = $modelClass::query();

        if ($this->hasSoftDeleteColumn($modelClass)) {
            $query->whereNull('deleted_at');
        }

        return $query;
    }

    protected function softDeleteIfSupported(Model $model): bool
    {
        if (! Schema::hasColumn($model->getTable(), 'deleted_at')) {
            return false;
        }

        $model->deleted_at = now();
        $model->save();

        return true;
    }

    protected function setIfColumnExists(Model $model, string $column, mixed $value): void
    {
        if (Schema::hasColumn($model->getTable(), $column)) {
            $model->{$column} = $value;
        }
    }

    protected function redactSensitiveData(array $data): array
    {
        $sensitivePattern = '/token|secret|password|chat[_-]?id|api[_-]?key/i';

        $redact = function (array $items) use (&$redact, $sensitivePattern): array {
            $result = [];

            foreach ($items as $key => $value) {
                if (is_array($value)) {
                    $result[$key] = $redact($value);
                    continue;
                }

                if (is_string($key) && preg_match($sensitivePattern, $key)) {
                    $result[$key] = '[redacted]';
                    continue;
                }

                $result[$key] = $value;
            }

            return $result;
        };

        return $redact($data);
    }

    protected function recordAudit(
        ?string $actorUserId,
        string $action,
        string $entityType,
        ?string $entityId = null,
        array $details = []
    ): void {
        AuditLogger::record($actorUserId, $action, $entityType, $entityId, $details);
    }
}
