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
        Schema::create("document_fields", function (Blueprint $table) {
            $table->id();
            $table->foreignId("document_id")->constrained("documents")->onDelete("cascade");
            $table->string("name");
            $table->string("label");
            $table->enum("type", ["text", "number", "date", "textarea"])->default("text");
            $table->boolean("required")->default(false);
            $table->string("placeholder")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("document_fields");
    }
};