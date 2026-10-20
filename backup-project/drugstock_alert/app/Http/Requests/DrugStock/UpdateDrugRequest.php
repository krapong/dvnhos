<?php

namespace App\Http\Requests\DrugStock;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDrugRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $drugId = $this->route('drug')?->id ?? $this->route('id');

        return [
            'code' => ['required', 'string', 'max:80', Rule::unique('drugs', 'code')->ignore($drugId)],
            'name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'drug_category_id' => ['nullable', 'exists:drug_categories,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'barcode' => ['nullable', 'string', 'max:120'],
            'min_stock_qty' => ['nullable', 'numeric', 'min:0'],
            'reorder_qty' => ['nullable', 'numeric', 'min:0'],
            'is_controlled' => ['boolean'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string'],
        ];
    }
}
