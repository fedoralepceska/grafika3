<?php

namespace App\Http\Controllers;

use App\Enums\JobAction;
use App\Models\MachinesCut;
use App\Models\MachinesPrint;
use App\Models\Warehouse;
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

    public function getMachines()
    {
        return response()->json([
            'machinesCut' => MachinesCut::all(),
            'machinesPrint' => MachinesPrint::all()
        ]);
    }

    public function createMachine(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'cut' => 'nullable|boolean',
            'print' => 'nullable|boolean'
        ]);

        if ($validatedData['cut'] === true) {
            $machine = new MachinesCut();
            $machine->name = $validatedData['name'];
            $machine->save();
        }
        if ($validatedData['print'] === true) {
            $machine = new MachinesPrint();
            $machine->name = $validatedData['name'];
            $machine->save();
        }

        return response()->json([
            'message' => 'Machine created successfully!',
        ], 201);
    }
    public function deleteMachine($id)
    {
        $machineCut = MachinesCut::find($id);

        if ($machineCut) {
            $machineCut->delete();
        }
        else {
            $machinePrint = MachinesPrint::find($id);
            $machinePrint->delete();
        }
        return response()->json(['message' => 'Machine deleted successfully!'], 200);
    }
}
