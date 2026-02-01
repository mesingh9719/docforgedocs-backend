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
        Schema::table('documents', function (Blueprint $table) {
            $table->string('final_pdf_path')->nullable()->after('pdf_path');
            $table->string('document_hash')->nullable()->after('status');
            $table->boolean('is_locked')->default(false)->after('document_hash');
            $table->timestamp('expires_at')->nullable()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['final_pdf_path', 'document_hash', 'is_locked', 'expires_at']);
        });
    }
};
