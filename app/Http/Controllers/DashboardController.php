<?php
namespace App\Http\Controllers;
use App\Models\Election;
use App\Models\ElectionResult;
use Inertia\Inertia;
class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard', ['stats' => ElectionResult::statusCounts(), 'elections' => Election::latest()->get()]);
    }
}
