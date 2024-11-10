<?php

namespace App\Services;

use App\Models\Promotions;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class PromotionService
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Lấy tất cả khuyến mãi
     */
    public function getAllPromotions()
    {
        return Promotions::with('products')
            ->get()
            ->map(function ($promotion) {
                return $this->formatPromotionResponse($promotion);
            });
    }

    /**
     * Lấy chi tiết khuyến mãi
     */
    public function getPromotionById($id)
    {
        $promotion = Promotions::with('products')->findOrFail($id);
        return $this->formatPromotionResponse($promotion);
    }

    /**
     * Tạo khuyến mãi mới
     */
    public function createPromotion(array $data)
    {
        DB::beginTransaction();
        try {
            // Validate trước khi tạo
            $this->validatePromotion($data);

            // Tạo promotion
            $promotion = Promotions::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_active' => true
            ]);

            // Áp dụng cho sản phẩm
            if (!empty($data['product_ids'])) {
                Product::whereIn('id', $data['product_ids'])
                    ->update(['promotion_id' => $promotion->id]);
            }

            DB::commit();
            
            return $this->getPromotionById($promotion->id);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cập nhật khuyến mãi
     */
    public function updatePromotion(Promotions $promotion, array $data)
    {
        DB::beginTransaction();
        try {
            // Validate dữ liệu mới
            $this->validatePromotion($data, $promotion->id);

            // Cập nhật thông tin promotion
            $promotion->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? $promotion->description,
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_active' => $data['is_active'] ?? $promotion->is_active
            ]);

            // Cập nhật danh sách sản phẩm nếu có
            if (isset($data['product_ids'])) {
                // Reset các sản phẩm cũ
                Product::where('promotion_id', $promotion->id)
                    ->update(['promotion_id' => null]);

                // Thêm sản phẩm mới
                Product::whereIn('id', $data['product_ids'])
                    ->update(['promotion_id' => $promotion->id]);
            }

            DB::commit();
            
            return $this->getPromotionById($promotion->id);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Xóa khuyến mãi
     */
    public function deletePromotion(Promotions $promotion)
    {
        DB::beginTransaction();
        try {
            // Reset promotion_id của các sản phẩm
            Product::where('promotion_id', $promotion->id)
                ->update(['promotion_id' => null]);

            // Xóa promotion
            $promotion->delete();

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate dữ liệu khuyến mãi
     */
    private function validatePromotion(array $data, $excludeId = null)
    {
        // Validate discount
        if ($data['discount_type'] === 'percentage') {
            if ($data['discount_value'] <= 0 || $data['discount_value'] > 100) {
                throw new Exception('Phần trăm giảm giá phải từ 1-100%');
            }
        } else {
            if ($data['discount_value'] <= 0) {
                throw new Exception('Giá trị giảm phải lớn hơn 0');
            }
        }

        // Validate dates
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $now = Carbon::now();

        if ($startDate->isPast() && !$startDate->isToday()) {
            throw new Exception('Thời gian bắt đầu không thể trong quá khứ');
        }

        if ($endDate->lte($startDate)) {
            throw new Exception('Thời gian kết thúc phải sau thời gian bắt đầu');
        }

        // Kiểm tra conflict với khuyến mãi khác
        if (!empty($data['product_ids'])) {
            $query = Product::whereIn('id', $data['product_ids'])
                ->whereHas('promotion', function($query) use ($startDate, $endDate) {
                    $query->where('is_active', true)
                        ->where(function($q) use ($startDate, $endDate) {
                            $q->whereBetween('start_date', [$startDate, $endDate])
                              ->orWhereBetween('end_date', [$startDate, $endDate]);
                        });
                });

            // Loại trừ promotion hiện tại khi update
            if ($excludeId) {
                $query->where('promotion_id', '!=', $excludeId);
            }

            $conflictingProducts = $query->get();

            if ($conflictingProducts->isNotEmpty()) {
                $productNames = $conflictingProducts->pluck('name')->join(', ');
                throw new Exception("Các sản phẩm sau đã có khuyến mãi trong thời gian này: {$productNames}");
            }
        }

        return true;
    }

    /**
     * Format response cho promotion
     */
    private function formatPromotionResponse(Promotions $promotion)
    {
        // Đảm bảo products được load với promotion
        if (!$promotion->relationLoaded('products')) {
            $promotion->load('products');
        }

        // Set promotion cho mỗi product để tính giá
        $promotion->products->each(function($product) use ($promotion) {
            $product->setRelation('promotion', $promotion);
        });

        return [
            'id' => $promotion->id,
            'name' => $promotion->name,
            'description' => $promotion->description,
            'discount_type' => $promotion->discount_type,
            'discount_value' => $promotion->discount_value,
            'start_date' => $promotion->start_date,
            'end_date' => $promotion->end_date,
            'is_active' => $promotion->is_active,
            'products' => $promotion->products->map(function($product) {
                // Đảm bảo sử dụng giá đúng
                $originalPrice = floatval($product->price);
                
                // Tính giá sau khuyến mãi
                $discountValue = floatval($product->promotion->discount_value);
                
                // Tính toán giá giảm
                $finalPrice = match($product->promotion->discount_type) {
                    'percentage' => $originalPrice * (1 - ($discountValue / 100)),
                    'fixed' => max(0, $originalPrice - $discountValue),
                    default => $originalPrice
                };

                // Tính số tiền tiết kiệm
                $savedAmount = $originalPrice - $finalPrice;
                
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => [
                        'original' => number_format($originalPrice, 2, '.', ''),
                        'final' => number_format($finalPrice, 2, '.', ''),
                        'saved_amount' => number_format($savedAmount, 2, '.', ''),
                        'saved_percentage' => $originalPrice > 0 
                            ? round(($savedAmount / $originalPrice) * 100, 1)
                            : 0
                    ]
                ];
            }),
            'status' => $this->getPromotionStatus($promotion),
            'created_at' => $promotion->created_at,
            'updated_at' => $promotion->updated_at
        ];
    }
    /**
     * Lấy trạng thái của promotion
     */
    private function getPromotionStatus(Promotions $promotion)
    {
        if (!$promotion->is_active) {
            return 'inactive';
        }

        $now = Carbon::now();

        // Bỏ kiểm tra thời gian để preview giá
        if ($now->lt($promotion->start_date)) {
            return 'upcoming';
        }

        if ($now->gt($promotion->end_date)) {
            return 'expired';
        }

        return 'active';
    }
    /**
     * Tính giá sau khuyến mãi
     */
     /**
     * Tính giá sau khuyến mãi
     */
    private function calculateDiscountedPrice($product, $promotion)
    {
        $originalPrice = floatval($product->price);
        $discountValue = floatval($promotion->discount_value);

        return match($promotion->discount_type) {
            'percentage' => $originalPrice * (1 - ($discountValue / 100)),
            'fixed' => max(0, $originalPrice - $discountValue),
            default => $originalPrice
        };
    }

   /**
 * Lấy danh sách khuyến mãi đang active
 */
public function getActivePromotions()
{
    try {
        $promotions = Promotions::with(['products' => function($query) {
                $query->select('id', 'name', 'price', 'promotion_id');
            }])
            ->where('is_active', true)
            ->where('start_date', '<=', Carbon::now())
            ->where('end_date', '>=', Carbon::now())
            ->get()
            ->map(function($promotion) {
                return $this->formatPromotionResponse($promotion);
            });

        return $promotions;

    } catch (Exception $e) {
        \Log::error('Error getting active promotions: ' . $e->getMessage());
        throw $e;
    }
}
    
}
