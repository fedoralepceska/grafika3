<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ActionController extends Controller
{
    public function index($actionId)
    {
        return Inertia::render('Production/ActionPage', [
        'actionId' => $actionId,
        ]);
    }
}
