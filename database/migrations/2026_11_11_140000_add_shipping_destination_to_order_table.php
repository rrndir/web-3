<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order', function (Blueprint $table) {
            $table->string('destination_province_id', 30)->nullable();
            $table->string('destination_province_name')->nullable();
            $table->string('destination_city_id', 30)->nullable();
            $table->string('destination_city_name')->nullable();
            $table->string('shipping_service_code', 80)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order', function (Blueprint $table) {
            $table->dropColumn([
                'destination_province_id',
                'destination_province_name',
                'destination_city_id',
                'destination_city_name',
                'shipping_service_code',
            ]);
        });
    }
};
