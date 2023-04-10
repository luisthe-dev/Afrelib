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
        Schema::create('cohorts', function (Blueprint $table) {
            $table->id();
            $table->string('cohort_id');
            $table->string('cohort_name');
            $table->longText('cohort_description');
            $table->longText('cohort_teams');
            $table->longText('cohort_mentors');
            $table->longText('cohort_panelists');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('Inactive');
            $table->boolean('is_deleted')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cohorts');
    }
};
