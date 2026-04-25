<?php

namespace Modules\Domains\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use Illuminate\View\View;

class DomainsController extends Controller
{
    public function index(Hosting $hosting): View
    {
        return view('domains::index', [
            'hosting' => $hosting,
        ]);
    }
}
