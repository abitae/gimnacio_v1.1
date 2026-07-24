<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="description" content="Fitness Center — Gimnasio con equipamiento moderno, clases grupales y entrenamiento personalizado.">

    <title>Fitness Center</title>

    <link rel="icon" href="{{ asset('img/web/logo.png') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('img/web/logo.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

    <style>
        :root {
            --fc-red: #c10d21;
            --fc-red-dark: #9a0a1a;
            --fc-red-light: #fde8eb;
            --fc-black: #0c0c0c;
            --fc-gray-900: #171717;
            --fc-gray-700: #404040;
            --fc-gray-500: #737373;
            --fc-gray-300: #d4d4d4;
            --fc-gray-100: #f5f5f5;
            --fc-white: #ffffff;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
            background: var(--fc-white);
            color: var(--fc-gray-900);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        @keyframes kenBurns {
            from { transform: scale(1.04); }
            to { transform: scale(1.1); }
        }

        .anim-fade-in-up { animation: fadeInUp 0.75s cubic-bezier(0.22, 1, 0.36, 1) both; }
        .anim-fade-in { animation: fadeIn 0.9s ease both; }
        .anim-delay-1 { animation-delay: 0.1s; }
        .anim-delay-2 { animation-delay: 0.22s; }
        .anim-delay-3 { animation-delay: 0.34s; }
        .anim-delay-4 { animation-delay: 0.46s; }
        .anim-float { animation: float 5s ease-in-out infinite; }
        .anim-float-delay { animation: float 5s ease-in-out 1s infinite; }
        .hero-bg-image { animation: kenBurns 20s ease-in-out infinite alternate; }

        .reveal {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity 0.65s ease, transform 0.65s cubic-bezier(0.22, 1, 0.36, 1);
        }
        .reveal.is-visible { opacity: 1; transform: translateY(0); }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background: var(--fc-red);
            color: var(--fc-white);
            font-weight: 600;
            transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 4px 14px rgba(193, 13, 33, 0.35);
        }
        .btn-primary:hover {
            background: var(--fc-red-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(193, 13, 33, 0.4);
        }

        .btn-outline-light {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            border: 2px solid rgba(255, 255, 255, 0.85);
            color: var(--fc-white);
            font-weight: 600;
            background: transparent;
            transition: background 0.2s ease, color 0.2s ease;
        }
        .btn-outline-light:hover {
            background: var(--fc-white);
            color: var(--fc-black);
        }

        .btn-outline-dark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            border: 2px solid var(--fc-gray-900);
            color: var(--fc-gray-900);
            font-weight: 600;
            background: transparent;
            transition: background 0.2s ease, color 0.2s ease;
        }
        .btn-outline-dark:hover {
            background: var(--fc-gray-900);
            color: var(--fc-white);
        }

        .section-label {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--fc-red);
        }

        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -16px rgba(0, 0, 0, 0.25);
        }

        .text-on-dark { color: var(--fc-white); }
        .text-muted-on-dark { color: #e4e4e7; }
        .text-subtle-on-dark { color: #a1a1aa; }

        .text-on-light { color: var(--fc-gray-900); }
        .text-muted-on-light { color: var(--fc-gray-700); }
        .text-subtle-on-light { color: var(--fc-gray-500); }

        @media (prefers-reduced-motion: reduce) {
            .anim-fade-in-up, .anim-fade-in, .anim-float, .anim-float-delay, .hero-bg-image {
                animation: none !important;
            }
            .reveal { opacity: 1; transform: none; transition: none; }
            .btn-primary:hover, .card-hover:hover { transform: none; }
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full antialiased">
    @php
        $gymName = 'Fitness Center';
        $web = 'img/web';
        $photos = [
            'gym1' => "{$web}/465035186_8773008999430202_2019562921522314047_n.jpeg",
            'gym2' => "{$web}/465055342_8772687276129041_5513433160172274761_n.jpeg",
            'gym3' => "{$web}/465167620_8773033516094417_6678840697859256812_n.jpeg",
            'gym4' => "{$web}/465174812_8772343832830052_2048947521416309355_n.jpeg",
            'logo' => "{$web}/logo.png",
        ];
    @endphp

    {{-- Header --}}
    <header id="site-header" class="fixed inset-x-0 top-0 z-50 anim-fade-in transition-[background,box-shadow] duration-300">
        <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between rounded-2xl border border-white/20 bg-black/75 px-4 py-3 shadow-lg backdrop-blur-md">
                <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-3">
                    <img src="{{ asset($photos['logo']) }}" alt="{{ $gymName }}" class="h-10 w-10 object-contain" />
                    <div class="hidden sm:block">
                        <div class="text-sm font-bold text-white">{{ $gymName }}</div>
                        <div class="text-xs font-medium text-zinc-300">Entrena · Supera · Transforma</div>
                    </div>
                </a>

                <nav class="flex items-center gap-1 sm:gap-2">
                    <a href="#servicios" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-white transition hover:bg-white/10 sm:inline">Servicios</a>
                    <a href="#instalaciones" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-white transition hover:bg-white/10 md:inline">Instalaciones</a>
                    <a href="#contacto" class="btn-primary px-4 py-2.5 text-sm sm:px-5">Únete hoy</a>
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="ml-1 hidden text-xs font-medium text-zinc-400 underline-offset-2 hover:text-white hover:underline sm:inline">Panel</a>
                        @else
                            <a href="{{ route('login') }}" class="ml-1 hidden text-xs font-medium text-zinc-400 underline-offset-2 hover:text-white hover:underline sm:inline">Staff</a>
                        @endauth
                    @endif
                </nav>
            </div>
        </div>
    </header>

    {{-- Hero --}}
    <section class="relative isolate min-h-[100svh] overflow-hidden bg-black pt-24">
        <div class="absolute inset-0">
            <img src="{{ asset($photos['gym1']) }}" alt="" aria-hidden="true" class="hero-bg-image h-full w-full object-cover opacity-70" />
        </div>
        <div class="absolute inset-0 bg-gradient-to-r from-black via-black/90 to-black/55"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black via-transparent to-black/40"></div>

        <div class="relative mx-auto grid max-w-7xl gap-10 px-4 pb-16 pt-6 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-14 lg:px-8 lg:pb-24 lg:pt-10">
            <div class="max-w-xl">
                <span class="anim-fade-in-up inline-flex items-center gap-2 rounded-full bg-[var(--fc-red)] px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-white">
                    Inscripciones abiertas
                </span>

                <h1 class="anim-fade-in-up anim-delay-1 mt-6 text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl lg:text-[3.4rem] lg:leading-[1.05]">
                    Bienvenido a<br>
                    <span class="text-[var(--fc-red)]">{{ $gymName }}</span>
                </h1>

                <p class="anim-fade-in-up anim-delay-2 mt-6 text-lg leading-relaxed text-zinc-200">
                    Equipamiento moderno, ambiente motivador y un equipo que te acompaña para alcanzar tus metas de fitness.
                </p>

                <div class="anim-fade-in-up anim-delay-3 mt-8 flex flex-wrap gap-3">
                    <a href="#contacto" class="btn-primary px-7 py-3.5 text-sm">Quiero inscribirme</a>
                    <a href="#servicios" class="btn-outline-light px-7 py-3.5 text-sm">Ver servicios</a>
                </div>

                <dl class="anim-fade-in-up anim-delay-4 mt-10 grid gap-3 sm:grid-cols-3">
                    @foreach ([
                        ['label' => 'Horario', 'value' => 'Lun – Sáb'],
                        ['label' => 'Zonas', 'value' => 'Cardio y pesas'],
                        ['label' => 'Clases', 'value' => 'Grupales'],
                    ] as $stat)
                        <div class="rounded-xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur-sm">
                            <dt class="text-[11px] font-semibold uppercase tracking-wider text-zinc-300">{{ $stat['label'] }}</dt>
                            <dd class="mt-0.5 text-base font-bold text-white">{{ $stat['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <div class="anim-fade-in anim-delay-2 relative mx-auto w-full max-w-md lg:max-w-none">
                <div class="relative aspect-[4/5] lg:aspect-[5/6]">
                    <div class="anim-float absolute inset-3 overflow-hidden rounded-3xl border-4 border-white/20 shadow-2xl">
                        <img src="{{ asset($photos['gym2']) }}" alt="Zona de entrenamiento {{ $gymName }}" class="h-full w-full object-cover" />
                    </div>
                    <div class="anim-float-delay absolute -bottom-3 left-0 w-32 overflow-hidden rounded-2xl border-4 border-white shadow-xl sm:w-40">
                        <img src="{{ asset($photos['gym3']) }}" alt="Instalaciones {{ $gymName }}" class="aspect-square w-full object-cover" />
                    </div>
                    <div class="absolute -right-1 top-6 rounded-2xl border-4 border-white bg-white p-2 shadow-xl sm:top-10 sm:p-3">
                        <img src="{{ asset($photos['logo']) }}" alt="{{ $gymName }}" class="h-16 w-16 object-contain sm:h-20 sm:w-20" />
                    </div>
                    <div class="absolute bottom-20 right-0 rounded-xl bg-[var(--fc-red)] px-4 py-3 shadow-lg">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-white/90">Comunidad</div>
                        <div class="text-2xl font-bold text-white">+500</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Instalaciones gallery --}}
    <section id="instalaciones" class="bg-[var(--fc-gray-100)] py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="reveal mx-auto max-w-2xl text-center">
                <span class="section-label">Instalaciones</span>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-[var(--fc-gray-900)] sm:text-4xl">Conoce nuestro espacio</h2>
                <p class="mt-4 text-lg leading-relaxed text-[var(--fc-gray-700)]">
                    Ambientes amplios, equipos de calidad y la energía que necesitas para dar lo mejor en cada sesión.
                </p>
            </div>

            <div class="reveal mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:gap-5">
                @foreach ([
                    ['img' => $photos['gym1'], 'label' => 'Musculación', 'desc' => 'Zona de pesas completa'],
                    ['img' => $photos['gym2'], 'label' => 'Entrenamiento', 'desc' => 'Equipos modernos'],
                    ['img' => $photos['gym3'], 'label' => 'Clases grupales', 'desc' => 'Sesiones dinámicas'],
                    ['img' => $photos['gym4'], 'label' => 'Ambiente', 'desc' => 'Espacio motivador'],
                ] as $item)
                    <figure class="card-hover group overflow-hidden rounded-2xl bg-white shadow-md ring-1 ring-black/5">
                        <div class="aspect-[4/5] overflow-hidden">
                            <img
                                src="{{ asset($item['img']) }}"
                                alt="{{ $item['label'] }} — {{ $gymName }}"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                            />
                        </div>
                        <figcaption class="border-t border-zinc-100 px-4 py-3">
                            <div class="font-bold text-[var(--fc-gray-900)]">{{ $item['label'] }}</div>
                            <div class="text-sm text-[var(--fc-gray-700)]">{{ $item['desc'] }}</div>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Por qué entrenar --}}
    <section class="bg-white py-16 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="reveal mx-auto max-w-2xl text-center">
                <span class="section-label">Ventajas</span>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-[var(--fc-gray-900)] sm:text-4xl">Por qué entrenar en {{ $gymName }}</h2>
                <p class="mt-4 text-lg leading-relaxed text-[var(--fc-gray-700)]">
                    Más que un gimnasio: un espacio pensado para que rindas al máximo y disfrutes cada sesión.
                </p>
            </div>

            <div class="mt-14 space-y-16 lg:space-y-24">
                <article class="reveal grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                    <div class="overflow-hidden rounded-3xl shadow-xl ring-1 ring-black/10">
                        <img src="{{ asset($photos['gym4']) }}" alt="Equipos en {{ $gymName }}" class="aspect-[4/3] w-full object-cover" />
                    </div>
                    <div>
                        <span class="section-label">Entrenamiento</span>
                        <h3 class="mt-2 text-2xl font-bold text-[var(--fc-gray-900)] sm:text-3xl">Equipos modernos y espacios amplios</h3>
                        <p class="mt-4 leading-relaxed text-[var(--fc-gray-700)]">
                            Zona de musculación, cardio y funcional con máquinas de última generación. Entrena a tu ritmo con todo lo que necesitas a mano.
                        </p>
                        <ul class="mt-6 space-y-3">
                            @foreach (['Pesas libres y máquinas guiadas', 'Zona cardio completa', 'Ambiente limpio y seguro'] as $point)
                                <li class="flex items-start gap-3 text-[var(--fc-gray-900)]">
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[var(--fc-red)] text-xs font-bold text-white">✓</span>
                                    {{ $point }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </article>

                <article class="reveal grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                    <div class="overflow-hidden rounded-3xl shadow-xl ring-1 ring-black/10 lg:order-2">
                        <img src="{{ asset($photos['gym3']) }}" alt="Clases en {{ $gymName }}" class="aspect-[4/3] w-full object-cover" />
                    </div>
                    <div class="lg:order-1">
                        <span class="section-label">Comunidad</span>
                        <h3 class="mt-2 text-2xl font-bold text-[var(--fc-gray-900)] sm:text-3xl">Clases, energía y acompañamiento</h3>
                        <p class="mt-4 leading-relaxed text-[var(--fc-gray-700)]">
                            Entrena solo o en grupo. Nuestro equipo te orienta desde el primer día para que avances con confianza y constancia.
                        </p>
                        <ul class="mt-6 space-y-3">
                            @foreach (['Clases grupales motivadoras', 'Asesoría en sala', 'Planes según tu objetivo'] as $point)
                                <li class="flex items-start gap-3 text-[var(--fc-gray-900)]">
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[var(--fc-red)] text-xs font-bold text-white">✓</span>
                                    {{ $point }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- Servicios --}}
    <section id="servicios" class="bg-[var(--fc-black)] py-16 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="reveal max-w-2xl">
                <span class="section-label text-[#ff6b7a]">Servicios</span>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-white sm:text-4xl">Todo para tu rutina</h2>
                <p class="mt-4 text-lg leading-relaxed text-zinc-300">
                    Lo que necesitas para empezar y mantener tu entrenamiento en {{ $gymName }}.
                </p>
            </div>

            <div class="mt-12 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['title' => 'Musculación', 'desc' => 'Zona de pesas y máquinas para fuerza, hipertrofia y tonificación.', 'icon' => 'M'],
                    ['title' => 'Cardio', 'desc' => 'Cintas, bicicletas y elípticas para resistencia y quema de grasa.', 'icon' => 'C'],
                    ['title' => 'Clases grupales', 'desc' => 'Sesiones dinámicas con música y energía para entrenar en equipo.', 'icon' => 'G'],
                    ['title' => 'Asesoría', 'desc' => 'Orientación en sala para usar bien el equipamiento y progresar.', 'icon' => 'A'],
                ] as $i => $service)
                    <article
                        class="reveal card-hover rounded-2xl border border-zinc-800 bg-zinc-900 p-6"
                        style="transition-delay: {{ $i * 70 }}ms"
                    >
                        <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-[var(--fc-red)] text-lg font-bold text-white">
                            {{ $service['icon'] }}
                        </div>
                        <h3 class="text-lg font-bold text-white">{{ $service['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-300">{{ $service['desc'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section id="contacto" class="relative overflow-hidden bg-[var(--fc-red)] py-16 sm:py-20">
        <div class="absolute inset-0 opacity-10" aria-hidden="true">
            <img src="{{ asset($photos['gym2']) }}" alt="" class="h-full w-full object-cover" />
        </div>
        <div class="relative mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
            <div class="reveal">
                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Empieza hoy en {{ $gymName }}</h2>
                <p class="mx-auto mt-4 max-w-xl text-lg leading-relaxed text-white/95">
                    Visítanos, conoce nuestras instalaciones y elige el plan que mejor se adapte a ti. Tu transformación empieza con un primer paso.
                </p>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <a href="#contacto" class="inline-flex items-center rounded-full bg-white px-7 py-3.5 text-sm font-bold text-[var(--fc-red)] transition hover:bg-zinc-100">
                        Solicitar información
                    </a>
                    <a href="#instalaciones" class="btn-outline-light px-7 py-3.5 text-sm">Ver instalaciones</a>
                </div>
                <p class="mt-8 text-sm font-medium text-white/90">
                    Pregunta en recepción por membresías, horarios y promociones vigentes.
                </p>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-zinc-800 bg-[var(--fc-black)] px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto flex max-w-7xl flex-col items-center gap-6 sm:flex-row sm:justify-between">
            <div class="flex items-center gap-3">
                <img src="{{ asset($photos['logo']) }}" alt="{{ $gymName }}" class="h-10 w-10 object-contain" />
                <div>
                    <div class="font-bold text-white">{{ $gymName }}</div>
                    <div class="text-sm text-zinc-400">&copy; {{ date('Y') }} Todos los derechos reservados</div>
                </div>
            </div>
            <p class="text-center text-sm font-medium text-zinc-300">Entrena · Supera · Transforma</p>
            @if (Route::has('login') && ! auth()->check())
                <a href="{{ route('login') }}" class="text-sm font-medium text-zinc-400 underline-offset-2 transition hover:text-white hover:underline">Acceso staff</a>
            @endif
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.reveal').forEach((el) => {
                new IntersectionObserver(
                    ([entry], obs) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            obs.unobserve(entry.target);
                        }
                    },
                    { threshold: 0.1, rootMargin: '0px 0px -32px 0px' }
                ).observe(el);
            });
        });
    </script>
</body>
</html>
