<?php

namespace App\Http\Controllers\Admin;
use App\Models\Categories;
use App\Http\Requests\Admin\CategoriesRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Str;

class CategoriesController extends Controller
{
    public function index(Request $request)
{
   try {
       // Lấy số lượng item trên mỗi trang từ request, mặc định là 10
       $perPage = $request->input('per_page', 10);
       
       // Lấy các tham số tìm kiếm và sắp xếp 
       $search = $request->input('search', '');
       $sortBy = $request->input('sort_by', 'created_at');
       $sortDesc = $request->input('sort_desc', true);

       // Query builder với điều kiện tìm kiếm
       $query = Categories::query();
       
       // Thêm điều kiện tìm kiếm nếu có
       if ($search) {
           $query->where('name', 'like', "%{$search}%");
       }

       // Thêm sắp xếp
       $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc');

       // Thực hiện phân trang và transform data
       $categories = $query->paginate($perPage)->through(function ($category) {
           $category->image = $category->image ? asset('storage/' . $category->image) : null;
           return $category;
       });

       return response()->json($categories);

   } catch (\Exception $e) {
       \Log::error('Error fetching categories: ' . $e->getMessage());
       return response()->json([
           'message' => 'Có lỗi xảy ra khi tải danh sách danh mục'
       ], 500);
   }
}
    public function show($id)
    {
        try {
            $category = Categories::find($id);
            if ($category) {
                $category->image = $category->image ? asset('storage/' . $category->image) : null;
                return response()->json($category, 200);
            } else {
                return response()->json(['message' => 'Không tìm thấy danh mục'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    
    public function store(CategoriesRequest $request)
    {
        try {
            $validatedData = $request->validated();
            
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = Str::random(20) . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('categories', $imageName, 'public');
                $validatedData['image'] = $imagePath;
            }
            
            $category = Categories::create($validatedData);
            
            // Cập nhật URL ảnh
            $category->image = $category->image ? asset('storage/' . $category->image) : null;
    
            return response()->json([
                'message' => 'Tạo danh mục thành công',
                'category' => $category
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public function destroy($id)
    {
        try {
            $category = Categories::with('products')->find($id);
    
            if (!$category) {
                return response()->json(['message' => 'Không tìm thấy danh mục'], 404);
            }
    
            // Kiểm tra xem danh mục có sản phẩm không
            if ($category->products->count() > 0) {
                return response()->json([
                    'message' => "Không thể xóa danh mục này vì đang có {$category->products->count()} sản phẩm liên kết",
                    'products_count' => $category->products->count()
                ], 400);
            }
    
            // Nếu không có sản phẩm, tiến hành xóa
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            
            $category->delete();
            
            return response()->json(['message' => 'Xóa danh mục thành công'], 200);
    
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public function update(CategoriesRequest $request, $id) {
        try {
            $category = Categories::find($id);
            
            if (!$category) {
                return response()->json(['message' => 'Không tìm thấy danh mục'], 404);
            }
    
            $validatedData = $request->validated();
    
            // Xử lý ảnh khi có file mới upload
            if ($request->hasFile('image')) {
                // Xóa ảnh cũ nếu tồn tại
                if ($category->image) {
                    Storage::disk('public')->delete($category->image);
                }
                
                // Lưu ảnh mới
                $image = $request->file('image');
                $imageName = Str::random(20) . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('categories', $imageName, 'public');
                $validatedData['image'] = $imagePath;
            } else {
                // Nếu không có ảnh mới, giữ nguyên ảnh cũ
                unset($validatedData['image']);
            }
    
            // Update category
            $category->update($validatedData);
    
            // Format lại URL ảnh để trả về
            $category->image = $category->image ? asset('storage/' . $category->image) : null;
    
            return response()->json([
                'message' => 'Cập nhật danh mục thành công',
                'category' => $category
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}

