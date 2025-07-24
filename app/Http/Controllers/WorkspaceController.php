<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class WorkspaceController extends Controller
{
    /**
     * Listar todos los workspaces creados por el usuario autenticado.
     */
    public function index()
    {
        $workspaces = Workspace::where('created_by', Auth::id())
            ->with('teams.users', 'tasks')
            ->get();

        return response()->json($workspaces);
    }

    /**
     * Crear un nuevo workspace.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $workspace = Workspace::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'created_by' => Auth::id(),
        ]);

        return response()->json(['success' => true], 201);
    }

    /**
     * Mostrar un workspace específico y sus datos relacionados.
     */
    public function show(string $id)
    {
        $workspace = Workspace::with('teams.users', 'tasks')->findOrFail($id);

        // Validar que el usuario autenticado sea el creador
        if ($workspace->created_by !== Auth::id()) {
            return response()->json(['success' => false, 'error' => 'No tienes permiso para ver este workspace'], 403);
        }

        return response()->json($workspace);
    }

    /**
     * Actualizar un workspace (solo el creador).
     */
    public function update(Request $request, string $id)
    {
        $workspace = Workspace::findOrFail($id);

        if ($workspace->created_by !== Auth::id()) {
            return response()->json(['success' => false, 'error' => 'No tienes permiso para actualizar este workspace'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $workspace->update($validated);

        return response()->json(['success' => true]);
    }

    /**
     * Eliminar un workspace (solo el creador).
     */
    public function destroy(string $id)
    {
        $workspace = Workspace::findOrFail($id);

        if ($workspace->created_by !== Auth::id()) {
            return response()->json(['success' => false, 'error' => 'No tienes permiso para eliminar este workspace'], 403);
        }

        $workspace->delete();

        return response()->json(['success' => true]);
    }
}
