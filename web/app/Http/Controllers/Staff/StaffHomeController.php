<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StaffHomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('staff.home', [
            'authContext' => $request->attributes->get('queueflow.auth'),
        ]);
    }
}
