<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiSyncLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'items_processed' => 'integer',
            'duration_ms' => 'integer',
            'triggered_manually' => 'boolean',
        ];
    }
}
