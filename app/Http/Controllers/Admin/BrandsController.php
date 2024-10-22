<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brands;
use App\Http\Requests\Admin\BrandsRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class BrandsController extends Controller
{
    public function index()
    {
        try {
            $brands = Brands::all()->map(function ($brand) {
                $brand->image = $brand->image ? asset('storage/' . $brand->image) : null;
                return $brand;
            });
            return response()->json($brands);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $brand = Brands::find($id);
            if ($brand) {
                $brand->image = $brand->image ? asset('storage/' . $brand->image) : null;
                return response()->json($brand, 200);
            } else {
                return response()->json(['message' => 'Không tìm thấy thương hiệu'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function store(BrandsRequest $request)
    {
        try {
            $validatedData = $request->validated();

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = Str::random(20) . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('brands', $imageName, 'public');
                $validatedData['image'] = $imagePath;
            }

            $brand = Brands::create($validatedData);

            $brand->image = $brand->image ? asset('storage/' . $brand->image) : null;

            return response()->json([
                'message' => 'Tạo thương hiệu thành công',
                'brand' => $brand
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update(BrandsRequest $request, $id)
    {
        try {
            $brand = Brands::find($id);

            if ($brand) {
                $validatedData = $request->validated();

                if ($request->hasFile('image')) {
                    if ($brand->image) {
                        Storage::disk('public')->delete($brand->image);
                    }
                    $image = $request->file('image');
                    $imageName = Str::random(20) . '.' . $image->getClientOriginalExtension();
                    $imagePath = $image->storeAs('brands', $imageName, 'public');
                    $validatedData['image'] = $imagePath;
                }

                $brand->update($validatedData);

                $brand->image = $brand->image ? asset('storage/' . $brand->image) : null;

                return response()->json([
                    'message' => 'Cập nhật thương hiệu thành công',
                    'brand' => $brand
                ], 200);
            } else {
                return response()->json(['message' => 'Không tìm thấy thương hiệu'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $brand = Brands::with('products')->find($id);
    
            if (!$brand) {
                return response()->json(['message' => 'Không tìm thấy thương hiệu'], 404);
            }
    
            // Kiểm tra xem thương hiệu có sản phẩm không
            if ($brand->products->count() > 0) {
                return response()->json([
                    'message' => 'Không thể xóa thương hiệu này vì đang có sản phẩm liên kết',
                    'products_count' => $brand->products->count()
                ], 400);
            }
    
            // Nếu không có sản phẩm, tiến hành xóa
            if ($brand->image) {
                Storage::disk('public')->delete($brand->image);
            }
            
            $brand->delete();
            
            return response()->json(['message' => 'Xóa thương hiệu thành công'], 200);
    
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}