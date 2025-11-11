@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Products</h4>
        <a href="{{ route('products.create') }}" class="btn btn-primary">Add Product</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <input type="text" id="product-search" class="form-control" placeholder="Search products..."
                        value="{{ request('search', '') }}">
                </div>
                <div class="col-md-4">
                    @php
                        $productSortBy = request('sort_by', 'created_at');
                    @endphp
                    <select id="product-sort-by" class="form-select">
                        <option value="created_at" @selected($productSortBy === 'created_at')>Newest</option>
                        <option value="name" @selected($productSortBy === 'name')>Name</option>
                        <option value="price" @selected($productSortBy === 'price')>Price</option>
                        <option value="status" @selected($productSortBy === 'status')>Status</option>
                    </select>
                </div>
                <div class="col-md-4">
                    @php
                        $productSortDirection = request('sort_direction', 'desc');
                    @endphp
                    <select id="product-sort-direction" class="form-select">
                        <option value="desc" @selected($productSortDirection === 'desc')>Descending</option>
                        <option value="asc" @selected($productSortDirection === 'asc')>Ascending</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Image</th>
                            <th scope="col">Name</th>
                            <th scope="col">Detail</th>
                            <th scope="col">Price</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="products-table-body">
                        @include('products.partials.rows', ['products' => $products])
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer" id="products-pagination">
            @include('products.partials.pagination', ['products' => $products])
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initAsyncTable({
                endpoint: '{{ route('products.index') }}',
                tableBodyId: 'products-table-body',
                paginationId: 'products-pagination',
                searchInputId: 'product-search',
                sortBySelectId: 'product-sort-by',
                sortDirectionSelectId: 'product-sort-direction',
            });
        });
    </script>
@endpush
