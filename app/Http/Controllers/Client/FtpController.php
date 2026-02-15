<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\KasFtpUser;
use App\Services\Kas\FtpSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FtpController extends Controller
{
    public function index(Request $request)
    {
        $kasLogin = (string) (Auth::guard('kas_client')->user()?->account_login ?? '');
        $q = (string) $request->query('q', '');

        $ftpusers = KasFtpUser::query()
            ->where('kas_login', strtolower($kasLogin))
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('ftp_login', 'like', '%' . $q . '%')
                      ->orWhere('ftp_path', 'like', '%' . $q . '%')
                      ->orWhere('ftp_comment', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('ftp_login', 'asc')
            ->paginate(25)
            ->withQueryString();

        return view('client.ftp.index', compact('ftpusers', 'q'));
    }

    public function preview(FtpSnapshotService $svc)
    {
        $kasLogin = (string) (Auth::guard('kas_client')->user()?->account_login ?? '');
        $diff = $svc->previewFtpusers($kasLogin);
        return view('client.ftp.preview', compact('diff'));
    }

    public function sync(FtpSnapshotService $svc)
    {
        $kasLogin = (string) (Auth::guard('kas_client')->user()?->account_login ?? '');
        $res = $svc->syncFtpusers($kasLogin);
        return redirect()->route('client.ftp.index')->with('success', "Sync abgeschlossen. Inserted: {$res['inserted']}.");
    }
}

