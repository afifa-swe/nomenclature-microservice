<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('user_id')->index();

            $table->string('entity_type')->index();
            $table->uuid('entity_id')->index();

            $table->string('action')->index();

            $table->jsonb('changes');

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_logs');
    }
};
