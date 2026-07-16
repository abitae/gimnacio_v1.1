<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bio_time_sucursal_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->unique()->constrained('sucursales')->cascadeOnDelete();
            $table->text('webhook_secret')->nullable();
            $table->unsignedBigInteger('area_biotime_id')->nullable()->index();
            $table->string('biotime_base_url')->nullable();
            $table->unsignedInteger('poll_interval_seconds')->default(3600);
            $table->boolean('enabled')->default(true)->index();
            $table->timestamp('last_received_at')->nullable()->index();
            $table->timestamp('last_heartbeat_at')->nullable()->index();
            $table->timestamps();
        });

        $legacySecret = $this->resolveLegacySecret();

        $sucursales = DB::table('sucursales')->select(['id', 'es_principal'])->orderBy('id')->get();
        $principalId = $sucursales->firstWhere('es_principal', true)?->id
            ?? $sucursales->first()?->id;

        $now = now();

        foreach ($sucursales as $sucursal) {
            $secret = null;
            if ($principalId !== null && (int) $sucursal->id === (int) $principalId && filled($legacySecret)) {
                // Re-encrypt so Eloquent encrypted cast can decrypt consistently.
                $secret = Crypt::encryptString((string) $legacySecret);
            }

            DB::table('bio_time_sucursal_settings')->insert([
                'sucursal_id' => $sucursal->id,
                'webhook_secret' => $secret,
                'area_biotime_id' => null,
                'biotime_base_url' => null,
                'poll_interval_seconds' => 3600,
                'enabled' => true,
                'last_received_at' => null,
                'last_heartbeat_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_time_sucursal_settings');
    }

    private function resolveLegacySecret(): ?string
    {
        if (Schema::hasTable('bio_time_settings')) {
            $row = DB::table('bio_time_settings')->orderBy('id')->first();
            if ($row && filled($row->webhook_secret ?? null)) {
                $raw = (string) $row->webhook_secret;
                try {
                    return Crypt::decryptString($raw);
                } catch (\Throwable) {
                    // Valor plano histórico o cifrado con otra APP_KEY.
                    return $raw;
                }
            }
        }

        $env = env('BIOTIME_WEBHOOK_SECRET');

        return filled($env) ? (string) $env : null;
    }
};
