<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Color;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ColorController extends Controller
{
    /**
     * Display a listing of colors
     */
    public function index()
    {
        try {
            $colors = Color::latest()->get();
            
            // Format lại URL ảnh cho từng color
            $colors->transform(function($color) {
                $color->code = $color->code ? asset('storage/' . $color->code) : null;
                return $color;
            });

            return response()->json([
                'success' => true,
                'data' => $colors
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách màu sắc',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created color
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:colors,name',
               
            ], [
                'name.required' => 'Vui lòng nhập tên màu',
                'name.unique' => 'Tên màu đã tồn tại',
               
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Upload code
            $code = $request->file('code');
            $codePath = $code->store('colors', 'public');

            $color = Color::create([
                'name' => $request->name,
                'code' => $codePath
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Thêm màu sắc thành công',
                'data' => $color
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thêm màu sắc',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified color
     */
    public function show($id)
    {
        try {
            $color = Color::findOrFail($id);
            
            // Format lại URL ảnh
            $color->code = $color->code ? asset('storage/' . $color->code) : null;

            return response()->json([
                'success' => true,
                'data' => $color
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy màu sắc',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    /**
     * Update the specified color
     */
    public function update(Request $request, $id)
    {
        try {
            $color = Color::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:colors,name,'.$id,
            ], [
                'name.required' => 'Vui lòng nhập tên màu',
                'name.unique' => 'Tên màu đã tồn tại',
               
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = [
                'name' => $request->name
            ];

            // Update code if provided
            if ($request->hasFile('code')) {
                // Delete old code
                if ($color->code) {
                    Storage::disk('public')->delete($color->code);
                }

                // Upload new code
                $code = $request->file('code');
                $codePath = $code->store('colors', 'public');
                $data['code'] = $codePath;
            }

            $color->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật màu sắc thành công',
                'data' => $color
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật màu sắc',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified color
     */
    public function destroy($id)
    {
        try {
            $color = Color::findOrFail($id);
            
            // Check if color is being used
            if ($color->productVariants()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể xóa màu sắc này vì đang được sử dụng'
                ], 422);
            }

            // Delete code
            if ($color->code) {
                Storage::disk('public')->delete($color->code);
            }

            $color->delete();

            return response()->json([
                'success' => true,
                'message' => 'Xóa màu sắc thành công'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa màu sắc',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}