<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $primaryKey = 'alert_id';
    public $incrementing = true;
    protected $keyType = 'int';

    // Let Laravel manage created_at automatically (no updated_at needed)
    const UPDATED_AT = null;

    protected $fillable = [
        'reading_id',
        'alert_type',
        'severity',
        'message',
        'acknowledged',
    ];

    protected $casts = [
        'acknowledged' => 'boolean',
    ];

    public function sensorReading(): BelongsTo
    {
        return $this->belongsTo(SensorReading::class, 'reading_id', 'reading_id');
    }
}
