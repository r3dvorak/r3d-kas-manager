<?php
/**
 * R3D KAS Manager – Add soft deletes to recipes and ensure kas_templates.kas_client_id FK
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák | R3D Internet Dienstleistungen
 * @version   0.24.1
 * @date      2025-10-09
 * @license   MIT License
 *
 * Adds soft deletes to recipes and ensures kas_templates has kas_client_id FK.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1) add softDeletes to recipes (if not present)
        if (Schema::hasTable('recipes') && !Schema::hasColumn('recipes', 'deleted_at')) {
            Schema::table('recipes', function (Blueprint $table) {
                $table->softDeletes()->after('updated_at');
            });
        }

        // 2) ensure kas_templates table exists
        if (!Schema::hasTable('kas_templates')) {
            // If table is missing, create a minimal placeholder to accept kas_client_id FK.
            // But normally kas_templates should already exist; in that case this block does nothing.
            Schema::create('kas_templates', function (Blueprint $table) {
                $table->id();
                $table->string('template_type', 64);
                $table->string('template_name', 120);
                $table->text('description')->nullable();
                $table->json('data_json')->nullable();
                $table->timestamps();
            });
        }

        // 3) add kas_client_id column + FK to kas_clients if not exists
        if (Schema::hasTable('kas_templates') && !Schema::hasColumn('kas_templates', 'kas_client_id')) {
            Schema::table('kas_templates', function (Blueprint $table) {
                $table->unsignedBigInteger('kas_client_id')->nullable()->after('data_json');
            });

            // Add foreign key constraint only if kas_clients table exists
            if (Schema::hasTable('kas_clients')) {
                // Determine a safe constraint name
                $fkName = 'kas_templates_kas_client_id_foreign';
                // Use raw statement to add FK (some DB drivers care about index naming)
                DB::statement("
                    ALTER TABLE kas_templates
                    ADD CONSTRAINT {$fkName}
                    FOREIGN KEY (kas_client_id) REFERENCES kas_clients(id)
                    ON DELETE SET NULL
                    ON UPDATE CASCADE
                ");
            }
        } else {
            // If column exists but not FK, try to add FK (best-effort)
            if (Schema::hasTable('kas_templates') && Schema::hasColumn('kas_templates', 'kas_client_id') && Schema::hasTable('kas_clients')) {
                // Check existing foreign keys via information_schema (MySQL)
                $database = DB::getDatabaseName();
                $hasFk = DB::selectOne(
                    "SELECT COUNT(*) AS cnt FROM information_schema.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'kas_templates' AND COLUMN_NAME = 'kas_client_id' AND REFERENCED_TABLE_NAME = 'kas_clients'",
                    [$database]
                )->cnt ?? 0;

                if (empty($hasFk)) {
                    $fkName = 'kas_templates_kas_client_id_foreign';
                    DB::statement("
                        ALTER TABLE kas_templates
                        ADD CONSTRAINT {$fkName}
                        FOREIGN KEY (kas_client_id) REFERENCES kas_clients(id)
                        ON DELETE SET NULL
                        ON UPDATE CASCADE
                    ");
                }
            }
        }
    }

    public function down(): void
    {
        // 1) drop FK on kas_templates if exists, then drop column if we added it
        if (Schema::hasTable('kas_templates') && Schema::hasColumn('kas_templates', 'kas_client_id')) {
            // Attempt to drop FK by name; MySQL created name may differ, so use information_schema to find it
            try {
                $database = DB::getDatabaseName();
                $fk = DB::selectOne("
                    SELECT CONSTRAINT_NAME AS fk_name
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'kas_templates' AND COLUMN_NAME = 'kas_client_id' AND REFERENCED_TABLE_NAME = 'kas_clients'
                ", [$database]);

                if ($fk && !empty($fk->fk_name)) {
                    DB::statement("ALTER TABLE kas_templates DROP FOREIGN KEY {$fk->fk_name}");
                }
            } catch (\Throwable $e) {
                // ignore errors on drop FK
            }

            // drop column
            Schema::table('kas_templates', function (Blueprint $table) {
                $table->dropColumn('kas_client_id');
            });
        }

        // 2) remove softDeletes from recipes if present
        if (Schema::hasTable('recipes') && Schema::hasColumn('recipes', 'deleted_at')) {
            Schema::table('recipes', function (Blueprint $table) {
                $table->dropColumn('deleted_at');
            });
        }
    }
};
