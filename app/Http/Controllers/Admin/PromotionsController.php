<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotions;
use App\Services\PromotionService;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class PromotionsController extends Controller
{
    protected $promotionService;

    public function __construct(PromotionService $promotionService)
    {
        $this->promotionService = $promotionService;
    }

    /**
     * Lấy danh sách tất cả khuyến mãi
     */
    public function index(): JsonResponse
    {
        try {
            $promotions = $this->promotionService->getAllPromotions();
            
            return response()->json([
                'success' => true,
                'data' => $promotions
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy danh sách khuyến mãi đang active
     */
    public function getActivePromotions(): JsonResponse
    {
        try {
            $promotions = $this->promotionService->getActivePromotions();

            return response()->json([
                'success' => true,
                'data' => $promotions
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lấy chi tiết một khuyến mãi
     */
    public function show($id): JsonResponse
    {
        try {
            $promotion = $this->promotionService->getPromotionById($id);

            return response()->json([
                'success' => true,
                'data' => $promotion
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tạo khuyến mãi mới
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'discount_type' => 'required',
                'discount_value' => 'required|numeric|min:0',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'product_ids' => 'required|array',
                'product_ids.*' => 'exists:products,id'
            ]);
    
            $promotion = $this->promotionService->createPromotion($validated);
    
            return response()->json([
                'success' => true,
                'message' => 'Tạo khuyến mãi thành công',
                'data' => $promotion
            ], 201);
    
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật thông tin khuyến mãi
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $promotion = Promotions::findOrFail($id);
            $updated = $this->promotionService->updatePromotion($promotion, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật khuyến mãi thành công',
                'data' => $updated
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa khuyến mãi
     */
    public function destroy($id): JsonResponse
    {
        try {
            $promotion = Promotions::findOrFail($id);
            $this->promotionService->deletePromotion($promotion);

            return response()->json([
                'success' => true,
                'message' => 'Xóa khuyến mãi thành công'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cập nhật trạng thái active/inactive
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'is_active' => 'required|boolean'
            ]);

            $promotion = Promotions::findOrFail($id);
            
            // Chỉ cập nhật trạng thái active
            $updated = $this->promotionService->updatePromotion($promotion, [
                ...$promotion->toArray(),
                'is_active' => $validated['is_active']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công',
                'data' => $updated
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }
}