<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    protected static function bootAuditable()
    {
        static::created(function ($model) {
            $model->logActivity('created');
        });
        
        static::updated(function ($model) {
            $model->logActivity('updated');
        });
        
        static::deleted(function ($model) {
            $model->logActivity('deleted');
        });
    }
    
    public function logActivity($action)
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'table_name' => $this->getTable(),
            'record_id' => $this->id,
            'old_values' => $action === 'updated' ? $this->getOriginal() : null,
            'new_values' => $action !== 'deleted' ? $this->getAttributes() : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
    
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}