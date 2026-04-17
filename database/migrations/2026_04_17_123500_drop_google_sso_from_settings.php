<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('settings', 'google_login') ? 'google_login' : null,
                Schema::hasColumn('settings', 'google_client_id') ? 'google_client_id' : null,
                Schema::hasColumn('settings', 'google_client_secret') ? 'google_client_secret' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            if (! Schema::hasColumn('settings', 'google_login')) {
                $table->boolean('google_login')->nullable()->default(0);
            }

            if (! Schema::hasColumn('settings', 'google_client_id')) {
                $table->string('google_client_id')->nullable()->default(null);
            }

            if (! Schema::hasColumn('settings', 'google_client_secret')) {
                $table->string('google_client_secret')->nullable()->default(null);
            }
        });
    }
};
