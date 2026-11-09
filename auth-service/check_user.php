<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$emails = [
    'sales-marketing-admin@example.com',
    'sales-marketing-manager@example.com',
    'sales-marketing-officer@example.com'
];

foreach ($emails as $email) {
    $user = \App\Models\User::where('email', $email)->first();
    if (!$user) {
        echo "$email: NOT FOUND\n";
        continue;
    }
    $role = $user->profile?->role?->name ?? 'None';
    $dept = $user->profile?->department?->name ?? 'None';
    $hashOk = \Illuminate\Support\Facades\Hash::check('password', $user->credentials?->password_hash);
    echo "$email | Role: $role | Dept: $dept | Password 'password' valid: " . ($hashOk ? 'YES' : 'NO') . "\n";
}
