<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title')->default('Untitled Document');
            $table->longText('content_html')->nullable();
            $table->json('model_json')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('restricted_mode')->default('none'); // none, read_only, comments_only
            $table->string('password_hash')->nullable();
            $table->unsignedInteger('current_version')->default(1);
            $table->timestamps();
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('title');
            $table->longText('content_html');
            $table->json('model_json')->nullable();
            $table->string('author')->default('System');
            $table->string('change_summary')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'version_number']);
        });

        Schema::create('document_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('user_email');
            $table->string('role')->default('editor'); // owner, editor, reviewer, viewer
            $table->timestamps();

            $table->unique(['document_id', 'user_email']);
        });

        Schema::create('document_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('op_type');
            $table->json('payload');
            $table->string('actor')->default('User');
            $table->timestamps();
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->string('event_type');
            $table->string('actor')->default('Anonymous');
            $table->json('details')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('document_operations');
        Schema::dropIfExists('document_permissions');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
    }
};
