<?php
/**
 * R3D KAS Manager – Admin Config Controller
 *
 * @package   r3d-kas-manager
 * @author    Richard Dvořák
 * @version   0.13.0-alpha
 * @date      2025-10-05
 * @license   MIT License
 */

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfigController extends Controller
{
    public function index()
    {
        $settings = AppSetting::pluck('value','key')->toArray();
        return view('config.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'session_timeout'       => 'nullable|integer|min:5|max:240',
            'absolute_session_max'  => 'nullable|integer|min:30|max:1440',
            'support_email'         => 'nullable|email',
            'site_name'             => 'nullable|string|max:255',
            'logo_file'             => 'nullable|file|mimes:svg,png,jpg,jpeg,gif|max:1024',
            'all_inkl_customer_number' => 'nullable|string|max:32',
            'all_inkl_contract_number' => 'nullable|string|max:32',
            'all_inkl_members_login'   => 'nullable|string|max:32',
            'all_inkl_monitor_login'   => 'nullable|string|max:255',
            'hints_show_public_ip'      => 'nullable',
            'hints_show_account_data'   => 'nullable',
            'hints_show_env_versions'   => 'nullable',
            'hints_show_last_reports'   => 'nullable',
            'hints_show_kas_status'     => 'nullable',
            'hints_show_quick_links'    => 'nullable',
            'hints_show_context_help'   => 'nullable',
            'mail_standardfilter_default' => 'nullable|string|max:512',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo_file')) {
            $file = $request->file('logo_file');
            $filename = 'logo_' . time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/logos', $filename);
            $publicUrl = asset('storage/logos/' . $filename);
            AppSetting::set('logo_url', $publicUrl);
        }

        // Save other settings
        $booleanKeys = [
            'hints_show_public_ip',
            'hints_show_account_data',
            'hints_show_env_versions',
            'hints_show_last_reports',
            'hints_show_kas_status',
            'hints_show_quick_links',
            'hints_show_context_help',
        ];

        foreach ($booleanKeys as $key) {
            AppSetting::set($key, $request->has($key) ? '1' : '0');
        }

        foreach ($request->except(array_merge(['_token','logo_file'], $booleanKeys)) as $key => $value) {
            AppSetting::set($key, $value);
        }

        return back()->with('success','Einstellungen gespeichert.');
    }
}
