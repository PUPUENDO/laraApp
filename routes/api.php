<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TaskController;

// Rutas públicas (registro y login)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Rutas de recuperación de contraseña
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Rutas protegidas por Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Datos del usuario autenticado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Obtener todos los usuarios
    Route::get('/users', [AuthController::class, 'getAllUsers']);

    // Workspaces
    Route::apiResource('workspaces', WorkspaceController::class);
    Route::get('workspaces/{id}/tasks', [WorkspaceController::class, 'getTasks']);

    // Teams
    Route::apiResource('teams', TeamController::class);
    Route::get('teams/{id}/members', [TeamController::class, 'getMembers']);
    Route::get('teams/{id}/tasks', [TeamController::class, 'getTasks']);
    Route::post('teams/{id}/add-member', [TeamController::class, 'addMember']);
    Route::delete('teams/{id}/remove-member/{userId}', [TeamController::class, 'removeMember']);

    // Tasks
    Route::apiResource('tasks', TaskController::class);
});
