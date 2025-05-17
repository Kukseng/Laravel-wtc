<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Notification;
use App\Models\User;
use App\Models\Role;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $products = Product::with('categories')->get();
        
        return response()->json([
            'products' => $products
        ]);
    }

    /**
     * Store a newly created product in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'required|integer|min:1',
            'image' => 'nullable|string',
            'status' => 'required|boolean',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::create($request->except('categories'));

        if ($request->has('categories')) {
            $product->categories()->attach($request->categories);
        }

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product->load('categories')
        ], 201);
    }

    /**
     * Display the specified product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $product = Product::with('categories')->findOrFail($id);
        
        return response()->json([
            'product' => $product
        ]);
    }

    /**
     * Update the specified product in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
            'quantity' => 'sometimes|required|integer|min:0',
            'low_stock_threshold' => 'sometimes|required|integer|min:1',
            'image' => 'nullable|string',
            'status' => 'sometimes|required|boolean',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::findOrFail($id);
        $product->update($request->except('categories'));

        // Check if quantity is updated and if it's below threshold
        if ($request->has('quantity') && $product->isLowStock()) {
            $this->sendLowStockNotification($product);
        }

        if ($request->has('categories')) {
            $product->categories()->sync($request->categories);
        }

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product->fresh()->load('categories')
        ]);
    }

    /**
     * Remove the specified product from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        
        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Send low stock notification to admin users.
     *
     * @param  \App\Models\Product  $product
     * @return void
     */
    private function sendLowStockNotification(Product $product)
    {
        $adminRole = Role::where('name', 'Admin')->first();
        
        if ($adminRole) {
            $admins = User::where('role_id', $adminRole->id)->get();
            
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\LowStockNotification($product));
            }
        }
    }

    /**
     * Get all products with low stock.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function lowStock()
    {
        $products = Product::whereRaw('quantity <= low_stock_threshold')->get();
        
        return response()->json([
            'low_stock_products' => $products
        ]);
    }
}
