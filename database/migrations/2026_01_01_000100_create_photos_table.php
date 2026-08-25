<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->string('guest_name')->nullable();
            $table->string('caption', 500)->nullable();
            $table->string('original_path');
            $table->string('thumb_path');
            $table->string('mime', 100);
            $table->unsignedBigInteger('bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('upload_token', 64)->nullable()->index();
            $table->ipAddress('ip')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->index(['is_hidden', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
