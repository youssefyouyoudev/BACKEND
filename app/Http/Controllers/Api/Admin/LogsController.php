<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class LogsController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::query()
            ->with('user')
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')->toString()))
            ->latest()
            ->paginate(30);

        return ActivityLogResource::collection($logs);
    }
}
