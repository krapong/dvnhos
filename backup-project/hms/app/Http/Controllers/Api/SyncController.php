<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HosxpSyncService;
use Illuminate\Http\JsonResponse;

class SyncController extends Controller
{
    public function __construct(private readonly HosxpSyncService $sync)
    {
    }

    public function run(): JsonResponse
    {
        return response()->json($this->sync->syncPatientCache());
    }
}
