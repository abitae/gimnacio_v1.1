<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\BioTime\BioTimeSucursalSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyBioTimeSyncToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $provided = $request->bearerToken() ?: $request->header('X-BioTime-Secret');

        if (! filled($provided)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $setting = BioTimeSucursalSetting::findBySecret((string) $provided);

        if (! $setting instanceof BioTimeSucursalSetting) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (! $setting->enabled) {
            return response()->json([
                'message' => 'BioTime sync disabled for this sucursal',
                'sucursal_id' => $setting->sucursal_id,
            ], 403);
        }

        $request->attributes->set('biotime_sucursal_id', (int) $setting->sucursal_id);
        $request->attributes->set('biotime_sucursal_setting_id', (int) $setting->id);
        $request->attributes->set('biotime_sucursal_setting', $setting);

        return $next($request);
    }
}
