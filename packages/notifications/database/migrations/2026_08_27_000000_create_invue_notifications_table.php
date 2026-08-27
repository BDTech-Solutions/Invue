<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A separate table from Laravel's own built-in `notifications` —
        // deliberately: Invue\Notifications\Notification is its own
        // simpler, parallel concept (title/body/icon/color, not
        // Illuminate\Notifications\Notification's arbitrary `data` blob +
        // `type` class name), and colliding with an app that already uses
        // Laravel's native notifications would be a real footgun.
        Schema::create('invue_notifications', function (Blueprint $table) {
            $table->id();
            $table->morphs('notifiable');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->default('gray');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invue_notifications');
    }
};
