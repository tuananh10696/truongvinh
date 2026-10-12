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
        Schema::table('users', function (Blueprint $table) {
            $table->string('department')->nullable()->after('email');
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tax_code')->nullable();
            $table->string('country')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('name');
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('folder_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('folder_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_template_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('folders');
            $table->string('name');
            $table->string('search_name')->index();
            $table->string('kind', 32);
            $table->string('path')->index();
            $table->unsignedSmallInteger('depth')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('company_id')->nullable()->constrained();
            $table->foreignId('project_id')->nullable()->constrained();
            $table->boolean('is_system')->default(false);
            $table->boolean('inherit_permissions')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folders');
        Schema::dropIfExists('folder_template_items');
        Schema::dropIfExists('folder_templates');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('companies');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }
};
