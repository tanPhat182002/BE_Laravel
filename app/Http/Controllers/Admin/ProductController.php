<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductImages;
use App\Models\ProductVariant;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Admin\ProductRequest;
use Log;


class ProductController extends Controller
{
    public function index()
    {
        try {
            $products = Product::with(['variants.color', 'variants.size', 'images', 'brand', 'category'])
                ->get()
                ->map(function ($product) {
                    $product->images->transform(function ($image) {
                        $image->full_url = $image->url ? asset('storage/' . $image->url) : null;
                        return $image;
                    });

                    // Thêm trường image_url cho sản phẩm (sử dụng ảnh chính nếu có)
                    $product->image_url = $product->images->where('is_primary', true)->first()->full_url 
                        ?? $product->images->first()->full_url 
                        ?? null;

                    return $product;
                });

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $product = Product::with(['variants.color', 'variants.size', 'images', 'brand', 'category'])->find($id);

            if (!$product) {
                return response()->json(['message' => 'Sản phẩm không tồn tại'], 404);
            }

            // Xử lý ảnh cho sản phẩm
            $product->images->transform(function ($image) {
                $image->full_url = $image->url ? asset('storage/' . $image->url) : null;
                return $image;
            });

            // Thêm trường image_url cho sản phẩm (sử dụng ảnh chính nếu có)
            $product->image_url = $product->images->where('is_primary', true)->first()->full_url 
                ?? $product->images->first()->full_url 
                ?? null;

            return response()->json($product);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {

        try {
            DB::beginTransaction();
            $product = Product::create([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'brand_id' => $request->brand_id,
                'category_id' => $request->category_id,
                'is_active' => $request->is_active ? 1 : 0,
                'promotion_id' => $request->promotion_id,
            ]);

            foreach ($request->variants as $variantData) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'color_id' => $variantData['color_id'],
                    'size_id' => $variantData['size_id'],
                    'stock_quantity' => $variantData['stock_quantity'],
                ]);
            }

            if ($request->hasFile('images')) {
                $images = $request->file('images');
                foreach (array_slice($images, 0, 5) as $index => $image) {
                    $imageName = Str::random(20) . '.' . $image->getClientOriginalExtension();
                    $imagePath = $image->storeAs('products', $imageName, 'public');

                    ProductImages::create([
                        'product_id' => $product->id,
                        'url' => $imagePath,
                        'is_primary' => $index === 0,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Thêm sản phẩm thành công',
                'product' => $product->load('variants.color', 'variants.size', 'images')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred while creating the product: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $product = Product::findOrFail($id);
            $product->update([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'brand_id' => $request->brand_id,
                'category_id' => $request->category_id,
                'is_active' => $request->is_active ? 1 : 0,
                'promotion_id' => $request->promotion_id,
            ]);

            // Xóa variants cũ và tạo mới
            $product->variants()->delete();
            foreach ($request->variants as $variantData) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'color_id' => $variantData['color_id'],
                    'size_id' => $variantData['size_id'],
                    'stock_quantity' => $variantData['stock_quantity'],
                ]);
            }

            // Xử lý ảnh mới nếu có
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                foreach (array_slice($images, 0, 5) as $index => $image) {
                    $imageName = Str::random(20) . '.' . $image->getClientOriginalExtension();
                    $imagePath = $image->storeAs('products', $imageName, 'public');

                    ProductImages::create([
                        'product_id' => $product->id,
                        'url' => $imagePath,
                        'is_primary' => $index === 0 && $product->images()->count() === 0,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Cập nhật sản phẩm thành công',
                'product' => $product->load('variants.color', 'variants.size', 'images')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred while updating the product: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $product = Product::findOrFail($id);

            // Xóa các ảnh liên quan
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->url);
                $image->delete();
            }

            // Xóa các variants
            $product->variants()->delete();

            // Xóa sản phẩm
            $product->delete();

            DB::commit();

            return response()->json(['message' => 'Xóa sản phẩm thành công']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred while deleting the product: ' . $e->getMessage()], 500);
        }
    }
}