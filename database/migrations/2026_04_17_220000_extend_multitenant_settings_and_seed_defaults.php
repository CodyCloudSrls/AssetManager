<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addTemplateOwnershipColumns('categories', 'categories_company_visibility_deleted_idx');
        $this->addTemplateOwnershipColumns('status_labels', 'status_labels_company_visibility_deleted_idx');
        $this->addTemplateOwnershipColumns('manufacturers', 'manufacturers_company_visibility_deleted_idx');
        $this->addTemplateOwnershipColumns('suppliers', 'suppliers_company_visibility_deleted_idx');
        $this->addTemplateOwnershipColumns('depreciations', 'depreciations_company_visibility_idx');

        DB::table('settings')->update([
            'full_multiple_companies_support' => 1,
            'scope_locations_fmcs' => 1,
        ]);

        $codyCloudCompanyId = $this->getCodyCloudCompanyId();
        $adminUserId = $this->getAdminUserId();

        if ($codyCloudCompanyId) {
            foreach ([
                'models',
                'custom_fieldsets',
                'document_types',
                'document_frameworks',
                'categories',
                'status_labels',
                'manufacturers',
                'suppliers',
                'depreciations',
            ] as $tableName) {
                if (Schema::hasColumn($tableName, 'company_id')) {
                    DB::table($tableName)
                        ->whereNull('company_id')
                        ->update([
                            'company_id' => $codyCloudCompanyId,
                            'visibility_type' => 'private',
                        ]);
                }
            }

            DB::table('locations')
                ->whereNull('deleted_at')
                ->whereNull('company_id')
                ->update([
                    'company_id' => $codyCloudCompanyId,
                ]);
        }

        if (! app()->environment('testing')) {
            $this->seedGlobalDefaultCategories($adminUserId);
            $this->seedGlobalDefaultStatusLabels($adminUserId);
            $this->seedGlobalDefaultManufacturers($adminUserId);
            $this->seedGlobalDefaultSuppliers($adminUserId);
            $this->seedGlobalDefaultDepreciations($adminUserId);
        }
    }

    public function down(): void
    {
        $this->dropTemplateOwnershipColumns('categories', 'categories_company_visibility_deleted_idx');
        $this->dropTemplateOwnershipColumns('status_labels', 'status_labels_company_visibility_deleted_idx');
        $this->dropTemplateOwnershipColumns('manufacturers', 'manufacturers_company_visibility_deleted_idx');
        $this->dropTemplateOwnershipColumns('suppliers', 'suppliers_company_visibility_deleted_idx');
        $this->dropTemplateOwnershipColumns('depreciations', 'depreciations_company_visibility_idx');
    }

    private function addTemplateOwnershipColumns(string $tableName, string $indexName): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexName) {
            if (! Schema::hasColumn($tableName, 'company_id')) {
                $table->unsignedInteger('company_id')->nullable()->after('created_by');
            }

            if (! Schema::hasColumn($tableName, 'visibility_type')) {
                $table->string('visibility_type', 32)->default('global')->after('company_id');
            }

            if (! $this->indexExists($tableName, $indexName)) {
                $columns = ['company_id', 'visibility_type'];

                if (Schema::hasColumn($tableName, 'deleted_at')) {
                    $columns[] = 'deleted_at';
                }

                $table->index($columns, $indexName);
            }
        });
    }

    private function dropTemplateOwnershipColumns(string $tableName, string $indexName): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexName) {
            if ($this->indexExists($tableName, $indexName)) {
                $table->dropIndex($indexName);
            }

            if (Schema::hasColumn($tableName, 'visibility_type')) {
                $table->dropColumn('visibility_type');
            }

            if (Schema::hasColumn($tableName, 'company_id')) {
                $table->dropColumn('company_id');
            }
        });
    }

    private function seedGlobalDefaultCategories(int $adminUserId): void
    {
        $now = Carbon::now();

        $defaults = [
            ['name' => 'Laptops', 'category_type' => 'asset', 'require_acceptance' => 1],
            ['name' => 'Desktops', 'category_type' => 'asset', 'require_acceptance' => 1],
            ['name' => 'Displays', 'category_type' => 'asset', 'require_acceptance' => 0],
            ['name' => 'Mobile Phones', 'category_type' => 'asset', 'require_acceptance' => 0],
            ['name' => 'Tablets', 'category_type' => 'asset', 'require_acceptance' => 0],
            ['name' => 'Keyboards', 'category_type' => 'accessory', 'require_acceptance' => 0],
            ['name' => 'Mouse', 'category_type' => 'accessory', 'require_acceptance' => 0],
            ['name' => 'Printer Paper', 'category_type' => 'consumable', 'require_acceptance' => 0],
            ['name' => 'Printer Ink', 'category_type' => 'consumable', 'require_acceptance' => 0],
            ['name' => 'Office Software', 'category_type' => 'license', 'require_acceptance' => 0],
        ];

        foreach ($defaults as $default) {
            $exists = DB::table('categories')
                ->whereNull('deleted_at')
                ->whereNull('company_id')
                ->where('name', $default['name'])
                ->where('category_type', $default['category_type'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('categories')->insert([
                'name' => $default['name'],
                'category_type' => $default['category_type'],
                'created_by' => $adminUserId,
                'company_id' => null,
                'visibility_type' => 'global',
                'require_acceptance' => $default['require_acceptance'],
                'checkin_email' => 0,
                'alert_on_response' => 0,
                'use_default_eula' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedGlobalDefaultStatusLabels(int $adminUserId): void
    {
        $now = Carbon::now();

        $defaults = [
            ['name' => 'Ready to Deploy', 'deployable' => 1, 'pending' => 0, 'archived' => 0, 'default_label' => 1],
            ['name' => 'Pending', 'deployable' => 0, 'pending' => 1, 'archived' => 0, 'default_label' => 1],
            ['name' => 'Archived', 'deployable' => 0, 'pending' => 0, 'archived' => 1, 'default_label' => 0],
        ];

        foreach ($defaults as $default) {
            $exists = DB::table('status_labels')
                ->whereNull('deleted_at')
                ->whereNull('company_id')
                ->where('name', $default['name'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('status_labels')->insert([
                'name' => $default['name'],
                'created_by' => $adminUserId,
                'company_id' => null,
                'visibility_type' => 'global',
                'deployable' => $default['deployable'],
                'pending' => $default['pending'],
                'archived' => $default['archived'],
                'default_label' => $default['default_label'],
                'show_in_nav' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedGlobalDefaultManufacturers(int $adminUserId): void
    {
        $now = Carbon::now();

        foreach (['Apple', 'Dell', 'HP', 'Lenovo', 'Microsoft', 'Samsung'] as $name) {
            $exists = DB::table('manufacturers')
                ->whereNull('deleted_at')
                ->whereNull('company_id')
                ->where('name', $name)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('manufacturers')->insert([
                'name' => $name,
                'created_by' => $adminUserId,
                'company_id' => null,
                'visibility_type' => 'global',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedGlobalDefaultSuppliers(int $adminUserId): void
    {
        $now = Carbon::now();

        $exists = DB::table('suppliers')
            ->whereNull('deleted_at')
            ->whereNull('company_id')
            ->where('name', 'Generic Supplier')
            ->exists();

        if (! $exists) {
            DB::table('suppliers')->insert([
                'name' => 'Generic Supplier',
                'created_by' => $adminUserId,
                'company_id' => null,
                'visibility_type' => 'global',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedGlobalDefaultDepreciations(int $adminUserId): void
    {
        $now = Carbon::now();

        $defaults = [
            ['name' => '24 Months', 'months' => 24],
            ['name' => '36 Months', 'months' => 36],
            ['name' => '60 Months', 'months' => 60],
        ];

        foreach ($defaults as $default) {
            $exists = DB::table('depreciations')
                ->whereNull('company_id')
                ->where('name', $default['name'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('depreciations')->insert([
                'name' => $default['name'],
                'months' => $default['months'],
                'created_by' => $adminUserId,
                'company_id' => null,
                'visibility_type' => 'global',
                'depreciation_type' => 'amount',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function getCodyCloudCompanyId(): ?int
    {
        return DB::table('companies')
            ->whereNull('deleted_at')
            ->where('name', 'CodyCloud')
            ->value('id')
            ?? DB::table('companies')->whereNull('deleted_at')->min('id');
    }

    private function getAdminUserId(): int
    {
        return (int) (DB::table('users')
            ->where('permissions', 'like', '%"superuser":1%')
            ->min('id') ?? 1);
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return collect(Schema::getIndexes($tableName))
            ->contains(fn (array $index) => ($index['name'] ?? null) === $indexName);
    }
};
