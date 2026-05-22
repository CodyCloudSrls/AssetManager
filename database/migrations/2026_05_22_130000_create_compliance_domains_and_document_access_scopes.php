<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('compliance_domains')) {
            Schema::create('compliance_domains', function (Blueprint $table) {
                $table->id();
                $table->string('key', 80)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_system')->default(false);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_active', 'sort_order']);
            });
        }

        if (! Schema::hasColumn('users', 'compliance_scope_restricted')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('compliance_scope_restricted')->default(false)->after('permissions');
            });
        }

        if (! Schema::hasTable('user_compliance_domains')) {
            Schema::create('user_compliance_domains', function (Blueprint $table) {
                $table->unsignedInteger('user_id');
                $table->unsignedBigInteger('compliance_domain_id');
                $table->timestamps();

                $table->primary(['user_id', 'compliance_domain_id'], 'user_compliance_domains_primary');
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('compliance_domain_id')->references('id')->on('compliance_domains')->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('documents', 'document_area')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->string('document_area', 40)->nullable()->after('status')->index();
            });
        }

        $this->seedDefaultDomains();
        $this->backfillLegacyFrameworkDomains();
    }

    public function down(): void
    {
        if (Schema::hasColumn('documents', 'document_area')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropColumn('document_area');
            });
        }

        Schema::dropIfExists('user_compliance_domains');

        if (Schema::hasColumn('users', 'compliance_scope_restricted')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('compliance_scope_restricted');
            });
        }

        Schema::dropIfExists('compliance_domains');
    }

    private function seedDefaultDomains(): void
    {
        $now = now();

        foreach ($this->defaultDomains() as $sortOrder => $definition) {
            $payload = [
                'name' => $definition['name'],
                'description' => $definition['description'],
                'is_active' => true,
                'is_system' => true,
                'sort_order' => $sortOrder + 10,
                'updated_at' => $now,
            ];

            if (DB::table('compliance_domains')->where('key', $definition['key'])->exists()) {
                DB::table('compliance_domains')->where('key', $definition['key'])->update($payload);
            } else {
                DB::table('compliance_domains')->insert($payload + [
                    'key' => $definition['key'],
                    'created_at' => $now,
                ]);
            }
        }
    }

    private function backfillLegacyFrameworkDomains(): void
    {
        if (! Schema::hasTable('document_frameworks') || ! Schema::hasColumn('document_frameworks', 'compliance_domain')) {
            return;
        }

        $existingDomains = DB::table('document_frameworks')
            ->whereNotNull('compliance_domain')
            ->where('compliance_domain', '!=', '')
            ->distinct()
            ->pluck('compliance_domain')
            ->filter()
            ->values();

        $now = now();
        $existingKeys = DB::table('compliance_domains')->pluck('key')->all();

        foreach ($existingDomains as $legacyDomainKey) {
            $domainKey = Str::slug((string) $legacyDomainKey, '_');

            if ($domainKey === '') {
                continue;
            }

            if ($domainKey !== $legacyDomainKey) {
                DB::table('document_frameworks')
                    ->where('compliance_domain', $legacyDomainKey)
                    ->update(['compliance_domain' => $domainKey]);
            }

            if (in_array($domainKey, $existingKeys, true)) {
                continue;
            }

            DB::table('compliance_domains')->insert([
                'key' => $domainKey,
                'name' => Str::headline(str_replace(['_', '-'], ' ', (string) $legacyDomainKey)),
                'description' => null,
                'is_active' => true,
                'is_system' => false,
                'sort_order' => 500,
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $existingKeys[] = $domainKey;
        }
    }

    private function defaultDomains(): array
    {
        return [
            ['key' => 'nis2', 'name' => 'NIS2', 'description' => 'Network and Information Security Directive requirements.'],
            ['key' => 'gdpr', 'name' => 'GDPR', 'description' => 'Privacy and personal data protection requirements.'],
            ['key' => 'ai_act', 'name' => 'AI Act', 'description' => 'Artificial intelligence governance requirements.'],
            ['key' => 'iso27001', 'name' => 'ISO 27001', 'description' => 'Information security management requirements.'],
            ['key' => 'supplier_risk', 'name' => 'Supplier Risk', 'description' => 'Supplier and third-party risk requirements.'],
            ['key' => 'internal', 'name' => 'Internal', 'description' => 'Internal governance requirements.'],
            ['key' => 'custom', 'name' => 'Custom', 'description' => 'Custom compliance scope.'],
        ];
    }
};
