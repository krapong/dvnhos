<?php

namespace App\Http\Requests\DrugStock;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlertRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rounds = config('medstock.stock_policy.expiry_alert_rounds', [30, 90, 180]);

        return [
            'scope' => ['nullable', Rule::in(['warehouse', 'location', 'drug'])],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'drug_id' => ['nullable', 'exists:drugs,id'],
            'alert_type' => ['nullable', Rule::in(['low_stock', 'near_expiry', 'expired'])],
            'threshold_qty' => ['nullable', 'numeric', 'min:0'],
            'expiry_days' => ['nullable', 'integer', Rule::in($rounds)],
            'is_enabled' => ['boolean'],
            'notify_telegram' => ['boolean'],
        ];
    }
}
