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
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('invoice_prefix')->nullable()->after('industry');
            $table->text('invoice_terms')->nullable()->after('invoice_prefix');
            $table->string('tax_label')->default('Tax')->nullable()->after('invoice_terms');
            $table->decimal('tax_percentage', 5, 2)->nullable()->after('tax_label');
            $table->string('currency_symbol')->nullable()->after('tax_percentage');
            $table->string('currency_code')->nullable()->after('currency_symbol');
            $table->string('currency_country')->nullable()->after('currency_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_prefix',
                'invoice_terms',
                'tax_label',
                'tax_percentage',
                'currency_symbol',
                'currency_code',
                'currency_country',
            ]);
        });
    }
};
