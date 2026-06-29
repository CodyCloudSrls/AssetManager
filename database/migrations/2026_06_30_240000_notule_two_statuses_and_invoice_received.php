<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Notule move to two payment states only: non pagata (unpaid) / pagata (paid). The old
 * three-state model (pending / invoiced / paid) collapses: pending+invoiced -> unpaid,
 * paid stays paid. The "invoice issued" concept becomes a manual `invoice_received` flag,
 * set after payment. Existing invoiced rows keep that meaning via invoice_received = true.
 * Non-destructive: amounts and paid_amount are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notule')) {
            return;
        }

        if (! Schema::hasColumn('notule', 'invoice_received')) {
            Schema::table('notule', function (Blueprint $table) {
                $table->boolean('invoice_received')->default(false)->after('status');
            });
        }

        // Preserve the old "invoice issued" meaning before remapping the status value.
        DB::table('notule')->where('status', 'invoiced')->update(['invoice_received' => true]);

        // Collapse to two states.
        DB::table('notule')->whereIn('status', ['pending', 'invoiced'])->update(['status' => 'unpaid']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('notule')) {
            return;
        }

        // Best-effort restore of the previous three-state model.
        DB::table('notule')->where('status', 'unpaid')->where('invoice_received', true)->update(['status' => 'invoiced']);
        DB::table('notule')->where('status', 'unpaid')->update(['status' => 'pending']);

        if (Schema::hasColumn('notule', 'invoice_received')) {
            Schema::table('notule', function (Blueprint $table) {
                $table->dropColumn('invoice_received');
            });
        }
    }
};
