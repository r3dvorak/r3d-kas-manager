<?php
/**
 * R3D KAS Manager – Domain Controller (Client)
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.7.0-alpha
 * @date      2025-09-27
 * @license   MIT License
 * 
 * app\Http\Controllers\MailboxController.php
 */

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\KasClient;
use App\Models\KasMailAccount;
use App\Services\Kas\MailSnapshotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MailboxController extends Controller
{
    public function index(Request $request)
    {
        $kasLogin = (string) (Auth::guard('kas_client')->user()?->account_login ?? '');
        $q = $request->query('q');
        $domain = (string) $request->query('domain', '');

        $client = KasClient::where('account_login', strtolower($kasLogin))->first();
        $domainOptions = $client?->domains()->orderBy('domain_full', 'asc')->pluck('domain_full')->all() ?? [];

        $base = KasMailAccount::query()
            ->where('kas_login', strtolower($kasLogin))
            ->when($q, fn($qq) => $qq->where('email', 'like', '%' . $q . '%'))
            ->when($domain !== '', fn($qq) => $qq->where('domain', strtolower($domain)))
            ->orderBy('domain', 'asc')
            ->orderBy('email', 'asc');

        $domainTotals = (clone $base)
            ->reorder()
            ->selectRaw('domain, count(*) as cnt')
            ->groupBy('domain')
            ->pluck('cnt', 'domain')
            ->all();

        // Totals across all matching mailboxes (not just the current page).
        $totalUsedMb = (clone $base)
            ->reorder()
            ->where('status', 'active')
            ->get(['data_json'])
            ->sum(function ($row) {
                $json = $row->data_json ?? null;
                $v = is_array($json) ? ($json['used_mailaccount_space'] ?? null) : null;
                return (is_numeric($v) ? (float) $v : 0.0);
            });

        $totalUsedGb = $totalUsedMb > 0 ? round($totalUsedMb / 1024, 2) : 0.0;

        $mailboxes = $base->paginate(25)->withQueryString();

        return view('client.mailboxes.index', compact('mailboxes', 'q', 'domain', 'domainOptions', 'domainTotals', 'kasLogin', 'client', 'totalUsedGb', 'totalUsedMb'));
    }

    public function preview(MailSnapshotService $svc)
    {
        $kasLogin = (string) (Auth::guard('kas_client')->user()?->account_login ?? '');
        $diff = $svc->previewMailboxes($kasLogin);
        return view('client.mailboxes.preview', compact('diff', 'kasLogin'));
    }

    public function sync(MailSnapshotService $svc)
    {
        $kasLogin = (string) (Auth::guard('kas_client')->user()?->account_login ?? '');
        $svc->syncMailboxes($kasLogin);
        return redirect()->route('client.mailboxes.index')->with('success', 'Sync abgeschlossen.');
    }
}
