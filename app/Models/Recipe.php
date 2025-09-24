<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.1.0-alpha
 * @date      2025-09-24
 * 
 * @copyright   (C) 2025 Richard Dvořák, R3D Internet Dienstleistungen
 * @license   MIT License
 * 
 * Service to execute automation recipes (domains, mailboxes, DNS).
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    protected $fillable = ['name','description','is_template','created_from','version','variables'];

    protected $casts = [
        'variables' => 'array',
        'is_template' => 'boolean',
    ];

    public function actions()
    {
        return $this->hasMany(RecipeAction::class)->orderBy('order');
    }

    public function runs()
    {
        return $this->hasMany(RecipeRun::class);
    }
}
