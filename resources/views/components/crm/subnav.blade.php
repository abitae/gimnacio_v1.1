@php
    $links = [
        ['route' => 'crm.pipeline', 'label' => 'Pipeline'],
        ['route' => 'crm.leads.index', 'label' => 'Leads', 'activeAlso' => ['crm.leads.show']],
        ['route' => 'crm.tareas', 'label' => 'Tareas'],
        ['route' => 'crm.deals', 'label' => 'Oportunidades'],
        ['route' => 'crm.campaigns', 'label' => 'Campañas', 'activeAlso' => ['crm.campaigns.show']],
        ['route' => 'crm.etiquetas', 'label' => 'Etiquetas'],
        ['route' => 'crm.reportes', 'label' => 'Reportes'],
        ['route' => 'crm.renovacion-reactivacion', 'label' => 'Renovación'],
    ];
@endphp

<nav aria-label="Navegación CRM" class="mb-1 flex flex-wrap gap-1 border-b border-zinc-200 dark:border-zinc-700 pb-2">
    @foreach ($links as $link)
        @php
            $patterns = array_merge([$link['route']], $link['activeAlso'] ?? []);
            $isActive = collect($patterns)->contains(fn ($p) => request()->routeIs($p));
        @endphp
        <a href="{{ route($link['route']) }}" wire:navigate
            @if ($isActive) aria-current="page" @endif
            class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ $isActive
                ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900'
                : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}">
            {{ $link['label'] }}
        </a>
    @endforeach
    @can('crm_mensaje.ver')
        <a href="{{ route('crm.mensajes') }}" wire:navigate
            @if (request()->routeIs('crm.mensajes')) aria-current="page" @endif
            class="rounded-lg px-3 py-1.5 text-sm font-medium transition {{ request()->routeIs('crm.mensajes')
                ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900'
                : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}">
            Mensajes
        </a>
    @endcan
</nav>
