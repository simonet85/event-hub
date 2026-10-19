<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lien de lecture seule pour le traiteur : un jeton non devinable et une date d'expiration.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('caterer_token', 64)->nullable()->unique();
            $table->timestamp('caterer_link_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique(['caterer_token']);
            $table->dropColumn(['caterer_token', 'caterer_link_expires_at']);
        });
    }
};
