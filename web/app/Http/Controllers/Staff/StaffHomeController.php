<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class StaffHomeController extends Controller
{
    public function __invoke(): View
    {
        return view('staff.home');
    }
}
