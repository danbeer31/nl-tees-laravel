@extends('layouts.app')

@section('title', $product->title ?? 'Product')

@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">

@endsection

@section('content')
    <div class="container py-4">
        <div class="row g-4">
            {{-- Left: gallery --}}
            <div class="col-12 col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        @php
                            $firstColor   = $product->colors->first();
                            $initialImgs  = optional($firstColor)->images ?? collect();
                            $hero         = $initialImgs->first();
                            $basePrice    = (float)($product->price ?? 0);
                            $colorDelta   = (float)optional($firstColor)->price_difference ?? 0;
                            $initialPrice = $basePrice + $colorDelta;
                        @endphp

                        <img id="hero-image"
                             src="{{ $hero->url ?? $hero->path ?? asset('images/placeholder.png') }}"
                             alt="{{ $hero->alt ?? $product->title }}"
                             class="img-fluid rounded mb-3"
                             style="max-height:460px;object-fit:contain;width:100%;">

                        <div id="thumbs" class="d-flex flex-wrap gap-2 justify-content-center">
                            @foreach($initialImgs as $img)
                                <img src="{{ $img->url ?? $img->path }}"
                                     alt="{{ $img->alt ?? $product->title }}"
                                     class="border rounded"
                                     style="width:72px;height:72px;object-fit:cover;cursor:pointer;">
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: details --}}
            <div class="col-12 col-md-6">
                <h1 class="fw-bold mb-2">{{ $product->title }}</h1>
                @if(!empty($product->subtitle))
                    <p class="text-muted">{{ $product->subtitle }}</p>
                @endif

                <div class="mb-3">
                    <span class="h3" id="price-display">${{ number_format($initialPrice, 2) }}</span>
                </div>

                {{-- Color swatches --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold">Color</label>
                    <div id="color-swatch-grid" class="d-flex flex-wrap gap-2">
                        @foreach($product->colors as $c)
                            @php $active = optional($firstColor)->id === $c->id; @endphp
                            <button type="button"
                                    class="color-swatch btn p-0 border {{ $active ? 'ring-2' : '' }}"
                                    data-color-id="{{ $c->id }}"
                                    data-color-name="{{ $c->name }}"
                                    data-color-hex="{{ $c->hex }}"
                                    style="width:36px;height:36px;border-radius:50%;background:{{ $c->hex ?? '#ccc' }};outline:none;">
                            </button>
                        @endforeach
                    </div>
                    <div class="small text-muted mt-2">
                        <span id="active-color-name">{{ optional($firstColor)->name }}</span>
                    </div>
                </div>

                {{-- Sizes --}}
                <div class="mb-3">
                    <label for="size-select" class="form-label fw-semibold">Size</label>
                    <select id="size-select" class="form-select"
                        @disabled(!$firstColor || $firstColor->sizes->isEmpty())>
                        @forelse($firstColor?->sizes ?? [] as $s)
                            @php
                                $label      = $s->label ?? $s->name ?? 'Size';
                                $deltaCents = (int)($s->pivot->price_diff_cents ?? 0);
                                $delta      = $deltaCents / 100;
                                $inStock    = is_null($s->pivot->stock_qty) ? true : ($s->pivot->stock_qty > 0);
                            @endphp
                            <option value="{{ $s->id }}"
                                    data-price-delta-cents="{{ $deltaCents }}"
                                    data-price-delta="{{ number_format($delta, 2, '.', '') }}"
                                @disabled(!$inStock)>
                                {{ $label }}
                                @if($deltaCents !== 0)
                                    ({{ $deltaCents > 0 ? '+' : '' }}${{ number_format($delta, 2) }})
                                @endif
                                @unless($inStock) — Out of stock @endunless
                            </option>
                        @empty
                            <option value="">Select a color first</option>
                        @endforelse
                    </select>
                </div>


                {{-- Qty + actions --}}
                <div class="mb-3">
                    <label for="qty" class="form-label fw-semibold">Quantity</label>
                    <input type="number" id="qty" class="form-control" value="1" min="1" step="1" style="max-width:160px;">
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" id="btn-add-to-cart">Add to Cart</button>
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary">Back</a>
                </div>

                {{-- Hidden state --}}
                <input type="hidden" id="product-id" value="{{ $product->id }}">
                <input type="hidden" id="base-price" value="{{ (float)($product->price ?? 0) }}">
                <input type="hidden" id="active-color-id" value="{{ optional($firstColor)->id }}">
                <input type="hidden" id="active-color-delta" value="{{ $colorDelta }}">
            </div>
        </div>
    </div>
    <script type="application/json" id="images-by-color">
        {!! json_encode($imagesByColor, JSON_UNESCAPED_SLASHES) !!}
    </script>
    <script src="/public/js/shop/product/product-show.js?ti=2" defer></script>
@endsection

