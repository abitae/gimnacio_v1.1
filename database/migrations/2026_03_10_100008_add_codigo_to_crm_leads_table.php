<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->string('codigo', 30)->nullable()->after('id');
        });

        $year = now()->format('Y');
        $leads = DB::table('crm_leads')->whereNull('codigo')->orderBy('id')->get();
        $num = 0;
        foreach ($leads as $lead) {
            $num++;
            $codigo = sprintf('LEAD-%s-%04d', $year, $num);
            DB::table('crm_leads')->where('id', $lead->id)->update(['codigo' => $codigo]);
        }

        Schema::table('crm_leads', function (Blueprint $table) {
            $table->unique('codigo');
        });
    }

    public function down(): void
    {
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropUnique(['codigo']);
        });
        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropColumn('codigo');
        });
    }
};
