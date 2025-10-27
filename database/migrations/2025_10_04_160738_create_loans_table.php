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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_id')->index();
            $table->unsignedBigInteger('member_id')->index();
            $table->date('loaned_at')->nullable(); // Null until approved
            $table->date('due_at')->nullable(); // Calculated when approved
            $table->date('returned_at')->nullable();
            $table->string('status', 20); // pending, approved, rejected, borrowed, returned, overdue
            $table->text('notes')->nullable(); // Rejection reason or admin notes
            $table->unsignedBigInteger('approved_by')->nullable(); // Admin who approved/rejected
            $table->timestamp('approved_at')->nullable(); // When approved/rejected
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
