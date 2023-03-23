$<?php

use App\Models\Album;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('image_manipulations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('path', 2000);
            $table->string('type', 25);
            $table->text('data');
            $table->string('output_path', 2000)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->foreignIdFor(\App\Models\User::class, 'user_id')->nullable();
            $table->foreignIdFor(App\Models\Album::class, 'album_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_manipulations');
    }
};
