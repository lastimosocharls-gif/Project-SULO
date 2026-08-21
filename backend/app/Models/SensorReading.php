<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SensorReading extends Model
{
    protected $primaryKey = 'reading_id';
    public $incrementing = true;
    protected $keyType = 'int';

    // recorded_at is set manually, but created_at/updated_at are auto-managed by Laravel
    // The migration includes timestamps() which creates both columns
    protected $fillable = [
        'temperature',
        'ph',
        'gas_level',
        'pressure',
        'flow_rate',
        'status',
        'valve_open',
        'recorded_at',
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'ph'          => 'decimal:2',
        'gas_level'   => 'decimal:2',
        'pressure'    => 'decimal:2',
        'flow_rate'   => 'decimal:4',
        'valve_open'  => 'boolean',
        'recorded_at' => 'datetime',
    ];

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class, 'reading_id', 'reading_id');
    }

    public function digesterStatus(): HasOne
    {
        return $this->hasOne(DigesterStatus::class, 'reading_id', 'reading_id');
    }

    /**
     * Status label accessor
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            0 => 'Normal',
            1 => 'Warning',
            2 => 'Critical',
            default => 'Unknown',
        };
    }
}
