<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DatasetImport;
use App\Models\Election;
use App\Models\ElectionResult;
use App\Models\PollingUnit;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $counts = ElectionResult::statusCounts();
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'elections' => Election::count(),
                'polling_units' => PollingUnit::count(),
                'records' => $counts['total'],
                'valid' => $counts['valid'],
                'invalid' => $counts['invalid'],
                'flagged' => $counts['flagged'],
                'pending' => $counts['pending'],
                'reviewed' => ElectionResult::whereNotNull('reviewed_at')->count(),
            ],
            'elections' => Election::withCount('results')->orderByDesc('year')->get(),
            'recentImports' => DatasetImport::with('election:id,name', 'user:id,name')->latest()->limit(5)->get(),
            'recentReviews' => ElectionResult::with('pollingUnit:id,code,name', 'reviewer:id,name')->whereNotNull('reviewed_at')->latest('reviewed_at')->limit(5)->get(),
            'recentActivity' => AuditLog::with('user:id,name')->latest()->limit(8)->get(),
            'candidateTotals' => ElectionResult::candidateTotals(),
        ]);
    }

    public function auditLogs()
    {
        return Inertia::render('Admin/AuditLogs', [
            'logs' => AuditLog::with('user:id,name')->latest()->paginate(25),
        ]);
    }
}
