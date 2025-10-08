@extends('layouts.shop')

@section('title', $Title ?? 'Home')

@section('content')
    <div class="row g-3">
        @foreach($products as $p)
            @php
                // Prefer product-level image; fallback to a neutral placeholder
                $img = $p->image_url ?: 'https://placehold.co/600x600?text=No+Image';
            @endphp

            <div class="col-md-4">
                <div class="card h-100 shadow-sm">
                    {{-- Image --}}
                    <img
                        src="{{ $img }}"
                        alt="{{ $p->title }}"
                        class="card-img-top"
                        loading="lazy"
                        style="height: 220px; object-fit: contain; background: #fff;"
                    >

                    <div class="card-body">
                        <h5 class="card-title mb-1">{{ $p->title }}</h5>
                        <div class="text-muted small mb-3">
                            ${{ number_format($p->base_price_cents/100, 2) }}
                        </div>
                        <a class="btn btn-primary" href="{{ route('product.show', $p) }}">View</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
