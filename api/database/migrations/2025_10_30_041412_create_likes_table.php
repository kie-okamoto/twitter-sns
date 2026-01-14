<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->string('user_id'); // Firebase UID
            $table->timestamps();

            $table->unique(['post_id', 'user_id']); // 同一ユーザーの重複いいね防止
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};
