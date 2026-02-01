<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('action'); // 'view', 'download'
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('location')->nullable(); // City, Country if available
            $table->string('device')->nullable(); // Desktop, Mobile, Tablet
            $table->string('platform')->nullable(); // Windows, iOS, Android
            $table->string('browser')->nullable(); // Chrome, Safari
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_usage_logs');
    }
};
