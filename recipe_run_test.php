<?php
$run = \App\Models\RecipeRun::create([
    'recipe_id'  => 4,
    'status'     => 'running',
    'kas_login'  => 'w01e77bc',
    'domain_name'=> 'r3d3.de',
    'variables'  => json_encode([
        'kas_login'         => 'w01e77bc',
        'domain_name'       => 'r3d3.de',
        'mail_account'      => 'tester',
        'mail_password'     => 'ChangeMe123!',
        'mail_quota_mb'     => 1024,
        'mail_forward_from' => 'test2',
        'mail_forward_to'   => 'tester',
    ]),
    'started_at' => now(),
]);

$disp    = app(\App\Services\Recipes\Dispatcher::class);
$recipe  = \App\Models\Recipe::with('actions')->find($run->recipe_id);
$results = [];

foreach ($recipe->actions->sortBy('order') as $a) {
    $rv = is_array($run->variables)
        ? $run->variables
        : (is_string($run->variables) ? json_decode($run->variables, true) : []);
    $ap = is_array($a->parameters)
        ? $a->parameters
        : (is_string($a->parameters) ? json_decode($a->parameters, true) : []);
    $merged = array_merge($rv, $ap);

    foreach ($merged as $k => $v) {
        if (is_string($v)) {
            foreach ($rv as $rk => $rvv) {
                $v = str_replace('{' . $rk . '}', $rvv, $v);
            }
            $merged[$k] = $v;
        }
    }

    $results[] = $disp->dispatch($a, $run, $merged, false);
}

var_export($results);
