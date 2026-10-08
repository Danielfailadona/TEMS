<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['citation_id']);
            $table->unsignedBigInteger('citation_id')->nullable()->change();

            $table->string('payable_type')->nullable()->after('citation_id');
            $table->unsignedBigInteger('payable_id')->nullable()->after('payable_type');

            $table->index(['payable_type', 'payable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payable_type', 'payable_id']);
            $table->dropColumn(['payable_type', 'payable_id']);

            $table->unsignedBigInteger('citation_id')->nullable(false)->change();
            $table->foreign('citation_id')->references('id')->on('citations');
        });
    }
};