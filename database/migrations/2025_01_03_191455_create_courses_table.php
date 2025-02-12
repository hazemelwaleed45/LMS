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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->integer('duration');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('rate', 2, 1)->default(0.0);
            $table->string('image')->nullable();
            $table->decimal('admin_amount', 10, 2);
            $table->decimal('instructor_amount', 10, 2);
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('instructor_id');
            $table->string('major')->nullable();
            $table->string('prerequister')->nullable();
            $table->enum('semster', ['semster1', 'semster2']);
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('instructor_id')->references('id')->on('instructors')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};