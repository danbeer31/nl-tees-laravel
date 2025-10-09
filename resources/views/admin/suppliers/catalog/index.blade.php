@extends('layouts.app')
@section('title','Catalog')

@section('content')
    <div class="container py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h4 mb-0">Catalog</h1>
            <div class="btn-group">
                <a href="{{ route('admin.ss.index') }}" class="btn btn-outline-primary">Import from S&S</a>
                <a href="{{ route('admin.sanmar.index') }}" class="btn btn-outline-primary">Import from SanMar</a>
            </div>
        </div>

        <form class="row g-2 mb-3" method="get" action="{{ route('admin.catalog.index') }}">
            <div class="col-sm-6 col-md-4">
                <input type="search" name="q" value="{{ $q }}" class="form-control"
                       placeholder="Search title, slug, supplier">
            </div>
            <div class="col-sm-3 col-md-2">
                <button class="btn btn-secondary w-100">Search</button>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="width:64px">Img</th>
                        <th>Title</th>
                        <th>Supplier</th>
                        <th class="text-center">Colors</th>
                        <th class="text-center">Sizes</th>
                        <th>Created</th>
                        <th style="width:220px">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($products as $p)
                        @php
                            $img = $p->image_url ?: asset('images/placeholder.png');
                            $sizeTotal = $p->colors->sum(fn($c) => $c->sizes_count ?? 0);
                        @endphp
                        <tr>
                            <td>
                                <img src="{{ $img }}" alt="{{ $p->title }}" class="rounded"
                                     style="width:48px;height:48px;object-fit:cover">
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $p->title }}</div>
                                <div class="text-muted small">{{ $p->slug }}</div>
                            </td>
                            <td>{{ $p->supplier ?? '—' }}</td>
                            <td class="text-center">{{ $p->colors_count }}</td>
                            <td class="text-center">{{ $sizeTotal }}</td>
                            <td class="text-muted">{{ $p->created_at?->format('Y-m-d') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ url('/admin/products/'.$p->id) }}" class="btn btn-outline-secondary">View</a>
                                    <a href="{{ url('/admin/products/'.$p->id.'/edit') }}"
                                       class="btn btn-outline-primary">Edit</a>
                                    <form method="POST" action="{{ url('/admin/products/'.$p->id) }}"
                                          onsubmit="return confirm('Delete this product?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No products yet.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $products->links() }}
            </div>
        </div>
    </div>
@endsection
