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
        // The customer table is already created by the 2024 customer migration.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This legacy duplicate migration does not own the customer table.
    }
};
