<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'NL-Tees'))</title>

    {{-- Bootstrap ONLY (no Tailwind, no Vite) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @yield('head')
</head>
<body>
{{-- If you still have a Tailwind/Breeze nav, it will look plain under Bootstrap.
     Use the Bootstrap nav I sent earlier, or keep your nav but expect different styles. --}}
@includeIf('layouts.navigation')

<main class="py-4">
    @yield('content')
    {{ $slot ?? '' }}
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
@stack('scripts')
</body>
</html>
