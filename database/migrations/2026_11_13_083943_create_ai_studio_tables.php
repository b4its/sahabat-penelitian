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
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('provider'); // openai, anthropic, gemini, groq, openrouter, ollama, custom
            $table->string('base_url')->nullable();
            $table->text('api_key')->nullable();
            $table->json('models');
            $table->string('default_model')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->string('status')->default('ready'); // ready, connected, error
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title')->default('Obrolan AI Baru');
            $table->foreignId('ai_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->string('model')->nullable();
            $table->text('system_prompt')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('ai_chat_sessions')->cascadeOnDelete();
            $table->string('role'); // user, assistant, system
            $table->longText('content');
            $table->string('provider_name')->nullable();
            $table->string('model_used')->nullable();
            $table->unsignedInteger('tokens_used')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
        Schema::dropIfExists('ai_chat_sessions');
        Schema::dropIfExists('ai_providers');
    }
};
