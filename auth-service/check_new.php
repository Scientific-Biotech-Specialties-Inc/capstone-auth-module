<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$u = DB::table('users')->orderByDesc('id')->first();
echo 'newest_user=' . $u->id . '|' . $u->email . '|' . $u->created_at . PHP_EOL;
$r = DB::table('roles')->orderByDesc('id')->first();
echo 'newest_role=' . $r->id . '|' . $r->name . '|' . $r->created_at . PHP_EOL;
echo 'users=' . DB::table('users')->count() . ' roles=' . DB::table('roles')->count() . PHP_EOL;
