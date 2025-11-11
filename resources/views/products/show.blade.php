@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">{{ $product->name }}</h4>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">Back</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    @if ($product->image_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}"
                            alt="{{ $product->name }}" class="img-fluid rounded">
                    @else
                        <div class="border rounded d-flex align-items-center justify-content-center bg-light"
                            style="height: 200px;">
                            <span class="text-muted">No image</span>
                        </div>
                    @endif
                </div>
                <div class="col-md-8">
                    <p><strong>Price:</strong> ${{ number_format($product->price, 2) }}</p>
                    <p><strong>Status:</strong>
                        <span
                            class="badge rounded-pill {{ $product->status ? 'bg-success' : 'bg-secondary' }}">{{ $product->status ? 'Active' : 'Inactive' }}</span>
                    </p>
                    <p><strong>Detail:</strong></p>
                    <p class="mb-0">{{ $product->detail ?? '—' }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
