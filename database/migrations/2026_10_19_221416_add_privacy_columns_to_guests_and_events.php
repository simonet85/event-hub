<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consentement (donnée de santé : régime alimentaire) et suppression des données personnelles des invités.
     */
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->timestamp('consent_at')->nullable();
            $table->timestamp('erased_at')->nullable();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('guest_data_purged_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn(['consent_at', 'erased_at']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('guest_data_purged_at');
        });
    }
};
