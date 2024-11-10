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
    public function index(Request $request)
    {
        try {
            // Lấy số lượng item trên mỗi trang từ request, mặc định là 10
            $perPage = $request->input('per_page', 1);
            
            // Lấy các tham số tìm kiếm và sắp xếp
            $search = $request->input('search', '');
            $sortBy = $request->input('sort_by', 'created_at');
            $sortDesc = $request->input('sort_desc', true);
    
            // Query builder với điều kiện tìm kiếm
            $query = Brands::query();
            
            // Thêm điều kiện tìm kiếm nếu có
            if ($search) {
                $query->where('name', 'like', "%{$search}%");
            }
    
            // Thêm sắp xếp
            $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');
    
            // Thực hiện phân trang và transform data
            $brands = $query->paginate($perPage)->through(function ($brand) {
                $brand->image = $brand->image ? asset('storage/' . $brand->image) : null;
                return $brand;
            });
    
            return response()->json($brands);
    
        } catch (\Exception $e) {
            \Log::error('Error fetching brands: ' . $e->getMessage());
            return response()->json([
                'message' => 'Có lỗi xảy ra khi tải danh sách thương hiệu'
            ], 500);
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

    public function update(BrandsRequest $request, $id) {
        try {
            $brand = Brands::find($id);
            
            if (!$brand) {
                return response()->json(['message' => 'Không tìm thấy thương hiệu'], 404);
            }
    
            $validatedData = $request->validated();
    
            // Xử lý ảnh: chỉ khi có ảnh mới upload lên
            if ($request->hasFile('image')) {
                // Nếu có ảnh cũ thì xóa
                if ($brand->image) {
                    Storage::disk('public')->delete($brand->image);
                }
                
                // Lưu ảnh mới
                $image = $request->file('image');
                $imageName = Str::random(20) . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('brands', $imageName, 'public');
                $validatedData['image'] = $imagePath;
            } else {
                // Nếu không có ảnh mới upload, giữ nguyên ảnh cũ
                // Loại bỏ trường image khỏi validated data để không bị update thành null
                unset($validatedData['image']);
            }
    
            // Update brand với dữ liệu đã validate
            $brand->update($validatedData);
    
            // Format lại đường dẫn ảnh để trả về
            $brand->image = $brand->image ? asset('storage/' . $brand->image) : null;
    
            return response()->json([
                'message' => 'Cập nhật thương hiệu thành công',
                'brand' => $brand
            ], 200);
    
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
                    'message' => "Không thể xóa thương hiệu này vì đang có {$brand->products->count()} sản phẩm liên kết",
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