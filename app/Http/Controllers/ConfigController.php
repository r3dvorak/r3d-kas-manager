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

use App\Http\Requests\UpdateConfigRequest;
use App\Models\AppSetting;

class ConfigController extends Controller
{
    public function index()
    {
        $settings = AppSetting::pluck('value','key')->toArray();
        return view('config.index', compact('settings'));
    }

    public function update(UpdateConfigRequest $request)
    {
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
