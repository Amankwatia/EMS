<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class BackupGuideController extends Controller
{
    public function __invoke()
    {
        return view('admin.backup-guide');
    }
}
