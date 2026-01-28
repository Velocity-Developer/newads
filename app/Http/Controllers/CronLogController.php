<?php

namespace App\Http\Controllers;

use App\Models\CronLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CronLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $logs = CronLog::orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        return Inertia::render('CronLogs/Index', [
            'logs' => $logs,
        ]);
    }

    /**
     * Remove all resources from storage.
     */
    public function clear()
    {
        CronLog::truncate();

        return redirect()->back()->with('success', 'All cron logs have been cleared.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $log = CronLog::findOrFail($id);
        $log->delete();

        return redirect()->back()->with('success', 'Cron log deleted successfully.');
    }
}
