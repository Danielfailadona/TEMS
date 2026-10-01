<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Create a mock request with session
$request = \Illuminate\Http\Request::create('/users', 'GET');
$app->instance('request', $request);

$user = App\Models\User::first();
if ($user) {
    auth()->login($user);
    $html = view('users.index', ['users' => App\Models\User::paginate(10), 'roles' => App\Enums\Role::cases()])->render();
    echo 'SUCCESS: ' . strlen($html) . ' chars';
    file_put_contents('test_output.html', $html);
} else {
    echo 'No users found';
}