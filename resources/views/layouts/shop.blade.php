<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', $Title ?? config('app.name'))</title>
    {{-- Quick bootstrap via CDN for speed today; we can switch to Vite later --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>


<body class="bg-light">
@include('shop.partials.header')


<main class="container mb-5">
    {{-- Fuel: $this->template->content --}}
    {{-- Laravel: yield content sections --}}
    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
