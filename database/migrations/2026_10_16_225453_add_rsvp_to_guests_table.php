<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->string('rsvp_status')->default('pending')->after('max_companions');
            $table->unsignedTinyInteger('companions_count')->default(0)->after('rsvp_status');
            $table->text('dietary_restrictions')->nullable()->after('companions_count');
            $table->string('favorite_song')->nullable()->after('dietary_restrictions');
            $table->text('rsvp_message')->nullable()->after('favorite_song');
            $table->timestamp('responded_at')->nullable()->after('rsvp_message');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn(['rsvp_status', 'companions_count', 'dietary_restrictions', 'favorite_song', 'rsvp_message', 'responded_at']);
        });
    }
};
