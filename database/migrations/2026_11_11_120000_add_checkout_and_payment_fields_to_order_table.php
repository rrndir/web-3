<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order', function (Blueprint $table) {
            $table->string('telepon_penerima', 30)->nullable();
            $table->string('payment_status')->default('unpaid');
            $table->string('payment_proof_path')->nullable();
            $table->timestamp('payment_submitted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('order', function (Blueprint $table) {
            $table->dropColumn([
                'telepon_penerima',
                'payment_status',
                'payment_proof_path',
                'payment_submitted_at',
                'paid_at',
                'shipped_at',
                'completed_at',
            ]);
        });
    }
};
