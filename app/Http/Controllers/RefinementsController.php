<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class RefinementsController extends Controller
{
    public function index()
    {
        //
        return Inertia::render('Refinements/Index');

    }
}
