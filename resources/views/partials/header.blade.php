<header>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand" href="{{ route('home') }}">
                <img src="{{ asset('images/logo.png') }}" alt="My Logo" width="150">
            </a>
            <!-- Toggle button for mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                    aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <!-- Navbar links -->
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="{{ route('home') }}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="">Shop</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="">Contact</a>
                    </li>
                </ul>
                <!-- Shopping Cart -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="">
                            <i class="fas fa-shopping-cart"></i> Cart
                            {{-- Optionally, you can display the cart count here: --}}
                            {{-- <span class="badge bg-danger">{{ session('cart') ? count(session('cart')) : 0 }}</span> --}}
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>
