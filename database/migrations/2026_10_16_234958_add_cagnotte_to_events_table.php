<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('cagnotte_enabled')->default(false);
            $table->unsignedInteger('cagnotte_goal')->nullable();
            $table->text('cagnotte_message')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['cagnotte_enabled', 'cagnotte_goal', 'cagnotte_message']);
        });
    }
};
