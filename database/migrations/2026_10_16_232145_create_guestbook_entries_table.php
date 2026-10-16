<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guestbook_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guestbook_entries');
    }
};
