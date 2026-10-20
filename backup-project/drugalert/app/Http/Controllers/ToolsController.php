<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Box;
use App\Models\Drug;
use App\Models\Location;
use Illuminate\Http\Request;

class ToolsController extends Controller
{
    public function index(Request $request)
    {
        return view('tools', [
            'user' => $request->user(),
            'summary' => [
                'boxes' => $this->activeQuery(Box::class)->count(),
                'drugs' => $this->activeQuery(Drug::class)->count(),
                'locations' => $this->activeQuery(Location::class)->count(),
                'openAlerts' => Alert::whereNull('resolved_at')->count(),
            ],
            'auditLogs' => AuditLog::orderByDesc('created_at')->take(10)->get(),
        ]);
    }
}
