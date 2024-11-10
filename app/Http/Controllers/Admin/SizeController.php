<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Size;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SizeController extends Controller
{
    /**
     * Display a listing of sizes
     */
    public function index()
    {
        try {
            $sizes = Size::latest()->get();
            
            return response()->json([
                'success' => true,
                'data' => $sizes
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách kích thước',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created size
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:sizes,name',
               
            ], [
                'name.required' => 'Vui lòng nhập tên kích thước',
                'name.unique' => 'Tên kích thước đã tồn tại',
               
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $size = Size::create([
                'name' => $request->name,
               
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thêm kích thước thành công',
                'data' => $size
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thêm kích thước',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified size
     */
    public function show($id)
    {
        try {
            $size = Size::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $size
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy kích thước',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified size
     */
    public function update(Request $request, $id)
    {
        try {
            $size = Size::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:sizes,name,'.$id,
            ], [
                'name.required' => 'Vui lòng nhập tên kích thước',
                'name.unique' => 'Tên kích thước đã tồn tại',
              
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $size->update([
                'name' => $request->name,
                'code' => $request->code
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật kích thước thành công',
                'data' => $size
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật kích thước',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified size
     */
    public function destroy($id)
    {
        try {
            $size = Size::findOrFail($id);
            
            // Kiểm tra xem size có đang được sử dụng không
            if ($size->productVariants()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa kích thước này vì đang được sử dụng'
                ], 422);
            }

            $size->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa kích thước thành công'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa kích thước',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}