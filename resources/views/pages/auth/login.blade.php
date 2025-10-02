<!DOCTYPE html>
<html lang="es" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalyx - Inicia Sesión</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full flex items-center justify-center bg-gradient-to-br from-emerald-500 to-emerald-300">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">
        <!-- Logo -->
        <div class="flex justify-center mb-4">
            <img src="/img/logo-kalyx.png" alt="Logo Kalyx" class="h-12 w-12">
        </div>

        <!-- Título -->
        <h1 class="text-2xl font-semibold mb-6 text-center text-emerald-600">Kalyx</h1>

        <!-- Alertas -->
        @if (session('status'))
            <div class="mb-4 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
                {{ session('status') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Formulario (POST clásico) -->
        <form method="POST" action="{{ route('auth.login') }}" class="space-y-4">
            @csrf

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Correo electrónico</label>
                <input type="email" name="email" value="{{ old('email') }}"
                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2
                           focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    placeholder="admin@kalix.local" required autofocus>
            </div>

            <!-- Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Contraseña</label>
                <input type="password" name="password"
                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2
                           focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    placeholder="••••••••" required>
            </div>

            <!-- Remember -->
            <div class="flex items-center justify-between">
                <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remember" class="rounded border-gray-300">
                    Recuérdame
                </label>
                <!-- (opcional) enlace recuperar -->
                {{-- <a href="#" class="text-sm text-emerald-600 hover:underline">¿Olvidaste tu contraseña?</a> --}}
            </div>

            <!-- Botón -->
            <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2
                       bg-emerald-600 hover:bg-emerald-700 text-white font-medium
                       rounded-lg shadow transition">
                → Iniciar sesión
            </button>
        </form>

        <!-- Texto de ayuda -->
        <p class="mt-4 text-xs text-center text-gray-500">De datos a decisiones.</p>
    </div>
</body>

</html>
