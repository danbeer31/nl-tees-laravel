@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@push('scripts')
    @vite('resources/js/shop/product/product-show.js')
@endpush
