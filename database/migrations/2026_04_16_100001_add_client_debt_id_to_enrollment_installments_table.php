<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_installments', function (Blueprint $table) {
            $table->foreignId('client_debt_id')
                ->nullable()
                ->after('enrollment_installment_plan_id')
                ->constrained('client_debts')
                ->nullOnDelete();
            $table->index('client_debt_id');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_installments', function (Blueprint $table) {
            $table->dropForeign(['client_debt_id']);
            $table->dropIndex(['client_debt_id']);
            $table->dropColumn('client_debt_id');
        });
    }
};
