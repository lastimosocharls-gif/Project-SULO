<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigesterStatus extends Model
{
    protected $table = 'digester_status';
    protected $primaryKey = 'status_id';
    public $incrementing = true;
    protected $keyType = 'int';

    // Use default Laravel timestamps (updated_at only, no created_at column)
    const CREATED_AT = null;

    protected $fillable = [
        'reading_id',
        'valve_status',
        'overall_status',
    ];

    public function sensorReading(): BelongsTo
    {
        return $this->belongsTo(SensorReading::class, 'reading_id', 'reading_id');
    }
}
