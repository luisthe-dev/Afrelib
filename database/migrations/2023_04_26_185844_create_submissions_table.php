<?php

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
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->integer('project_id');
            $table->integer('submitter_id');
            $table->string('submission_title');
            $table->string('submission_url')->nullable();
            $table->longText('submission_comment');
            $table->longText('submission_attachments');
            $table->integer('submission_week');
            $table->longText('panelist_feedback');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
