<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-managed catalogues -------------------------------------------------
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');            // "COZA Sunday Service"
            $table->string('short_name');      // "COZA SUNDAY" (header / file name)
            $table->text('default_summary')->nullable();
            $table->text('default_conclusion')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('task_templates', function (Blueprint $table) {
            $table->id();
            // null = applies to every service type
            $table->foreignId('service_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number');          // "1", "9a" ...
            $table->string('title');
            $table->string('default_remarks')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('role')->nullable(); // HOD, AHOD ...
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Reports ----------------------------------------------------------------
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained();
            $table->date('service_date');
            $table->string('start_time')->nullable();
            $table->string('end_time')->nullable();
            $table->string('broadcast_status')->nullable();
            $table->string('prepared_by')->nullable();
            $table->text('summary')->nullable();
            $table->json('observations')->nullable();
            $table->text('conclusion')->nullable();
            $table->string('status')->default('draft'); // draft|submitted|approved|returned
            $table->text('admin_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('report_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->string('title');
            $table->string('handled_by')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedInteger('position')->default(0);
        });

        Schema::create('report_viewerships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('platform');
            $table->unsignedInteger('viewers')->nullable();
            $table->unsignedInteger('position')->default(0);
        });

        Schema::create('report_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('role')->nullable();
            $table->unsignedInteger('position')->default(0);
        });

        Schema::create('report_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('time')->nullable();
            $table->string('incident')->nullable();
            $table->string('severity')->nullable();
            $table->text('action_taken')->nullable();
            $table->string('status')->nullable();
            $table->unsignedInteger('position')->default(0);
        });
    }

    public function down(): void
    {
        foreach (['report_incidents', 'report_attendees', 'report_viewerships', 'report_tasks', 'reports', 'workers', 'platforms', 'task_templates', 'service_types'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
