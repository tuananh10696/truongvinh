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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained();
            $table->string('name');
            $table->string('extension', 16);
            $table->string('search_name')->index();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->date('expires_on')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_no');
            $table->string('storage_key');
            $table->unsignedBigInteger('size');
            $table->char('sha256', 64);
            $table->string('source', 16);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['document_id', 'version_no']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreign('current_version_id')->references('id')->on('document_versions');
        });

        Schema::create('edit_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('doc_key', 64)->unique();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('base_version_id')->constrained('document_versions');
            $table->string('status', 16)->default('open');
            $table->unsignedTinyInteger('last_status_code')->nullable();
            $table->foreignId('opened_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['document_id', 'status']);
        });

        Schema::create('document_opens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->timestamp('opened_at');

            $table->unique(['user_id', 'document_id']);
        });

        Schema::create('folder_pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'folder_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folder_pins');
        Schema::dropIfExists('document_opens');
        Schema::dropIfExists('edit_sessions');

        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
        });

        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
    }
};
