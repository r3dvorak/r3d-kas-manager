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
 * app\Http\Controllers\DnsController.php
 */

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;

class DnsController extends Controller
{
    public function index()
    {
        return view('client.dns.index');
    }
}
