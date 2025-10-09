<?php
/**
 * R3D KAS Manager – Recipe Action History Model
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.24.1
 * @date      2025-10-09
 * @license   MIT License
 *
 * Recipe action history.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeActionHistory extends Model
{
    protected $table = 'recipe_action_history';

    // mass-assignable
    protected $guarded = [];

    protected $casts = [
        'request_payload'  => 'array',
        'response_payload' => 'array',
        'started_at'       => 'datetime',
        'finished_at'      => 'datetime',
    ];

    // Relations
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(RecipeRun::class, 'recipe_run_id');
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(RecipeAction::class, 'recipe_action_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\KasClient::class, 'kas_client_id');
    }
}
