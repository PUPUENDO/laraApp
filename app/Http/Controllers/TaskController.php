<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Team;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class TaskController extends Controller
{
    /**
     * Listar todas las tareas del usuario autenticado.
     */
    public function index()
    {
        // Muestra todas las tareas asignadas al usuario
        $tasks = Task::where('assigned_to', Auth::id())
            ->with('workspace', 'assignedUser', 'creator')
            ->get();

        return response()->json($tasks);
    }

    /**
     * Crear una nueva tarea (solo líderes del equipo).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'workspace_id' => 'required|exists:workspaces,id',
            'team_id' => 'required|exists:teams,id',
            'assigned_to' => 'required|exists:users,id',
        ]);

        // Verificar que el usuario autenticado sea líder del equipo
        $team = Team::findOrFail($validated['team_id']);

        $isLeader = $team->users()
            ->where('user_id', Auth::id())
            ->where('role', 'leader')
            ->exists();

        if (!$isLeader) {
            return response()->json(['success' => false, 'error' => 'No tienes permisos para crear tareas en este equipo'], 403);
        }

        // Crear la tarea
        $task = Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'progress' => 0,
            'is_done' => false,
            'workspace_id' => $validated['workspace_id'],
            'assigned_to' => $validated['assigned_to'],
            'created_by' => Auth::id(),
        ]);

        return response()->json(['success' => true], 201);
    }

    /**
     * Ver una tarea.
     */
    public function show(string $id)
    {
        $task = Task::with('workspace', 'assignedUser', 'creator')->findOrFail($id);

        // Validar que el usuario pueda ver la tarea (si es miembro del equipo o líder)
        if (
            $task->assigned_to !== Auth::id() &&
            $task->creator->id !== Auth::id()
        ) {
            return response()->json(['success' => false, 'error' => 'No tienes permisos para ver esta tarea'], 403);
        }

        return response()->json($task);
    }

    /**
     * Actualizar una tarea.
     * Líder: puede editar título, descripción, asignación.
     * Miembro asignado: puede actualizar progreso e is_done.
     */
    public function update(Request $request, string $id)
    {
        $task = Task::findOrFail($id);

        // ¿Es líder?
        $isLeader = $task->workspace->teams()
            ->whereHas('users', function ($query) {
                $query->where('user_id', Auth::id())
                      ->where('role', 'leader');
            })->exists();

        // ¿Es el miembro asignado?
        $isAssigned = $task->assigned_to === Auth::id();

        if (!$isLeader && !$isAssigned) {
            return response()->json(['success' => false, 'error' => 'No tienes permisos para actualizar esta tarea'], 403);
        }

        if ($isLeader) {
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'assigned_to' => 'sometimes|exists:users,id',
                'progress' => 'nullable|integer|min:0|max:100',
                'is_done' => 'nullable|boolean',
            ]);

            $task->update($validated);
        } elseif ($isAssigned) {
            // El miembro solo puede cambiar progreso e is_done
            $validated = $request->validate([
                'progress' => 'required|integer|min:0|max:100',
                'is_done' => 'nullable|boolean',
            ]);

            $task->update($validated);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Eliminar una tarea (solo líder).
     */
    public function destroy(string $id)
    {
        $task = Task::findOrFail($id);

        // ¿Es líder?
        $isLeader = $task->workspace->teams()
            ->whereHas('users', function ($query) {
                $query->where('user_id', Auth::id())
                      ->where('role', 'leader');
            })->exists();

        if (!$isLeader) {
            return response()->json(['success' => false, 'error' => 'No tienes permisos para eliminar esta tarea'], 403);
        }

        $task->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Obtener todas las tareas de un workspace (para líderes).
     */
    public function getWorkspaceTasks($workspaceId)
    {
        // Verificar que el workspace existe
        $workspace = Workspace::findOrFail($workspaceId);
        
        // Verificar que el usuario tenga permisos para ver las tareas del workspace
        $hasAccess = $workspace->created_by === Auth::id() || 
                     $workspace->teams()->whereHas('users', function ($query) {
                         $query->where('user_id', Auth::id());
                     })->exists();

        if (!$hasAccess) {
            return response()->json([
                'success' => false, 
                'error' => 'No tienes permisos para ver las tareas de este workspace'
            ], 403);
        }

        // Obtener todas las tareas del workspace
        $tasks = Task::where('workspace_id', $workspaceId)
            ->with(['workspace', 'assignedUser', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tasks);
    }

    /**
     * Obtener todas las tareas de un equipo (para miembros del equipo).
     */
    public function getTeamTasks($teamId)
    {
        // Verificar que el equipo existe
        $team = Team::findOrFail($teamId);
        
        // Verificar que el usuario sea miembro del equipo
        $isMember = $team->users()->where('user_id', Auth::id())->exists();

        if (!$isMember) {
            return response()->json([
                'success' => false, 
                'error' => 'No tienes permisos para ver las tareas de este equipo'
            ], 403);
        }

        // Obtener todas las tareas del workspace del equipo
        $tasks = Task::where('workspace_id', $team->workspace_id)
            ->with(['workspace', 'assignedUser', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tasks);
    }
}
