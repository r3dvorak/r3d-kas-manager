<?php
/**
 * R3D KAS Manager – RecipeAction Model
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.24.1
 * @date      2025-10-09
 * @license   MIT License
 *
 * Represents a single actionable step inside a Recipe.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecipeAction extends Model
{
    // allow mass-assignment from services/seeders; control via service layer
    protected $guarded = [];

    protected $casts = [
        'parameters' => 'array',
    ];

    /**
     * The recipe this action belongs to.
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * History entries created when this action was executed.
     */
    public function history(): HasMany
    {
        return $this->hasMany(RecipeActionHistory::class, 'recipe_action_id');
    }
}
