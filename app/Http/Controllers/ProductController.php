<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('detail', 'like', '%' . $search . '%');
            });
        }

        $allowedSorts = ['id', 'name', 'price', 'status', 'created_at'];
        $sortBy = $request->input('sort_by', 'created_at');
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $sortDirection = $request->input('sort_direction', 'desc') === 'asc' ? 'asc' : 'desc';

        $products = $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'body' => view('products.partials.rows', compact('products'))->render(),
                'pagination' => view('products.partials.pagination', compact('products'))->render(),
            ]);
        }

        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'status' => ['nullable', 'boolean'],
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $status = $request->boolean('status');

        Product::create([
            'name' => $validated['name'],
            'detail' => $validated['detail'] ?? null,
            'price' => $validated['price'],
            'image_path' => $imagePath,
            'status' => $status,
        ]);

        return redirect()->route('products.index')->with('status', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'detail' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'status' => ['nullable', 'boolean'],
        ]);

        $product->name = $validated['name'];
        $product->detail = $validated['detail'] ?? null;
        $product->price = $validated['price'];
        $product->status = $request->boolean('status');

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $product->image_path = $request->file('image')->store('products', 'public');
        }

        $product->save();

        return redirect()->route('products.index')->with('status', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return redirect()->route('products.index')->with('status', 'Product deleted successfully.');
    }

    public function toggleStatus(Request $request, Product $product)
    {
        $validated = $request->validate([
            'status' => ['required', 'boolean'],
        ]);

        $product->status = $validated['status'];
        $product->save();

        return response()->json([
            'status' => $product->status,
            'label' => $product->status ? 'Active' : 'Inactive',
        ]);
    }
}
