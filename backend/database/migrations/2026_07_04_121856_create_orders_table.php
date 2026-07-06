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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->string('paypal_order_status')->nullable();
            $table->string('capture_id')->nullable()->unique();
            $table->string('capture_status')->nullable();
            $table->string('payer_name')->nullable();
            $table->string('payer_email')->nullable();
            $table->string('item_number')->index();
            $table->string('title');
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('mail_sent_at')->nullable();
            $table->text('mail_error')->nullable();
            $table->json('raw_payload')->nullable();
            $table->json('paypal_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
