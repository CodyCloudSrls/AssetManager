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
        Schema::create('ticket_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->index();
            $table->string('color', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_closed')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedInteger('company_id')->nullable()->index();
            $table->string('visibility_type', 32)->default('global');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ticket_priorities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->index();
            $table->string('color', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedInteger('response_hours')->nullable();
            $table->unsignedInteger('resolution_hours')->nullable();
            $table->unsignedInteger('company_id')->nullable()->index();
            $table->string('visibility_type', 32)->default('global');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ticket_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug')->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedInteger('company_id')->nullable()->index();
            $table->string('visibility_type', 32)->default('global');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ticket_number', 32)->unique();
            $table->unsignedInteger('company_id')->nullable()->index();
            $table->unsignedInteger('requester_id')->nullable()->index();
            $table->unsignedInteger('assignee_id')->nullable()->index();
            $table->unsignedInteger('ticket_type_id')->nullable()->index();
            $table->unsignedInteger('ticket_status_id')->nullable()->index();
            $table->unsignedInteger('ticket_priority_id')->nullable()->index();
            $table->unsignedInteger('asset_id')->nullable()->index();
            $table->unsignedInteger('document_id')->nullable()->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->unsignedInteger('related_user_id')->nullable()->index();
            $table->string('source', 32)->default('internal')->index();
            $table->string('portal_token', 64)->nullable()->unique();
            $table->string('subject');
            $table->longText('description');
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable()->index();
            $table->string('guest_phone')->nullable();
            $table->timestamp('first_response_due_at')->nullable();
            $table->timestamp('resolution_due_at')->nullable();
            $table->timestamp('first_responded_at')->nullable();
            $table->timestamp('last_replied_at')->nullable();
            $table->timestamp('last_public_reply_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ticket_worklogs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ticket_id')->index();
            $table->unsignedInteger('company_id')->nullable()->index();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('category', 32)->default('analysis');
            $table->boolean('is_billable')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('minutes');
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_worklogs');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_types');
        Schema::dropIfExists('ticket_priorities');
        Schema::dropIfExists('ticket_statuses');
    }

    private function seedDefaults(): void
    {
        if (DB::table('ticket_statuses')->count() === 0) {
            $now = now();
            $statuses = [
                ['name' => 'New', 'slug' => 'new', 'color' => '#2f80ed', 'is_closed' => false, 'sort_order' => 10],
                ['name' => 'Triage', 'slug' => 'triage', 'color' => '#56ccf2', 'is_closed' => false, 'sort_order' => 20],
                ['name' => 'In Progress', 'slug' => 'in-progress', 'color' => '#f2994a', 'is_closed' => false, 'sort_order' => 30],
                ['name' => 'Waiting Customer', 'slug' => 'waiting-customer', 'color' => '#9b51e0', 'is_closed' => false, 'sort_order' => 40],
                ['name' => 'Waiting Vendor', 'slug' => 'waiting-vendor', 'color' => '#bb6bd9', 'is_closed' => false, 'sort_order' => 50],
                ['name' => 'Resolved', 'slug' => 'resolved', 'color' => '#27ae60', 'is_closed' => false, 'sort_order' => 60],
                ['name' => 'Closed', 'slug' => 'closed', 'color' => '#6c757d', 'is_closed' => true, 'sort_order' => 70],
                ['name' => 'Cancelled', 'slug' => 'cancelled', 'color' => '#eb5757', 'is_closed' => true, 'sort_order' => 80],
            ];

            DB::table('ticket_statuses')->insert(array_map(function (array $status) use ($now) {
                return array_merge($status, [
                    'is_active' => true,
                    'company_id' => null,
                    'visibility_type' => 'global',
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }, $statuses));
        }

        if (DB::table('ticket_priorities')->count() === 0) {
            $now = now();
            $priorities = [
                ['name' => 'Low', 'slug' => 'low', 'color' => '#6c757d', 'sort_order' => 10, 'response_hours' => 24, 'resolution_hours' => 120],
                ['name' => 'Medium', 'slug' => 'medium', 'color' => '#2f80ed', 'sort_order' => 20, 'response_hours' => 8, 'resolution_hours' => 48],
                ['name' => 'High', 'slug' => 'high', 'color' => '#f2994a', 'sort_order' => 30, 'response_hours' => 4, 'resolution_hours' => 24],
                ['name' => 'Critical', 'slug' => 'critical', 'color' => '#eb5757', 'sort_order' => 40, 'response_hours' => 1, 'resolution_hours' => 8],
            ];

            DB::table('ticket_priorities')->insert(array_map(function (array $priority) use ($now) {
                return array_merge($priority, [
                    'is_active' => true,
                    'company_id' => null,
                    'visibility_type' => 'global',
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }, $priorities));
        }

        if (DB::table('ticket_types')->count() === 0) {
            $now = now();
            $types = [
                ['name' => 'Incident', 'slug' => 'incident', 'description' => 'Service interruption, malfunction, degradation or anomaly.', 'is_public' => true, 'sort_order' => 10],
                ['name' => 'Service Request', 'slug' => 'service-request', 'description' => 'General support, request, provisioning or user assistance.', 'is_public' => true, 'sort_order' => 20],
                ['name' => 'Access Request', 'slug' => 'access-request', 'description' => 'Access, permission or account related request.', 'is_public' => true, 'sort_order' => 30],
                ['name' => 'Change Request', 'slug' => 'change-request', 'description' => 'Planned change requiring evaluation and execution.', 'is_public' => false, 'sort_order' => 40],
            ];

            DB::table('ticket_types')->insert(array_map(function (array $type) use ($now) {
                return array_merge($type, [
                    'is_active' => true,
                    'company_id' => null,
                    'visibility_type' => 'global',
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }, $types));
        }
    }
};
