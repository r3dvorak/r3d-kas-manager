<?php
/**
 * R3D KAS Manager – AppSetting Model
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.15.1-alpha
 * @date      2025-10-05
 * @license   MIT License
 */

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public $timestamps = false;

    // app_settings has no numeric auto-increment id; the key is the identifier.
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Get a setting by key, with optional default.
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set or update a setting by key.
     */
    public static function setValue(string $key, $value): void
    {
        DB::table('app_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => (string) $value]
        );
    }

    /**
     * Backwards-compatible shorthand used across the app.
     */
    public static function set(string $key, $value): void
    {
        static::setValue($key, $value);
    }

    /**
     * Backwards-compatible shorthand.
     */
    public static function get(string $key, $default = null)
    {
        return static::getValue($key, $default);
    }
}
