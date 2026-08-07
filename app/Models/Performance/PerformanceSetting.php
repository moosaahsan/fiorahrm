<?php

namespace App\Models\Performance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceSetting extends Model
{
    use HasFactory;

    protected $table = 'performance_settings';

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];

    /**
     * Helper to get a setting value or default fallback.
     */
    public static function getVal(string $key, float $default = 0.0): float
    {
        $setting = self::where('key', $key)->first();
        return $setting ? (float)$setting->value : $default;
    }
}
