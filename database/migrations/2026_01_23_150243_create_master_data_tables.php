<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // USD, EUR
            $table->string('name');
            $table->string('symbol')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('iso_code', 2)->unique();
            $table->string('phone_code')->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('iso_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // VAT, GST
            $table->decimal('rate', 5, 2); // 18.00
            $table->string('type')->default('percentage'); // percentage, fixed
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxes');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('states');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('industries');
        Schema::dropIfExists('currencies');
    }
};
