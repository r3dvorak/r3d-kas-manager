<?php
/**
 * R3D KAS Manager – Recipe Model
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.24.1
 * @date      2025-10-09
 * @license   MIT License
 *
 * Represents an automation recipe (blueprint) and provides relations to actions and runs.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recipe extends Model
{
    use SoftDeletes;

    // Allow mass-assignment during imports/seeders; control access at service layer.
    protected $guarded = [];

    protected $casts = [
        'variables'        => 'array',
        'is_template'      => 'boolean',
        'expected_runtime' => 'integer',
        'enable_ssl'       => 'boolean',
    ];

    // --- Defensive mutators for variables ---
    /**
     * Ensure variables are always stored as an array (avoid double-encoded JSON strings).
     *
     * @param  array|string|null  $value
     * @return void
     */
    public function setVariablesAttribute($value): void
    {
        if (is_null($value)) {
            $this->attributes['variables'] = null;
            return;
        }

        // If it's already an array, store as-is (Eloquent will JSON-encode)
        if (is_array($value)) {
            $this->attributes['variables'] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        // If it's a JSON string, try to decode
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->attributes['variables'] = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                return;
            }

            // Not valid JSON — try to interpret simple "key:val" pairs? No — fallback: store empty array
            // but to be safe, store original string under '_raw' key so we don't silently lose data
            $this->attributes['variables'] = json_encode(['_raw' => $value], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        // Fallback: cast to array then store
        $this->attributes['variables'] = json_encode((array) $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Ensure we always return an array (never a JSON string).
     *
     * @param  mixed  $value
     * @return array|null
     */
    public function getVariablesAttribute($value)
    {
        if (is_null($value)) return null;

        if (is_array($value)) return $value;

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) return $decoded;
            // If decoding fails, return as single raw entry
            return ['_raw' => $value];
        }

        return (array) $value;
    }

    /**
     * Ordered actions that belong to this recipe.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function actions(): HasMany
    {
        return $this->hasMany(RecipeAction::class)->orderBy('order');
    }

    /**
     * Execution runs of this recipe.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function runs(): HasMany
    {
        return $this->hasMany(RecipeRun::class);
    }

    /**
     * Optional default template referenced by this recipe (e.g. DNS template).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function defaultTemplate(): BelongsTo
    {
        return $this->belongsTo(KasTemplate::class, 'default_template_id');
    }

    /**
     * Self-reference: which recipe created this one (optional).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_from');
    }
}
