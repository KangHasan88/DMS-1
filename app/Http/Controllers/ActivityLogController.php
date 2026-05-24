<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request)
    {
        $logs = ActivityLog::with('causer')
            ->when($request->search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhere('log_name', 'like', "%{$search}%")
                      ->orWhere('ip_address', 'like', "%{$search}%")
                      ->orWhereHas('causer', function($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            })
            ->when($request->log_name, function($query, $logName) {
                $query->where('log_name', $logName);
            })
            ->when($request->causer_id, function($query, $causerId) {
                $query->where('causer_id', $causerId);
            })
            ->when($request->date_from, function($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($request->date_to, function($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->when($request->event, function($query, $event) {
                $query->where('event', $event);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get unique log names for filter
        $logNames = ActivityLog::select('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');

        // Get users who have logs
        $users = User::whereHas('activityLogs')->orderBy('name')->get();

        // Statistics
        $stats = [
            'total' => ActivityLog::count(),
            'today' => ActivityLog::whereDate('created_at', today())->count(),
            'this_week' => ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => ActivityLog::whereMonth('created_at', now()->month)->count(),
            'unique_users' => ActivityLog::distinct('causer_id')->count('causer_id'),
        ];

        return view('activity-logs.index', compact('logs', 'logNames', 'users', 'stats'));
    }

    /**
     * Display the specified activity log.
     */
    public function show(ActivityLog $activityLog)
    {
        $activityLog->load('causer', 'subject');
        
        return view('activity-logs.show', compact('activityLog'));
    }

    /**
     * Clear old logs (only for super admin)
     */
    public function clear(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365'
        ]);

        $days = $request->days;
        $date = now()->subDays($days);

        $count = ActivityLog::where('created_at', '<', $date)->delete();

        return redirect()
            ->route('activity-logs.index')
            ->with('success', "Berhasil menghapus {$count} log yang lebih dari {$days} hari.");
    }

    /**
     * Export logs (optional)
     */
    public function export(Request $request)
    {
        // Ini bisa dikembangkan lebih lanjut untuk export CSV/Excel
        return redirect()->route('activity-logs.index')
            ->with('info', 'Fitur export sedang dalam pengembangan.');
    }
}