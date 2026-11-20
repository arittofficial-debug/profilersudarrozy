<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('usergate_id')->nullable()->unique()->after('id');
            $table->string('username')->nullable()->after('email');
            $table->boolean('is_active')->default(false)->after('password');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->timestamp('last_synced_at')->nullable()->after('last_login_at');

            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['usergate_id']);
            $table->dropColumn(['usergate_id', 'username', 'is_active', 'last_login_at', 'last_synced_at']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
