<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('quantity')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cart_id');
            $table->dropColumn(['product_id', 'quantity']);
        });

        Schema::table('carts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
