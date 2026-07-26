<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StockMovementStoreRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'product_sku' => [
                'required',
                'string',
                'exists:products,sku',
            ],
            'warehouse_id' => [
                'required',
                'integer',
                'exists:warehouses,id',
            ],
            'movement_type' => [
                'required',
                'string',
                Rule::in(['in', 'out', 'transfer', 'adjustment']),
            ],
            'quantity' => [
                'required',
                'integer',
                'not_in:0', // BR5: Movement qty ≠ 0
                'min:-999999',
                'max:999999',
            ],
            'reference_number' => [
                'nullable',
                'string',
                'max:100',
            ],
            'notes' => [
                'nullable',
                'string',
            ],
            'moved_by' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_sku.exists' => 'The selected product SKU does not exist.',
            'warehouse_id.exists' => 'The selected warehouse does not exist.',
            'movement_type.in' => 'The movement type must be one of: in, out, transfer, adjustment.',
            'quantity.not_in' => 'The quantity cannot be zero.',
        ];
    }
}
