<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'clamping_officer')
            ->update(['role' => 'enforcer']);
    }

    public function down(): void
    {
        // Cannot reliably reverse role conversions; leave the change in place.
    }
};