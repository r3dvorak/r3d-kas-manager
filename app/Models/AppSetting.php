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

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public $timestamps = false;

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
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}