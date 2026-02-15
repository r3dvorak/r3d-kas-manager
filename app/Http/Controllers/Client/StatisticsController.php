<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\KasSpaceReport;
use App\Services\Kas\StatisticsSnapshotService;
use Illuminate\Support\Facades\Auth;

class StatisticsController extends Controller
{
    public function index()
    {
        $client = Auth::guard('kas_client')->user();
        $kasLogin = strtolower((string) ($client?->account_login ?? ''));

        $latest = KasSpaceReport::where('kas_login', $kasLogin)->orderByDesc('measured_at')->first();

        return view('client.statistics.index', compact('latest', 'client'));
    }

    public function preview(StatisticsSnapshotService $svc)
    {
        $client = Auth::guard('kas_client')->user();
        $diff = $svc->previewSpace((string) $client->account_login, (int) $client->id);
        return view('client.statistics.preview', compact('diff'));
    }

    public function sync(StatisticsSnapshotService $svc)
    {
        $client = Auth::guard('kas_client')->user();
        $svc->syncSpace((string) $client->account_login, (int) $client->id);
        return redirect()->route('client.statistics.index')->with('success', 'Sync abgeschlossen.');
    }
}

