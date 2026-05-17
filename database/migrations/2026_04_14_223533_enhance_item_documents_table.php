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
        Schema::table('item_documents', function (Blueprint $table) {
            $table->string('category')->default('supporting_file')->after('follow_up_item_id');
            $table->string('document_label')->nullable()->after('category');
            $table->unsignedInteger('version_number')->default(1)->after('document_label');
            $table->boolean('is_primary')->default(false)->after('version_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_documents', function (Blueprint $table) {
            $table->dropColumn([
                'category',
                'document_label',
                'version_number',
                'is_primary',
            ]);
        });
    }
};
