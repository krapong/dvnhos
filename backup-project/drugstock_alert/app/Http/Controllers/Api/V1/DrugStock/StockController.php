<?php

namespace App\Http\Controllers\Api\V1\DrugStock;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\DrugStock\AdjustStockRequest;
use App\Http\Requests\DrugStock\IssueStockRequest;
use App\Http\Requests\DrugStock\ReceiveStockRequest;
use App\Http\Services\DrugStockService;
use Illuminate\Http\Request;

class StockController extends BaseApiController
{
    public function __construct(private readonly DrugStockService $service)
    {
    }

    public function current(Request $request)
    {
        return $this->ok($this->service->currentStocks($request->all()));
    }

    public function history(Request $request)
    {
        return $this->ok($this->service->movements($request->all()));
    }

    public function receive(ReceiveStockRequest $request)
    {
        return $this->ok($this->service->receive($request->validated(), (int) $request->user()->id), 'บันทึกรับเข้าคลังเรียบร้อย');
    }

    public function issue(IssueStockRequest $request)
    {
        return $this->ok($this->service->issue($request->validated(), (int) $request->user()->id), 'บันทึกเบิกยาเรียบร้อย');
    }

    public function adjust(AdjustStockRequest $request)
    {
        return $this->ok($this->service->adjust($request->validated(), (int) $request->user()->id), 'บันทึกตัด stock เรียบร้อย');
    }
}
