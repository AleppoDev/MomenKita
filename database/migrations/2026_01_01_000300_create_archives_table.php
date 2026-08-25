<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archives', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('batch');
            $table->string('status', 20)->default('pending');
            $table->string('path')->nullable();
            $table->unsignedBigInteger('bytes')->nullable();
            $table->unsignedInteger('photo_count')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archives');
    }
};
