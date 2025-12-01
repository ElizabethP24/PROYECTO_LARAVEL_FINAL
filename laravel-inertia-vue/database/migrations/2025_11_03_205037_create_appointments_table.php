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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id('id_appointment');
            $table->foreignId('id_patient')->constrained('patients', 'id_patient')->onDelete('cascade');
            $table->foreignId('id_doctor')->constrained('doctors', 'id_doctor')->onDelete('cascade');
            $table->foreignId('id_specialty')->constrained('specialties', 'id_specialty')->onDelete('cascade');
            $table->string('slug')->unique();
            $table->date('date');
            $table->time('time');
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->timestamps();

            $table->unique(['id_doctor', 'date', 'time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
