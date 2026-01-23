<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return $setting->value;
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Remove a setting by key
     */
    public static function remove(string $key): void
    {
        static::where('key', $key)->delete();
    }

    /**
     * Check if a setting exists
     */
    public static function has(string $key): bool
    {
        return static::where('key', $key)->exists();
    }
}
