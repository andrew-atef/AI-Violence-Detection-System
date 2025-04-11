<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('violence_notifications', function (Blueprint $table) {
            $table->id();
            $table->text('note')->nullable();
            $table->integer('camera_num')->default(1);
            $table->string('video_path');
            $table->string('prediction');
            $table->float('confidence');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('violence_notifications');
    }
};
