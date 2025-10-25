<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* === ROLES → USERS (RESTRICT) === */
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')
                  ->references('id')->on('roles')
                  ->restrictOnDelete();
        });

        /* === USERS → MEMBERS (CASCADE) === */
        Schema::table('members', function (Blueprint $table) {
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->cascadeOnDelete();
        });

        /* === CATEGORIES/AUTHORS/SHELVES → BOOKS (RESTRICT) === */
        Schema::table('books', function (Blueprint $table) {
            $table->foreign('category_id')
                    ->references('id')->on('categories')
                    ->restrictOnDelete();

            $table->foreign('author_id')
                    ->references('id')->on('authors')
                    ->restrictOnDelete();
        });

        /* === BOOKS → LOANS === and to member */
        Schema::table('loans', function (Blueprint $table) {
            $table->foreign('book_id')
                  ->references('id')->on('books')
                  ->restrictOnDelete();

            $table->foreign('member_id')
                  ->references('id')->on('members')
                    ->restrictOnDelete();
        });
        

        /* === LOANS → FINES (CASCADE) === */
        Schema::table('fines', function (Blueprint $table) {
            $table->foreign('loan_id')
                  ->references('id')->on('loans')
                  ->cascadeOnDelete();
        });

        /* === USERS → NOTIFICATIONS (CASCADE) === */
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreign('member_id')
                  ->references('id')->on('members')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropForeign(['role_id']));
        Schema::table('members', fn (Blueprint $t) => $t->dropForeign(['user_id']));
        Schema::table('books', function (Blueprint $t) {
            foreach (['category_id','author_id','shelf_id'] as $col) {
                if (Schema::hasColumn('books', $col)) $t->dropForeign([$col]);
            }
        });
        Schema::table('loans', function (Blueprint $t) {
            foreach (['book_id','created_by'] as $col) {
                if (Schema::hasColumn('loans', $col)) $t->dropForeign([$col]);
            }
        });
        if (Schema::hasTable('reservations')) {
            Schema::table('reservations', function (Blueprint $t) {
                foreach (['book_id','created_by'] as $col) {
                    if (Schema::hasColumn('reservations', $col)) $t->dropForeign([$col]);
                }
            });
        }
        Schema::table('fines', fn (Blueprint $t) => $t->dropForeign(['loan_id']));
        Schema::table('notifications', fn (Blueprint $t) => $t->dropForeign(['user_id']));
    }
};
