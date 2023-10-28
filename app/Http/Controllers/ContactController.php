<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contact;
use Inertia\Inertia;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): \Illuminate\Database\Eloquent\Collection
    {
         return Contact::all();
    }
}
