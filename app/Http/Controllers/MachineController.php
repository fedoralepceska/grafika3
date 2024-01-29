<?php

namespace App\Http\Controllers;

use App\Enums\JobAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MachineController extends Controller
{
    public function index($machineId)
    {
        return Inertia::render('Production/MachinePage', [
            'machineId' => $machineId,
        ]);
    }
}
