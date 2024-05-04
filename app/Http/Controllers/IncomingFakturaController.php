<?php

namespace App\Http\Controllers;

use App\Models\IncomingFaktura;
use Illuminate\Http\Request;

class IncomingFakturaController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->all();
        $incoming_faktura = new IncomingFaktura();

        $incoming_faktura->update($data);
        $incoming_faktura->save();

        return response()->json(['message' => 'Incoming invoice added successfully'], 201);
    }

}
