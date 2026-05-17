<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add soft deletes to follow_up_items and the missing FK on section_id
        Schema::table('follow_up_items', function (Blueprint $table): void {
            $table->softDeletes();
            $table->foreign('section_id')
                ->references('id')
                ->on('sections')
                ->nullOnDelete();
        });

        // Protect audit history: soft-delete rows rather than cascade-hard-delete
        Schema::table('item_histories', function (Blueprint $table): void {
            $table->softDeletes();
        });

        // Protect document records the same way
        Schema::table('item_documents', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('item_documents', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('item_histories', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('follow_up_items', function (Blueprint $table): void {
            $table->dropForeign(['section_id']);
            $table->dropSoftDeletes();
        });
    }
};
