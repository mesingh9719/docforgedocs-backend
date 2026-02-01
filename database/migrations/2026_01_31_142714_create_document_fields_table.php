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
        Schema::create('document_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->foreignId('signer_id')->nullable()->constrained('document_signers')->onDelete('cascade');
            $table->string('type'); // signature, date, text
            $table->integer('page_number');
            $table->float('x_position');
            $table->float('y_position');
            $table->float('width')->nullable();
            $table->float('height')->nullable();
            $table->json('metadata')->nullable(); // For value, styling, etc.
            $table->text('value')->nullable(); // To store the signed value
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_fields');
    }
};
