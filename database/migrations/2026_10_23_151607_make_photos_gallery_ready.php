<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les photos de la galerie sont publiées par l'hôte : elles n'ont pas d'invité auteur (`guest_id` nul)
 * et gardent une miniature pour la grille.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->foreignId('guest_id')->nullable()->change();
            $table->string('thumb_path')->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('thumb_path');
        });
    }
};
