<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramNotificationService;
use Illuminate\Http\JsonResponse;

class TelegramController extends Controller
{
    public function __construct(private readonly TelegramNotificationService $telegram)
    {
    }

    public function test(): JsonResponse
    {
        return response()->json($this->telegram->send('Hospital Meal System test notification.'));
    }
}
