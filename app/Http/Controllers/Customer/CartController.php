<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CartController extends Controller
{
    public function index()
    {
        $products = Product::active()->orderBy('name')->get();

        return view('customer.cart', compact('products'));
    }

    public function items(Request $request)
    {
        $user = $request->user();

        return response()->json($this->cartResponse($user));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $product = Product::where('id', $validated['product_id'])->where('status', true)->first();

        if (!$product) {
            return response()->json([
                'message' => 'Product is not available.',
            ], 422);
        }

        $user = $request->user();
        $quantity = $validated['quantity'] ?? 1;

        $cartItem = CartItem::firstOrNew([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $cartItem->quantity = $cartItem->exists
            ? $cartItem->quantity + $quantity
            : $quantity;

        $cartItem->save();

        return response()->json($this->cartResponse($user));
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $user = $request->user();

        if ($cartItem->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        if (!$cartItem->product || !$cartItem->product->status) {
            $cartItem->delete();

            return response()->json($this->cartResponse($user));
        }

        $cartItem->quantity = $validated['quantity'];
        $cartItem->save();

        return response()->json($this->cartResponse($user));
    }

    public function destroy(Request $request, CartItem $cartItem)
    {
        $user = $request->user();

        if ($cartItem->user_id !== $user->id) {
            abort(403);
        }

        $cartItem->delete();

        return response()->json($this->cartResponse($user));
    }

    protected function cartResponse($user): array
    {
        $items = CartItem::with('product')
            ->where('user_id', $user->id)
            ->get()
            ->filter(function ($item) {
                return $item->product && $item->product->status;
            })
            ->values();

        $total = $items->reduce(function ($carry, $item) {
            return $carry + ($item->product->price * $item->quantity);
        }, 0);

        return [
            'items' => $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product->name,
                    'price' => (float) $item->product->price,
                    'quantity' => $item->quantity,
                    'image' => $item->product->image_path ? Storage::url($item->product->image_path) : null,
                    'subtotal' => (float) $item->product->price * $item->quantity,
                ];
            })->values(),
            'total' => (float) $total,
        ];
    }
}
