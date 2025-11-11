@php
    use Illuminate\Support\Facades\Storage;
@endphp

@forelse ($products as $product)
    <tr>
        <td>{{ $product->id }}</td>
        <td>
            @if ($product->image_path)
                <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="rounded"
                    width="64" height="64">
            @else
                <span class="text-muted">No image</span>
            @endif
        </td>
        <td>{{ $product->name }}</td>
        <td>{{ \Illuminate\Support\Str::limit($product->detail, 60) }}</td>
        <td>${{ number_format($product->price, 2) }}</td>
        <td>
            <div class="d-flex align-items-center gap-2">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                        data-url="{{ route('products.toggle-status', $product) }}"
                        {{ $product->status ? 'checked' : '' }}>
                </div>
                <span
                    class="badge rounded-pill status-badge {{ $product->status ? 'bg-success' : 'bg-secondary' }}">{{ $product->status ? 'Active' : 'Inactive' }}</span>
            </div>
        </td>
        <td class="text-end">
            <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-secondary">View</a>
            <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary">Edit</a>
            <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline"
                onsubmit="return confirm('Delete this product?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="text-center text-muted py-4">No products found.</td>
    </tr>
@endforelse
