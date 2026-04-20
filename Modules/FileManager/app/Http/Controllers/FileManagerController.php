<?php

namespace Modules\FileManager\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hosting;
use Illuminate\View\View;

class FileManagerController extends Controller
{
    public function index(Hosting $hosting): View
    {
        return view('filemanager::index', [
            'hosting' => $hosting,
        ]);
    }
}
