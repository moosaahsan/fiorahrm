<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    protected static function booted()
    {
        static::saved(function () {
            Cache::forget('app_settings');
            app_settings(); // reload cache immediately
        });

        static::deleted(function () {
            Cache::forget('app_settings');
        });
    }
}
