<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunSnippet extends Command
{
    protected $signature = 'run:snippet';
    protected $description = 'Run a hardcoded Laravel snippet';

    public function handle()
    {
        require base_path('recipe_run_test.php');
        return 0;
    }
}
