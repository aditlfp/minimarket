<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::first();
echo "User: {$user->email}\n";
echo "Roles: " . $user->getRoleNames() . "\n";
echo "Has admin: " . ($user->hasRole('admin') ? 'YES' : 'NO') . "\n";
echo "Has kasir: " . ($user->hasRole('kasir') ? 'YES' : 'NO') . "\n";
echo "Outlet ID: " . ($user->outlet_id ?? 'NULL') . "\n";
