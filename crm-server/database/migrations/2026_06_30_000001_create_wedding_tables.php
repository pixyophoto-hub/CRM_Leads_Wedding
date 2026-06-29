<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('source')->default('Lain-lain');
            $table->string('service')->default('Wedding');
            $table->string('status')->default('New Lead');
            $table->string('pic')->nullable();
            $table->unsignedInteger('value')->default(0);
            $table->text('notes')->nullable();
            $table->string('source_ref')->nullable()->index(); // dedupe / external id
            $table->timestamp('last_contact_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
            $table->index('phone');
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('direction')->default('me'); // customer | me
            $table->text('body');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('assignee')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->string('priority')->default('Sederhana');
            $table->boolean('done')->default(false);
            $table->timestamps();
        });

        Schema::create('automations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trigger')->nullable();
            $table->string('action')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('name')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('automations');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('leads');
    }
};
