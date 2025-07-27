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
     * Mostrar un workspace específico.
     */
    public function show(string $id)
    {
        $workspace = Workspace::with(['teams'])->findOrFail($id);

        // Verificar si el usuario es el creador del workspace
        if ($workspace->created_by === Auth::id()) {
            return response()->json($workspace);
        }

        // Verificar si el usuario es miembro de algún equipo en este workspace
        $isMember = $workspace->teams()->whereHas('users', function ($query) {
            $query->where('user_id', Auth::id());
        })->exists();

        if ($isMember) {
            return response()->json($workspace);
        }

        // Si no es creador ni miembro, denegar acceso
        return response()->json([
            'success' => false,
            'error' => 'No tienes permisos para ver este workspace'
        ], 403);
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

        /**
     * Obtener todas las tareas de un workspace específico.
     */
    public function getTasks(string $id)
    {
        $workspace = Workspace::findOrFail($id);

        // Verificar si el usuario es el creador del workspace
        if ($workspace->created_by === Auth::id()) {
            $tasks = Task::where('workspace_id', $id)
                ->with(['assignedUser', 'creator'])
                ->get();
            return response()->json($tasks);
        }

        // Verificar si el usuario es miembro de algún equipo en este workspace
        $isMember = $workspace->teams()->whereHas('users', function ($query) {
            $query->where('user_id', Auth::id());
        })->exists();

        if ($isMember) {
            $tasks = Task::where('workspace_id', $id)
                ->with(['assignedUser', 'creator'])
                ->get();
            return response()->json($tasks);
        }

        // Si no es creador ni miembro, denegar acceso
        return response()->json([
            'success' => false,
            'error' => 'No tienes permisos para ver las tareas de este workspace'
        ], 403);
    }

    /**
     * Obtener workspaces donde el usuario autenticado es miembro.
     */
    public function getMemberWorkspaces()
    {
        $workspaces = Workspace::whereHas('teams.users', function ($query) {
            $query->where('user_id', Auth::id());
        })
            ->with(['teams' => function ($query) {
                $query->whereHas('users', function ($subQuery) {
                    $subQuery->where('user_id', Auth::id());
                });
            }])
            ->distinct()
            ->get();

        return response()->json($workspaces);
    }
}
