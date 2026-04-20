<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_apps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('api_key', 64)->unique();
            $table->string('api_secret', 64);
            $table->string('webhook_url')->nullable();
            $table->string('webhook_secret', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('allowed_origins')->nullable();
            $table->timestamps();
        });

        // Add external_app_id and external_customer_id to tickets table
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('external_app_id')->nullable()->after('id')->constrained('external_apps')->nullOnDelete();
            $table->string('external_customer_id')->nullable()->after('external_app_id');
            $table->string('external_customer_email')->nullable()->after('external_customer_id');
            $table->string('external_customer_name')->nullable()->after('external_customer_email');

            $table->index(['external_app_id', 'external_customer_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['external_app_id']);
            $table->dropColumn(['external_app_id', 'external_customer_id', 'external_customer_email', 'external_customer_name']);
        });

        Schema::dropIfExists('external_apps');
    }
};
