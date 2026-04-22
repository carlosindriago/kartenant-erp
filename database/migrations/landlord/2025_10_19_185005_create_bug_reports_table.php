<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('landlord')->create('bug_reports', function (Blueprint $table) {
            $table->id();

            // Información del reporte
            $table->string('ticket_number')->unique(); // BUG-2025-0001
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->string('title');
            $table->text('description');
            $table->text('steps_to_reproduce')->nullable();

            // Estado y prioridad
            $table->enum('status', ['pending', 'in_progress', 'waiting_feedback', 'resolved', 'closed'])->default('pending');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');

            // Usuario que reportó
            $table->string('reporter_name');
            $table->string('reporter_email');
            $table->unsignedBigInteger('reporter_user_id')->nullable();
            $table->string('reporter_ip')->nullable();

            // Tenant
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('tenant_name')->nullable();

            // Contexto técnico
            $table->text('url')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('screenshots')->nullable(); // Array de paths

            // Asignación y seguimiento
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Metadata
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('status');
            $table->index('severity');
            $table->index('priority');
            $table->index('tenant_id');
            $table->index('assigned_to');
            $table->index('created_at');

            // Foreign keys
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reporter_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('bug_reports');
    }
};
