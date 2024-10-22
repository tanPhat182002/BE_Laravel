<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'brand_id' => 'required|exists:brands,id',
            'category_id' => 'required|exists:categories,id',
            'is_active' => 'boolean',
       
            'variants' => 'required|array',
            'variants.*.color_id' => 'required|exists:colors,id',
            'variants.*.size_id' => 'required|exists:sizes,id',
            'variants.*.stock_quantity' => 'required|integer|min:0',
            'images' => 'required|array|min:1|max:5',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'Tên không được để trống',
            'name.string' => 'Tên phải là chuỗi',
            'name.max' => 'Tên không được vượt quá 255 ký tự',
            'description.string' => 'Mô tả phải là chuỗi',
            'price.required' => 'Giá không được để trống',
            'price.numeric' => 'Giá phải là số',
            'price.min' => 'Giá không được nhỏ hơn 0',
            'brand_id.required' => 'Thương hiệu không được để trống',
            'brand_id.exists' => 'Thương hiệu không tồn tại',
            'category_id.required' => 'Danh mục không được để trống',
            'category_id.exists' => 'Danh mục không tồn tại',
            'is_active.boolean' => 'Trạng thái phải là boolean',
            'variants.required' => 'Biến thể không được để trống',
            'variants.array' => 'Biến thể phải là mảng',
            'variants.*.color_id.required' => 'Màu sắc không được để trống',
            'variants.*.color_id.exists' => 'Màu sắc không tồn tại',
            'variants.*.size_id.required' => 'Kích thước không được để trống',
            'variants.*.size_id.exists' => 'Kích thước không tồn tại',
            'variants.*.stock_quantity.required' => 'Số lượng không được để trống',
            'variants.*.stock_quantity.integer' => 'Số lượng phải là số nguyên',
            'variants.*.stock_quantity.min' => 'Số lượng không được nhỏ hơn 0',
            'images.required' => 'Ảnh không được để trống',
            'images.array' => 'Ảnh phải là mảng',
            'images.min' => 'Ít nhất phải có 1 ảnh',
            'images.max' => 'Nhiều nhất chỉ có thể có 5 ảnh',
            'images.*.required' => 'Ảnh không được để trống',
            'images.*.image' => 'Ảnh không đúng định dạng',
            'images.*.mimes' => 'Ảnh phải có định dạng jpeg, png, jpg, gif',
            'images.*.max' => 'Ảnh không được vượt quá 2048 KB',
        ];
    }
}
