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
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->comment('this is owner of the business')->constrained()->cascadeOnDelete();
            $table->string('name')->comment('name of the business');
            $table->string('description')->comment('description of the business');
            $table->string('logo')->comment('logo of the business');
            $table->string('address')->comment('address of the business');
            $table->string('city')->comment('city of the business');
            $table->string('state')->comment('state of the business');
            $table->string('zip')->comment('zip of the business');
            $table->string('country')->comment('country of the business');
            $table->string('phone')->comment('phone of the business');
            $table->string('email')->comment('email of the business');
            $table->string('website')->comment('website of the business');
            $table->string('industry')->comment('industry of the business');
            $table->string('status')->comment('status of the business')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
