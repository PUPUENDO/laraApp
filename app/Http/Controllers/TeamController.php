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
                return response()->json(['success' => false, 'error' => 'El workspace no existe'], 422);
            }

            // Verificar que el usuario autenticado sea creador del workspace
            if ($workspace->created_by !== Auth::id()) {
                return response()->json([
                    'success' => false,
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

            return response()->json(['success' => true], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Errores de validación
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Cualquier otro error inesperado
            return response()->json([
                'success' => false,
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
            return response()->json(['success' => false, 'error' => 'No perteneces a este equipo'], 403);
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
            return response()->json(['success' => false, 'error' => 'No tienes permisos para actualizar este equipo'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $team->update($validated);

        return response()->json(['success' => true]);
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
            return response()->json(['success' => false, 'error' => 'No tienes permisos para eliminar este equipo'], 403);
        }

        $team->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Agregar miembro al equipo (solo líder).
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
            return response()->json(['success' => false, 'error' => 'No tienes permisos para agregar miembros'], 403);
        }

        // Verificar que el usuario no esté ya en el equipo
        if ($team->users()->where('user_id', $validated['user_id'])->exists()) {
            return response()->json(['success' => false, 'error' => 'El usuario ya es miembro del equipo'], 409);
        }

        // Agregar miembro
        $team->users()->attach($validated['user_id'], ['role' => $validated['role']]);

        return response()->json(['success' => true, 'message' => 'Miembro agregado exitosamente']);
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
            return response()->json(['success' => false, 'error' => 'No tienes permisos para eliminar miembros'], 403);
        }

        // Verificar que el usuario esté en el equipo
        if (!$team->users()->where('user_id', $userId)->exists()) {
            return response()->json(['success' => false, 'error' => 'El usuario no es miembro del equipo'], 404);
        }

        // No permitir que el líder se remueva a sí mismo si es el único líder
        if ($userId == Auth::id()) {
            $leaderCount = $team->users()->where('role', 'leader')->count();
            if ($leaderCount <= 1) {
                return response()->json(['success' => false, 'error' => 'No puedes removerte como último líder del equipo'], 400);
            }
        }

        $team->users()->detach($userId);

        return response()->json(['success' => true, 'message' => 'Miembro removido exitosamente']);
    }

    /**
     * Obtener solo los miembros de un equipo.
     */
    public function getMembers(string $id)
    {
        $team = Team::findOrFail($id);

        // Validar que el usuario sea miembro del equipo
        if (!$team->users()->where('user_id', Auth::id())->exists()) {
            return response()->json(['success' => false, 'error' => 'No perteneces a este equipo'], 403);
        }

        $members = $team->users()->select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'team_user.role')->get();

        return response()->json($members);
    }

    /**
     * Obtener todas las tareas de un equipo específico.
     */
    public function getTasks(string $id)
    {
        $team = Team::findOrFail($id);

        // Validar que el usuario sea miembro del equipo
        if (!$team->users()->where('user_id', Auth::id())->exists()) {
            return response()->json(['success' => false, 'error' => 'No perteneces a este equipo'], 403);
        }

        // Obtener las tareas del workspace de este equipo
        $tasks = Task::where('workspace_id', $team->workspace_id)
            ->with(['assignedUser', 'creator'])
            ->get();

        return response()->json($tasks);
    }
}
