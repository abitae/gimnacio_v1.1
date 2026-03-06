@props(['evaluacion'])

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Silueta Humana -->
    <div class="flex items-center justify-center">
        <div class="w-32 h-64 relative">
            <!-- Silueta SVG simplificada -->
            <svg viewBox="0 0 100 200" class="w-full h-full text-zinc-300 dark:text-zinc-600">
                <!-- Cabeza -->
                <ellipse cx="50" cy="15" rx="12" ry="15" fill="currentColor" />
                <!-- Cuello -->
                <rect x="45" y="30" width="10" height="8" fill="currentColor" />
                <!-- Torso -->
                <ellipse cx="50" cy="60" rx="20" ry="25" fill="currentColor" />
                <!-- Brazo izquierdo -->
                <ellipse cx="25" cy="55" rx="8" ry="20" fill="currentColor" />
                <!-- Brazo derecho -->
                <ellipse cx="75" cy="55" rx="8" ry="20" fill="currentColor" />
                <!-- Pierna izquierda -->
                <ellipse cx="42" cy="120" rx="10" ry="35" fill="currentColor" />
                <!-- Pierna derecha -->
                <ellipse cx="58" cy="120" rx="10" ry="35" fill="currentColor" />
            </svg>
        </div>
    </div>

    <!-- Valores de Circunferencias -->
    <div class="space-y-1.5">
        @php
            $circunferencias = $evaluacion->circunferencias ?? [];
        @endphp

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Estatura</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ isset($circunferencias['estatura']) && $circunferencias['estatura'] > 0 ? number_format($circunferencias['estatura'], 1) . ' cm' : '-' }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Cuello</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ isset($circunferencias['cuello']) && $circunferencias['cuello'] > 0 ? number_format($circunferencias['cuello'], 1) . ' cm' : '0cm' }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Brazo Normal</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ isset($circunferencias['brazo_normal']) && $circunferencias['brazo_normal'] > 0 ? number_format($circunferencias['brazo_normal'], 1) . ' cm' : '0cm' }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Brazo Contraído</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ isset($circunferencias['brazo_contraido']) && $circunferencias['brazo_contraido'] > 0 ? number_format($circunferencias['brazo_contraido'], 1) . ' cm' : '0cm' }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Circunferencia. tórax</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ isset($circunferencias['torax']) && $circunferencias['torax'] > 0 ? number_format($circunferencias['torax'], 1) . ' cm' : '0cm' }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Cintura</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ isset($circunferencias['cintura']) && $circunferencias['cintura'] > 0 ? number_format($circunferencias['cintura'], 1) . ' cm' : '0cm' }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Cintura baja</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ isset($circunferencias['cintura_baja']) && $circunferencias['cintura_baja'] > 0 ? number_format($circunferencias['cintura_baja'], 1) . ' cm' : '0cm' }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Cadera</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ isset($circunferencias['cadera']) && $circunferencias['cadera'] > 0 ? number_format($circunferencias['cadera'], 1) . ' cm' : '0cm' }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Muslo</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ isset($circunferencias['muslo']) && $circunferencias['muslo'] > 0 ? number_format($circunferencias['muslo'], 1) . ' cm' : '0cm' }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Glúteos</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ isset($circunferencias['gluteos']) && $circunferencias['gluteos'] > 0 ? number_format($circunferencias['gluteos'], 1) . ' cm' : '0cm' }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Pantorrilla</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ isset($circunferencias['pantorrilla']) && $circunferencias['pantorrilla'] > 0 ? number_format($circunferencias['pantorrilla'], 1) . ' cm' : '0cm' }}
            </p>
        </div>
    </div>
</div>
