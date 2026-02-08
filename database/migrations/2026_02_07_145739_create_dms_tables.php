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
        // 1. Nodes (Files & Folders)
        Schema::create('dms_nodes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('dms_nodes')->onDelete('cascade'); // Cascade deletes children
            $table->string('name');
            $table->enum('type', ['folder', 'file']);
            $table->string('mime_type')->nullable(); // Only for files
            $table->unsignedBigInteger('size')->default(0); // In bytes
            $table->json('metadata')->nullable(); // Tags, descriptions, etc.

            // Audit columns
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes(); // Clean "Trash" functionality

            // Indexes for speed
            $table->index(['business_id', 'parent_id']);
        });

        // 2. Version Control
        Schema::create('dms_file_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained('dms_nodes')->onDelete('cascade');
            $table->string('storage_path'); // S3 Key or Local Path
            $table->integer('version_number');
            $table->string('checksum')->nullable(); // SHA-256 for integrity
            $table->unsignedBigInteger('size');

            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['node_id', 'version_number']);
        });

        // 3. Permissions (Sharing)
        Schema::create('dms_node_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained('dms_nodes')->onDelete('cascade');

            // Allow sharing with User or Team (Polymorphic)
            $table->unsignedBigInteger('accessor_id');
            $table->string('accessor_type'); // App\Models\User or App\Models\Team

            $table->enum('permission_level', ['viewer', 'editor', 'manager']);

            $table->timestamps();

            // Prevent duplicate rules
            $table->unique(['node_id', 'accessor_id', 'accessor_type'], 'unique_node_permission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dms_node_permissions');
        Schema::dropIfExists('dms_file_versions');
        Schema::dropIfExists('dms_nodes');
    }
};
