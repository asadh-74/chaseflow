<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value with caching. Stored values may be JSON.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting:$key", function () use ($key, $default) {
            $row = static::query()->where('key', $key)->first();

            if (! $row) {
                return $default;
            }

            $decoded = json_decode($row->value, true);

            return json_last_error() === JSON_ERROR_NONE ? $decoded : $row->value;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => is_string($value) ? $value : json_encode($value)]);
        Cache::forget("setting:$key");
    }
}
