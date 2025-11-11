@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">My Cart</h4>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0">Available Products</h5>
                </div>
                <div class="card-body">
                    @if ($products->isEmpty())
                        <p class="text-muted mb-0">No active products right now.</p>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach ($products as $product)
                                <div class="list-group-item">
                                    <div class="d-flex align-items-start">
                                        @if ($product->image_path)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}"
                                                alt="{{ $product->name }}" class="rounded me-3" width="64"
                                                height="64">
                                        @else
                                            <div class="rounded bg-light d-flex align-items-center justify-content-center text-muted me-3"
                                                style="width: 64px; height: 64px;">
                                                No image
                                            </div>
                                        @endif
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1">{{ $product->name }}</h6>
                                                    <p class="text-muted mb-2 small">
                                                        {{ \Illuminate\Support\Str::limit($product->detail, 80) }}</p>
                                                </div>
                                                <strong>${{ number_format($product->price, 2) }}</strong>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="number" class="form-control form-control-sm quantity-input"
                                                    min="1" value="1" style="width: 80px;">
                                                <button class="btn btn-sm btn-primary add-to-cart-btn"
                                                    data-product-id="{{ $product->id }}">
                                                    Add to Cart
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Cart Items</h5>
                    <button class="btn btn-sm btn-outline-secondary" id="refresh-cart-btn">Refresh</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center" style="width: 120px;">Quantity</th>
                                    <th class="text-end" style="width: 120px;">Subtotal</th>
                                    <th class="text-end" style="width: 60px;"></th>
                                </tr>
                            </thead>
                            <tbody id="cart-items-body">
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Cart is empty.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <strong>Total</strong>
                    <strong id="cart-total">$0.00</strong>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const routes = {
                items: '{{ route('customer.cart.items') }}',
                store: '{{ route('customer.cart.store') }}',
                update: (id) => '{{ route('customer.cart.update', ['cartItem' => '__id__']) }}'.replace(
                    '__id__', id),
                destroy: (id) => '{{ route('customer.cart.destroy', ['cartItem' => '__id__']) }}'.replace(
                    '__id__', id),
            };

            const cartBody = document.getElementById('cart-items-body');
            const cartTotal = document.getElementById('cart-total');
            const refreshBtn = document.getElementById('refresh-cart-btn');

            function formatCurrency(value) {
                return '$' + Number(value).toFixed(2);
            }

            function buildRow(item) {
                return `
                    <tr data-id="${item.id}">
                        <td>
                            <div class="d-flex align-items-center">
                                ${item.image ? `<img src="${item.image}" alt="${item.name}" class="rounded me-2" width="48" height="48">` : ''}
                                <div>
                                    <div>${item.name}</div>
                                    <small class="text-muted">${formatCurrency(item.price)}</small>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <input type="number" class="form-control form-control-sm cart-quantity" min="1" value="${item.quantity}">
                        </td>
                        <td class="text-end">${formatCurrency(item.subtotal)}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-danger remove-cart-item">&times;</button>
                        </td>
                    </tr>
                `;
            }

            function renderCart(data) {
                if (!data.items || data.items.length === 0) {
                    cartBody.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center text-muted">Cart is empty.</td>
                        </tr>
                    `;
                } else {
                    cartBody.innerHTML = data.items.map(buildRow).join('');
                }

                cartTotal.textContent = formatCurrency(data.total || 0);
            }

            function fetchCart() {
                fetch(routes.items, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error('Unable to load cart.');
                        }

                        return response.json();
                    })
                    .then(renderCart)
                    .catch((error) => {
                        console.error(error);
                    });
            }

            function sendCartRequest(url, options = {}) {
                return fetch(url, {
                    method: options.method || 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(options.body || {}),
                }).then((response) => {
                    if (!response.ok) {
                        return response.json()
                            .then((data) => {
                                throw new Error(data.message || 'Unable to update cart.');
                            })
                            .catch((error) => {
                                if (error instanceof Error) {
                                    throw error;
                                }

                                throw new Error('Unable to update cart.');
                            });
                    }

                    return response.json();
                });
            }

            document.querySelectorAll('.add-to-cart-btn').forEach((btn) => {
                btn.addEventListener('click', function() {
                    const quantityInput = this.closest('.d-flex').querySelector('.quantity-input');
                    const quantity = Number(quantityInput?.value) || 1;

                    sendCartRequest(routes.store, {
                            method: 'POST',
                            body: {
                                product_id: this.dataset.productId,
                                quantity,
                            },
                        })
                        .then(renderCart)
                        .catch((error) => alert(error.message));
                });
            });

            cartBody.addEventListener('change', function(event) {
                if (!event.target.classList.contains('cart-quantity')) {
                    return;
                }

                const row = event.target.closest('tr');
                const id = row?.dataset?.id;
                const quantity = Number(event.target.value);

                if (!id || !quantity || quantity < 1) {
                    event.target.value = 1;
                    return;
                }

                sendCartRequest(routes.update(id), {
                        method: 'PATCH',
                        body: {
                            quantity,
                        },
                    })
                    .then(renderCart)
                    .catch((error) => {
                        alert(error.message);
                        fetchCart();
                    });
            });

            cartBody.addEventListener('click', function(event) {
                if (!event.target.classList.contains('remove-cart-item')) {
                    return;
                }

                const row = event.target.closest('tr');
                const id = row?.dataset?.id;

                if (!id) {
                    return;
                }

                sendCartRequest(routes.destroy(id), {
                        method: 'DELETE',
                    })
                    .then(renderCart)
                    .catch((error) => alert(error.message));
            });

            refreshBtn.addEventListener('click', fetchCart);

            fetchCart();
        });
    </script>
@endpush
