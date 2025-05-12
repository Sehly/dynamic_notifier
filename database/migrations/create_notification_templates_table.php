<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->string('channel');
            $table->string('header')->nullable();
            $table->text('body');
            $table->boolean('is_batchable')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('notification_templates');
    }
};
