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
        Schema::create('follow_up_items', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code')->unique();
            $table->string('item_type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('eoffice_reference')->nullable()->index();
            $table->string('source')->nullable();
            $table->date('received_at');
            $table->string('priority')->default('medium');
            $table->string('status')->default('new')->index();
            $table->foreignId('current_owner_id')->constrained('users');
            $table->unsignedBigInteger('section_id')->nullable()->index();
            $table->string('next_action');
            $table->date('next_follow_up_date');
            $table->date('due_date')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('closure_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_up_items');
    }
};
