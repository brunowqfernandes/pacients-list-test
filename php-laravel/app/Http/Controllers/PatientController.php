<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{

    public function index()
    {
        $patients = Patient::all();

        return response()->json($patients);
    }


    public function show(int $id)
    {
        $patient = Patient::findOrFail($id);

        return response()->json($patient);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cpf' => [
                'required',
                'string',
                'max:14',
                Rule::unique('patients', 'cpf'),
            ],
            'rg' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'biological_sex' => ['required', Rule::in(['M', 'F'])],
            'cellphone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $patient = Patient::create($validated);

        return response()->json($patient, 201);
    }

    public function update(Request $request, int $id)
    {
        $patient = Patient::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cpf' => [
                'required',
                'string',
                'max:14',
                Rule::in([$patient->cpf]),
            ],
            'rg' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'biological_sex' => ['required', Rule::in(['M', 'F'])],
            'cellphone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $patient->update($validated);

        return response()->json($patient->fresh());
    }



    public function destroy(int $id)
    {
        $patient = Patient::findOrFail($id);

        $patient->delete();

        return response()->noContent();
    }

    public function trashed()
    {
        $patients = Patient::onlyTrashed()->get();

        return response()->json($patients);
    }

    public function restore(int $id)
    {
        $patient = Patient::withTrashed()->findOrFail($id);

        $patient->restore();

        return response()->json($patient->fresh());
    }
}
