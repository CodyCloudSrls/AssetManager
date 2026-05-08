<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_frameworks', function (Blueprint $table) {
            if (! Schema::hasColumn('document_frameworks', 'source_pack_version')) {
                $table->string('source_pack_version', 80)->nullable()->after('source_pack_key')->index();
            }
        });

        foreach ((array) config('compliance_frameworks.packs', []) as $packKey => $pack) {
            $packVersion = $pack['pack_version'] ?? data_get($pack, 'framework.version');

            if (! $packVersion) {
                continue;
            }

            DB::table('document_frameworks')
                ->where('source_pack_key', $packKey)
                ->where(function ($query) {
                    $query->whereNull('source_pack_version')
                        ->orWhere('source_pack_version', '');
                })
                ->update(['source_pack_version' => $packVersion]);
        }
    }

    public function down(): void
    {
        Schema::table('document_frameworks', function (Blueprint $table) {
            if (Schema::hasColumn('document_frameworks', 'source_pack_version')) {
                $table->dropColumn('source_pack_version');
            }
        });
    }
};
