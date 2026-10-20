<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MedicalRequest;
use App\Models\AuditLog;
use App\Models\Feedback;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RequestController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user) return response()->json([], 401);
        
        $query = MedicalRequest::query();
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }
        
        return $query->latest()->get();
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $requestId = 'REQ-' . strtoupper(Str::random(8));
        
        $data = $request->validate([
            'department' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'target_date' => 'required|date',
            'type' => 'required',
            'columns' => 'required',
            'details' => 'nullable',
            'purpose' => 'nullable',
            'requester_name' => 'required',
            'contact_number' => 'required',
            'fiscal_year' => 'required',
            'delivery_channel' => 'required',
        ]);

        $medicalRequest = MedicalRequest::create(array_merge($data, [
            'request_id' => $requestId,
            'user_id' => $user->id,
            'status' => 'Pending'
        ]));

        AuditLog::create([
            'username' => $user->username,
            'action' => 'CREATE_REQUEST',
            'details' => "ส่งคำขอ ID: $requestId"
        ]);

        return response()->json(['success' => true, 'id' => $requestId]);
    }

    public function getDashboardData()
    {
        $requests = MedicalRequest::all();
        $total = $requests->count();
        
        $deptCounts = $requests->countBy('department');
        
        $feedbacks = Feedback::all();
        $avgRating = $feedbacks->avg('rating') ?: 0;
        
        return response()->json([
            'labels' => $deptCounts->keys(),
            'counts' => $deptCounts->values(),
            'total' => $total,
            'avgRating' => number_format((float)$avgRating, 1, '.', ''),
            'totalFeedback' => $feedbacks->count(),
            'recentFeedback' => Feedback::latest()->take(10)->get()
        ]);
    }

    public function submitFeedback(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'requestId' => 'required',
            'rating' => 'required|integer',
            'comment' => 'nullable',
            'dept' => 'required'
        ]);

        Feedback::create([
            'request_id_str' => $data['requestId'],
            'username' => $user->username,
            'rating' => $data['rating'],
            'comment' => $data['comment'],
            'department' => $data['dept']
        ]);

        AuditLog::create([
            'username' => $user->username,
            'action' => 'FEEDBACK',
            'details' => "ส่งประเมินความพึงพอใจ: {$data['rating']} ดาว"
        ]);

        return response()->json(['success' => true]);
    }
}
