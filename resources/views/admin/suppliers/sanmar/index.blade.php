@extends('layouts.admin')

@section('title', $Title ?? 'SanMar Catalog')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">SanMar Catalog</h1>
        @if(session('success'))
            <div class="alert alert-success py-1 px-2 mb-0">{{ session('success') }}</div>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.sanmar.search') }}" class="mb-3">
        @csrf
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search by style (e.g., 5000, PC54)" value="{{ $term ?? '' }}">
            <button class="btn btn-primary">Search</button>
        </div>
    </form>

    @isset($results)
        <div class="row g-3">
            @forelse($results as $r)
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        @if(!empty($r['image'])) <img src="{{ $r['image'] }}" class="card-img-top" alt=""> @endif
                        <div class="card-body">
                            <div class="small text-muted">{{ $r['brand'] }}</div>
                            <h5 class="card-title">{{ $r['title'] }}</h5>
                            <div class="mb-2"><code>{{ $r['style'] }}</code></div>
                            <form method="POST" action="{{ route('admin.sanmar.import') }}">
                                @csrf
                                <input type="hidden" name="style" value="{{ $r['style'] }}">
                                <button class="btn btn-success btn-sm">Import</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="alert alert-warning">No results.</div></div>
            @endforelse
        </div>
    @endisset
@endsection

