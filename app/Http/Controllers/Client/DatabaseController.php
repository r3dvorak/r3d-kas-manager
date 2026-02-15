<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\KasDatabase;
use App\Services\Kas\DatabaseSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DatabaseController extends Controller
{
    public function index(Request $request)
    {
        $kasLogin = (string) (Auth::guard('kas_client')->user()?->account_login ?? '');
        $q = (string) $request->query('q', '');

        $databases = KasDatabase::query()
            ->where('kas_login', strtolower($kasLogin))
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('database_login', 'like', '%' . $q . '%')
                      ->orWhere('database_comment', 'like', '%' . $q . '%')
                      ->orWhere('database_allowed_hosts', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('database_login', 'asc')
            ->paginate(25)
            ->withQueryString();

        return view('client.databases.index', compact('databases', 'q'));
    }

    public function preview(DatabaseSnapshotService $svc)
    {
        $kasLogin = (string) (Auth::guard('kas_client')->user()?->account_login ?? '');
        $diff = $svc->previewDatabases($kasLogin);
        return view('client.databases.preview', compact('diff'));
    }

    public function sync(DatabaseSnapshotService $svc)
    {
        $kasLogin = (string) (Auth::guard('kas_client')->user()?->account_login ?? '');
        $res = $svc->syncDatabases($kasLogin);
        return redirect()->route('client.databases.index')->with('success', "Sync abgeschlossen. Inserted: {$res['inserted']}.");
    }
}

