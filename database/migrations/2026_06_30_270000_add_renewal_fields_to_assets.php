<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedicated renewal/expiry fields on assets (like the warranty fields), for virtual
 * assets that renew: domains, IPs, monitoring, certificates. `renewal_date` is the next
 * renewal/expiry, `auto_renewal` flags whether it renews automatically. Non-destructive.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assets')) {
            return;
        }

        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'renewal_date')) {
                $table->date('renewal_date')->nullable()->after('asset_eol_date');
                $table->index('renewal_date');
            }
            if (! Schema::hasColumn('assets', 'auto_renewal')) {
                $table->boolean('auto_renewal')->default(false)->after('renewal_date');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('assets')) {
            return;
        }

        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'renewal_date')) {
                $table->dropIndex(['renewal_date']);
                $table->dropColumn('renewal_date');
            }
            if (Schema::hasColumn('assets', 'auto_renewal')) {
                $table->dropColumn('auto_renewal');
            }
        });
    }
};
