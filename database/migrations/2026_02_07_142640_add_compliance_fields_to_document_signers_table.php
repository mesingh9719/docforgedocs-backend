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
        Schema::table('document_signers', function (Blueprint $table) {
            $table->string('access_code')->nullable()->after('token');
            $table->boolean('is_access_code_required')->default(false)->after('access_code');
            $table->timestamp('audit_consent_at')->nullable()->after('status');
            $table->string('audit_consent_ip')->nullable()->after('audit_consent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_signers', function (Blueprint $table) {
            //
        });
    }
};
