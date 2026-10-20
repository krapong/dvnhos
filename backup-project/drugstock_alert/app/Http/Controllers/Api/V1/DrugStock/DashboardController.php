<?php

namespace App\Http\Controllers\Api\V1\DrugStock;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Services\DrugStockDashboardService;
use Illuminate\Http\Request;

class DashboardController extends BaseApiController
{
    public function __construct(private readonly DrugStockDashboardService $service)
    {
    }

    public function overview(Request $request)
    {
        $fiscalYear = $request->input('fiscal_year') ? (int) $request->input('fiscal_year') : null;
        return $this->ok($this->service->overview($fiscalYear));
    }
}
