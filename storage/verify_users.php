<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pdo = Illuminate\Support\Facades\DB::connection()->getPdo();
$stmt = $pdo->query("SELECT email, role, is_active FROM users WHERE email IN ('admin@example.com','enforcer@example.com','clamp@example.com','cashier@example.com','owner@example.com') ORDER BY email");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['email'] . ' | ' . $row['role'] . ' | ' . ($row['is_active'] ? 'active' : 'inactive') . PHP_EOL;
}
