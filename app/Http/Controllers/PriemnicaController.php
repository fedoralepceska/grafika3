<?php

namespace App\Http\Controllers;

use App\Models\Priemnica;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PriemnicaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return Inertia::render('Priemnica/Index');

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Priemnica/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $priemnica = new Priemnica();

        $priemnica->update($data);
        $priemnica->save();

        return response()->json(['message' => 'Receipt added successfully'], 201);
    }

}
