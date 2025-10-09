<?php
/**
 * R3D KAS Manager – RecipeRun Model
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.24.1
 * @date      2025-10-09
 * @license   MIT License
 *
 * Represents one execution instance of a Recipe (logging context).
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecipeRun extends Model
{
    protected $guarded = [];

    protected $casts = [
        'variables'   => 'array',
        'result'      => 'array',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * The recipe this run belongs to.
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * The user who initiated this run (nullable).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * All history entries produced during this run.
     */
    public function history(): HasMany
    {
        return $this->hasMany(RecipeActionHistory::class, 'recipe_run_id');
    }
}
