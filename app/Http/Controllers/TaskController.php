<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Models\Task;
use App\Events\TaskUpdated;

class TaskController extends Controller
{
    public function index()
    {
        try {
            $tasks = Task::allForUser(Auth::id());
            return response()->json($tasks);
        } catch (\Exception $e) {
            Log::error("Error en index(): " . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error al obtener las tareas'], 500);
        }
    }

    public function show($id)
    {
        try {
            $task = Task::find($id, Auth::id());

            if (!$task) {
                return response()->json(['error' => 'Tarea no encontrada o sin acceso'], 404);
            }

            return response()->json($task);
        } catch (\Exception $e) {
            Log::error("Error al obtener tarea ID {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error al obtener la tarea'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'nullable|string'
            ]);

            DB::beginTransaction(); // Inicia transacción

            // Crear la tarea
            $task = Task::create([
                'title' => $request->title,
                'description' => $request->description,
                'status' => $request->status ?? 'pendiente',
                'creator_id' => Auth::id()
            ]);

            // Verificar si el usuario ya está asignado a esta tarea
            $alreadyAssigned = DB::selectOne("
            SELECT * FROM task_users WHERE task_id = ? AND user_id = ?
        ", [$task->id, Auth::id()]);

            if (!$alreadyAssigned) {
                // Asignar la tarea al creador en la tabla `task_users`
                DB::insert("
                INSERT INTO task_users (task_id, user_id) VALUES (?, ?)
            ", [$task->id, Auth::id()]);
            }

            // Registrar en `task_history` que la tarea fue creada
            DB::insert("
            INSERT INTO task_history (task_id, user_id, action, details, timestamp)
            VALUES (?, ?, 'creada', ?, now())
        ", [$task->id, Auth::id(), "Tarea '{$task->title}' creada por usuario ID " . Auth::id()]);

            DB::commit(); // Confirma transacción

            return response()->json($task, 201);
        } catch (ValidationException $e) {
            DB::rollBack(); // Revierte cambios si hay error
            return response()->json(['error' => 'Error de validación', 'details' => $e->errors()], 422);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error('Error en store(): ' . $e->getMessage());
            return response()->json(['error' => 'Error en la base de datos, intenta más tarde'], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error inesperado en store(): ' . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error inesperado'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $task = Task::find($id, Auth::id());

            if (!$task) {
                return response()->json(['error' => 'Tarea no encontrada o sin acceso'], 404);
            }

            $changes = [];

            if ($request->has('title') && $request->title !== $task->title) {
                $changes[] = "Título cambiado de '{$task->title}' a '{$request->title}'";
            }
            if ($request->has('description') && $request->description !== $task->description) {
                $changes[] = "Descripción cambiada de '{$task->description}' a '{$request->description}'";
            }
            if ($request->has('status') && $request->status !== $task->status) {
                $changes[] = "Estado cambiado de '{$task->status}' a '{$request->status}'";
            }

            if (empty($changes)) {
                return response()->json(['message' => 'No se realizaron cambios en la tarea'], 200);
            }

            DB::beginTransaction();

            Task::updateTask($id, [
                'title' => $request->title ?? $task->title,
                'description' => $request->description ?? $task->description,
                'status' => $request->status ?? $task->status,
            ]);

            DB::insert("
            INSERT INTO task_history (task_id, user_id, action, details, timestamp)
            VALUES (?, ?, 'actualizada', ?, now())
        ", [$id, Auth::id(), implode(' | ', $changes)]);

            // Emitir evento para actualizar la tarea en tiempo real
            event(new TaskUpdated(Task::find($id, Auth::id())));

            DB::commit();

            return response()->json([
                'message' => 'Tarea actualizada correctamente',
                'task' => Task::find($id, Auth::id())
            ]);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error("Error en update() para tarea ID {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Error en la base de datos, intenta más tarde'], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error inesperado en update() para tarea ID {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error inesperado'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $task = Task::find($id, Auth::id());

            if (!$task) {
                return response()->json(['error' => 'Solo el creador puede eliminar esta tarea'], 403);
            }

            DB::beginTransaction();

            // Registrar en `task_history` que la tarea fue eliminada
            DB::insert("
            INSERT INTO task_history (task_id, user_id, action, details, timestamp)
            VALUES (?, ?, 'eliminada', ?, now())
        ", [$id, Auth::id(), "Tarea '{$task->title}' eliminada por usuario ID " . Auth::id()]);

            // Actualizar estado de la tarea en lugar de borrarla
            DB::update("
            UPDATE tasks SET status = 'eliminada', updated_at = now() WHERE id = ?
        ", [$id]);

            DB::commit();

            return response()->json(['message' => 'Tarea marcada como eliminada']);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error("Error en destroy() para tarea ID {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Error en la base de datos, intenta más tarde'], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error inesperado en destroy() para tarea ID {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error inesperado'], 500);
        }
    }

    public function assign(Request $request, $id)
    {
        try {
            // Validar que la tarea existe
            $task = DB::selectOne("SELECT * FROM tasks WHERE id = ?", [$id]);

            if (!$task) {
                return response()->json(['error' => 'Tarea no encontrada o sin acceso'], 404);
            }

            // Validar la lista de usuarios
            $request->validate([
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'exists:users,id'
            ]);

            DB::beginTransaction();

            // Asignar cada usuario a la tarea, evitando duplicados
            foreach ($request->user_ids as $userId) {
                DB::insert("
                INSERT INTO task_users (task_id, user_id) VALUES (?, ?)
                ON CONFLICT (task_id, user_id) DO NOTHING
            ", [$id, $userId]);

                // Registrar en el historial
                DB::insert("
                INSERT INTO task_history (task_id, user_id, action, details, timestamp)
                VALUES (?, ?, 'asignada', ?, now())
            ", [$id, Auth::id(), "Usuario ID " . $userId . " asignado a tarea"]);
            }

            DB::commit();

            // Obtener la lista actualizada de usuarios asignados
            $assignedUsers = DB::select("
            SELECT u.id, u.name, u.email
            FROM users u
            INNER JOIN task_users tu ON tu.user_id = u.id
            WHERE tu.task_id = ?
        ", [$id]);

            // Emitir el evento para actualización en tiempo real
            event(new TaskUpdated($task, $assignedUsers));

            return response()->json([
                'message' => 'Usuarios asignados correctamente a la tarea',
                'task' => $task,
                'assigned_users' => $assignedUsers
            ]);
        } catch (QueryException $e) {
            DB::rollBack();
            Log::error("Error en assign() para tarea ID {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Error en la base de datos, intenta más tarde'], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error inesperado en assign() para tarea ID {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error inesperado'], 500);
        }
    }
}
