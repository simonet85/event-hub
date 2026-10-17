<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gift_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('amount');
            $table->string('message', 500)->nullable();
            $table->string('status')->default('pending');
            $table->string('payment_token', 100)->nullable()->unique();
            $table->text('payment_url')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
            $table->index(['gift_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
