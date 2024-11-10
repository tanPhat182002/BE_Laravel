<?php

namespace App\Http\Controllers\User;
use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Promotions;

class IndexController extends Controller
{
    public function getHome(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 12);

            $products = Product::with([
                'images',
                'promotion' => function($query) {
                    $query->where('is_active', true)
                        ->where('start_date', '<=', now())
                        ->where('end_date', '>=', now());
                }
            ])
            ->where('is_active', true)
            ->latest()
            ->paginate($perPage);

            $formattedProducts = $products->map(function($product) {
                $primaryImage = $product->images->where('is_primary', true)->first();
                $mainImage = $primaryImage ? asset('storage/' . $primaryImage->url) : 
                            ($product->images->first() ? asset('storage/' . $product->images->first()->url) : null);

                $finalPrice = $product->price;
                if ($product->promotion) {
                    if ($product->promotion->discount_type === 'percentage') {
                        $finalPrice = $finalPrice * (1 - $product->promotion->discount_value / 100);
                    } else {
                        $finalPrice = $finalPrice - $product->promotion->discount_value;
                    }
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => [
                        'original' => number_format($product->price, 0, ',', '.') . '₫',
                        'final' => number_format($finalPrice, 0, ',', '.') . '₫',
                    ],
                    'main_image' => $mainImage,
                    'promotion' => $product->promotion ? [
                        'discount' => [
                            'display' => $product->promotion->discount_type === 'percentage'
                                ? "-{$product->promotion->discount_value}%"
                                : "-" . number_format($product->promotion->discount_value, 0, ',', '.') . '₫'
                        ]
                    ] : null
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedProducts,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFlashSale()
    {
        try {
            $activePromotion = Promotions::where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('end_date', '>', now())
                ->first();

            if (!$activePromotion) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'is_active' => false,
                        'products' => []
                    ]
                ]);
            }

            $endTime = Carbon::parse($activePromotion->end_date);
            $remainingTime = [
                'hours' => $endTime->diffInHours(now()),
                'minutes' => $endTime->diffInMinutes(now()) % 60,
                'seconds' => $endTime->diffInSeconds(now()) % 60,
            ];

            $products = Product::with(['images', 'variants'])
                ->where('is_active', true)
                ->where('promotion_id', $activePromotion->id)
                ->latest()
                ->get();

            $formattedProducts = $products->map(function($product) {
                $primaryImage = $product->images->where('is_primary', true)->first();
                $mainImage = $primaryImage ? asset('storage/' . $primaryImage->url) : 
                            ($product->images->first() ? asset('storage/' . $product->images->first()->url) : null);

                $originalPrice = $product->price;
                $finalPrice = $originalPrice;
                $discount = null;

                if ($product->promotion) {
                    if ($product->promotion->discount_type === 'percentage') {
                        $finalPrice = $originalPrice * (1 - $product->promotion->discount_value / 100);
                        $discount = "-{$product->promotion->discount_value}%";
                    } else {
                        $finalPrice = $originalPrice - $product->promotion->discount_value;
                        $discount = "-" . number_format($product->promotion->discount_value, 0, ',', '.') . '₫';
                    }
                }

                $totalStock = $product->variants->sum('stock_quantity');
                $soldCount = $product->variants->sum('sold_count') ?? 0;
                $soldPercentage = $totalStock > 0 ? ($soldCount / $totalStock) * 100 : 0;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => [
                        'original' => number_format($originalPrice, 0, ',', '.') . '₫',
                        'final' => number_format($finalPrice, 0, ',', '.') . '₫',
                        'discount' => $discount
                    ],
                    'main_image' => $mainImage,
                    'sold_info' => [
                        'total_stock' => $totalStock,
                        'sold_count' => $soldCount,
                        'sold_percentage' => round($soldPercentage, 1),
                        'remaining' => $totalStock - $soldCount
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'is_active' => true,
                    'flash_sale' => [
                        'name' => $activePromotion->name,
                        'end_time' => $activePromotion->end_date,
                        'remaining_time' => $remainingTime
                    ],
                    'products' => $formattedProducts
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy thông tin flash sale',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getDetailProduct($id)
    {
       try {
           $product = Product::with([
               'images',
               'brand:id,name',
               'category:id,name', 
               'variants' => function($query) {
                   $query->with(['color:id,name,code', 'size:id,name']);
               },
               'promotion' => function($query) {
                   $query->where('is_active', true)
                       ->where('start_date', '<=', now())
                       ->where('end_date', '>=', now());
               },
               'ratings' => function($query) {
                   $query->latest()->with('user:id,name');
               }
           ])
           ->where('is_active', true)
           ->findOrFail($id);
    
           // Format images
           $formattedImages = $product->images->map(function($image) {
               return [
                   'id' => $image->id,
                   'url' => asset('storage/' . $image->url),
                   'is_primary' => $image->is_primary
               ];
           });
    
           // Get primary image
           $mainImage = $formattedImages->where('is_primary', true)->first() 
                       ?? $formattedImages->first();
    
           // Calculate final price
           $finalPrice = $product->price;
           if ($product->promotion) {
               if ($product->promotion->discount_type === 'percentage') {
                   $finalPrice = $finalPrice * (1 - $product->promotion->discount_value / 100);
               } else {
                   $finalPrice = $finalPrice - $product->promotion->discount_value;
               }
           }
    
           // Group variants by color với ảnh màu
           $variantsByColor = $product->variants->groupBy('color.id')->map(function($variants) {
               $firstVariant = $variants->first();
               return [
                   'color' => [
                       'id' => $firstVariant->color->id,
                       'name' => $firstVariant->color->name,
                       'image_url' => asset('storage/' . $firstVariant->color->code), // Thêm image_url từ code
                   ],
                   'sizes' => $variants->map(function($variant) {
                       return [
                           'id' => $variant->id,
                           'size' => [
                               'id' => $variant->size->id,
                               'name' => $variant->size->name,
                           ],
                           'stock' => $variant->stock_quantity
                       ];
                   })->values()
               ];
           })->values();
    
           // Calculate ratings
           $ratings = $product->ratings;
           $averageRating = $ratings->avg('star_rating');
           $totalRatings = $ratings->count();
           $ratingCounts = collect(range(1, 5))->map(function($star) use ($ratings) {
               return [
                   'star' => $star,
                   'count' => $ratings->where('star_rating', $star)->count()
               ];
           });
    
           // Format final response
           $formattedProduct = [
               'id' => $product->id,
               'name' => $product->name,
               'description' => $product->description,
               'brand' => [
                   'id' => $product->brand->id,
                   'name' => $product->brand->name
               ],
               'category' => [
                   'id' => $product->category->id,
                   'name' => $product->category->name
               ],
               'price' => [
                   'original' => number_format($product->price, 0, ',', '.') . '₫',
                   'final' => number_format($finalPrice, 0, ',', '.') . '₫',
                   'raw' => [
                       'original' => $product->price,
                       'final' => $finalPrice
                   ]
               ],
               'promotion' => $product->promotion ? [
                   'name' => $product->promotion->name,
                   'discount' => [
                       'type' => $product->promotion->discount_type,
                       'value' => $product->promotion->discount_value,
                       'display' => $product->promotion->discount_type === 'percentage' 
                           ? "-{$product->promotion->discount_value}%" 
                           : "-" . number_format($product->promotion->discount_value, 0, ',', '.') . '₫'
                   ],
                   'end_date' => $product->promotion->end_date
               ] : null,
               'images' => [
                   'main' => $mainImage['url'] ?? null,
                   'gallery' => $formattedImages
               ],
               'variants' => [
                   'by_color' => $variantsByColor,
                   'total_stock' => $product->variants->sum('stock_quantity')
               ],
               'ratings' => [
                   'average' => round($averageRating, 1),
                   'total' => $totalRatings,
                   'distribution' => $ratingCounts,
                   'latest' => $ratings->take(5)->map(function($rating) {
                       return [
                           'id' => $rating->id,
                           'user_name' => $rating->user->name,
                           'star' => $rating->star_rating,
                           'comment' => $rating->comment,
                           'created_at' => $rating->created_at->format('d/m/Y')
                       ];
                   })
               ]
           ];
    
           return response()->json([
               'success' => true,
               'data' => $formattedProduct
           ]);
    
       } catch (\Exception $e) {
           return response()->json([
               'success' => false,
               'message' => 'Có lỗi xảy ra khi lấy thông tin sản phẩm', 
               'error' => $e->getMessage()
           ], 500);
       }
    }
}