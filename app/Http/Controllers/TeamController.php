<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class TeamController extends Controller
{
    /**
     * Listar todos los equipos en los que participa el usuario autenticado.
     */
    public function index()
    {
        $teams = Team::whereHas('users', function ($query) {
            $query->where('user_id', Auth::id());
        })
            ->with(['workspace', 'users'])
            ->get();

        return response()->json($teams);
    }

    /**
     * Crear un nuevo equipo en un workspace.
     * El usuario autenticado se agrega como líder.
     */
    public function store(Request $request)
    {
        try {
            // Validar datos de entrada
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'workspace_id' => 'required|exists:workspaces,id',
            ]);

            // Buscar el workspace
            $workspace = Workspace::find($validated['workspace_id']);

            if (!$workspace) {
                return response()->json(['error' => 'El workspace no existe'], 422);
            }

            // Verificar que el usuario autenticado sea creador del workspace
            if ($workspace->created_by !== Auth::id()) {
                return response()->json([
                    'error' => 'No tienes permisos para crear equipos en este workspace'
                ], 403);
            }

            // Crear el equipo
            $team = Team::create([
                'name' => $validated['name'],
                'workspace_id' => $validated['workspace_id'],
            ]);

            // Agregar al creador como líder
            $team->users()->attach(Auth::id(), ['role' => 'leader']);

            return response()->json($team->load('users'), 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Errores de validación
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Cualquier otro error inesperado
            return response()->json([
                'error' => 'Ocurrió un error al crear el equipo',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar un equipo y sus miembros.
     */
    public function show(string $id)
    {
        $team = Team::with(['workspace', 'users'])->findOrFail($id);

        // Validar que el usuario sea miembro del equipo
        if (!$team->users()->where('user_id', Auth::id())->exists()) {
            return response()->json(['error' => 'No perteneces a este equipo'], 403);
        }

        return response()->json($team);
    }

    /**
     * Actualizar datos del equipo (solo líder).
     */
    public function update(Request $request, string $id)
    {
        $team = Team::findOrFail($id);

        // Validar que el usuario sea líder del equipo
        $isLeader = $team->users()
            ->where('user_id', Auth::id())
            ->where('role', 'leader')
            ->exists();

        if (!$isLeader) {
            return response()->json(['error' => 'No tienes permisos para actualizar este equipo'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $team->update($validated);

        return response()->json($team);
    }

    /**
     * Eliminar un equipo (solo líder).
     */
    public function destroy(string $id)
    {
        $team = Team::findOrFail($id);

        // Validar que el usuario sea líder
        $isLeader = $team->users()
            ->where('user_id', Auth::id())
            ->where('role', 'leader')
            ->exists();

        if (!$isLeader) {
            return response()->json(['error' => 'No tienes permisos para eliminar este equipo'], 403);
        }

        $team->delete();

        return response()->json(['message' => 'Equipo eliminado correctamente']);
    }

    /**
     * Añadir miembro al equipo (solo líder).
     */
    public function addMember(Request $request, string $id)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:member,leader',
        ]);

        $team = Team::findOrFail($id);

        // Validar que el usuario autenticado sea líder
        $isLeader = $team->users()
            ->where('user_id', Auth::id())
            ->where('role', 'leader')
            ->exists();

        if (!$isLeader) {
            return response()->json(['error' => 'No tienes permisos para agregar miembros'], 403);
        }

        // Agregar miembro
        $team->users()->syncWithoutDetaching([
            $validated['user_id'] => ['role' => $validated['role']]
        ]);

        return response()->json(['message' => 'Miembro agregado correctamente']);
    }

    /**
     * Quitar miembro del equipo (solo líder).
     */
    public function removeMember(string $id, string $userId)
    {
        $team = Team::findOrFail($id);

        $isLeader = $team->users()
            ->where('user_id', Auth::id())
            ->where('role', 'leader')
            ->exists();

        if (!$isLeader) {
            return response()->json(['error' => 'No tienes permisos para eliminar miembros'], 403);
        }

        $team->users()->detach($userId);

        return response()->json(['message' => 'Miembro eliminado correctamente']);
    }
}
