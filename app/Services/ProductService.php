<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImages;
use App\Models\ProductVariant;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService 
{
    /**
     * Xử lý format ảnh cho sản phẩm
     */
    private function formatProductImages($product) 
    {
        $product->images->transform(function ($image) {
            $image->full_url = $image->url ? asset('storage/' . $image->url) : null;
            return $image;
        });

        $product->image_url = $product->images->where('is_primary', true)->first()->full_url 
            ?? $product->images->first()->full_url 
            ?? null;

        return $product;
    }

    /**
     * Xử lý upload ảnh sản phẩm
     */
    private function uploadProductImages($product, $images)
    {
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

    /**
     * Tạo variants cho sản phẩm
     */
    private function createProductVariants($product, $variants)
    {
        foreach ($variants as $variantData) {
            ProductVariant::create([
                'product_id' => $product->id,
                'color_id' => $variantData['color_id'],
                'size_id' => $variantData['size_id'],
                'stock_quantity' => $variantData['stock_quantity'],
            ]);
        }
    }

    /**
     * Lấy danh sách sản phẩm
     */
    public function getAllProducts()
    {
        return Product::with(['variants.color', 'variants.size', 'images', 'brand', 'category'])
            ->get()
            ->map(function ($product) {
                return $this->formatProductImages($product);
            });
    }

    /**
     * Lấy chi tiết sản phẩm
     */
    public function getProduct($id)
    {
        $product = Product::with(['variants.color', 'variants.size', 'images', 'brand', 'category'])
            ->findOrFail($id);
            
        return $this->formatProductImages($product);
    }

    /**
     * Tạo sản phẩm mới
     */
    public function createProduct($request)
    {
        DB::beginTransaction();
        try {
            // Tạo sản phẩm
            $product = Product::create([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'brand_id' => $request->brand_id,
                'category_id' => $request->category_id,
                'is_active' => $request->is_active ? 1 : 0,
                
            ]);

            // Tạo variants
            if (!empty($request->variants)) {
                $this->createProductVariants($product, $request->variants);
            }

            // Upload ảnh
            if ($request->hasFile('images')) {
                $this->uploadProductImages($product, $request->file('images'));
            }

            DB::commit();
            
            return $product->load(['variants.color', 'variants.size', 'images']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cập nhật sản phẩm
     */
    public function updateProduct($request, $id)
    {
        DB::beginTransaction();
        try {
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

            // Cập nhật variants
            $product->variants()->delete();
            if (!empty($request->variants)) {
                $this->createProductVariants($product, $request->variants);
            }

            // Cập nhật ảnh
            if ($request->hasFile('images')) {
                // Xóa ảnh cũ
                foreach ($product->images as $image) {
                    Storage::disk('public')->delete($image->url);
                    $image->delete();
                }

                // Upload ảnh mới
                $this->uploadProductImages($product, $request->file('images'));
            }

            DB::commit();
            
            return $this->formatProductImages(
                $product->load(['variants.color', 'variants.size', 'images'])
            );

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Xóa sản phẩm
     */
    public function deleteProduct($id)
    {
        DB::beginTransaction();
        try {
            $product = Product::findOrFail($id);

            // Xóa ảnh
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->url);
                $image->delete();
            }

            // Xóa variants và sản phẩm
            $product->variants()->delete();
            $product->delete();

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Tính giá sau khuyến mãi
     */
    public function calculateFinalPrice($product)
    {
        if (!$product->promotion || !$product->promotion->is_active) {
            return $product->price;
        }

        $now = Carbon::now();
        if (!$now->between($product->promotion->start_date, $product->promotion->end_date)) {
            return $product->price;
        }

        return match($product->promotion->discount_type) {
            'percentage' => round($product->price * (1 - $product->promotion->discount_value / 100), 0),
            'fixed' => max(0, $product->price - $product->promotion->discount_value),
            default => $product->price
        };
    }

    /**
     * Lấy sản phẩm đang khuyến mãi
     */
    public function getPromotionalProducts($request)
    {
        return Product::with(['promotion', 'variants', 'images'])
            ->whereHas('promotion', function($query) {
                $query->where('is_active', true)
                    ->where('start_date', '<=', Carbon::now())
                    ->where('end_date', '>=', Carbon::now());
            })
            ->when($request->category_id, function($query, $categoryId) {
                return $query->where('category_id', $categoryId);
            })
            ->when($request->brand_id, function($query, $brandId) {
                return $query->where('brand_id', $brandId);
            })
            ->get()
            ->map(function($product) {
                $product = $this->formatProductImages($product);
                $product->final_price = $this->calculateFinalPrice($product);
                return $product;
            });
    }
}