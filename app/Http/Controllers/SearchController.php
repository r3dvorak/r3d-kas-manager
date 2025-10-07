<?php
/**
 * R3D KAS Manager
 * 
 * @package   r3d-kas-manager
 * @author    Richard Dvořák, R3D Internet Dienstleistungen
 * @version   0.6.2-alpha
 * @date      2025-09-26
 * 
 * @copyright (C) 2025 Richard Dvořák
 * @license   MIT License
 * 
 * User Management Controller
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\KasClient;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');

        $users = User::where('name', 'like', "%$q%")
            ->orWhere('login', 'like', "%$q%")
            ->get();

        $clients = KasClient::where('name', 'like', "%$q%")
            ->orWhere('login', 'like', "%$q%")
            ->orWhere('domain', 'like', "%$q%")
            ->get();

        return view('search.results', compact('users','clients','q'));
    }
}
