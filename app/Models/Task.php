<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class Task
{
    public static function allForUser($userId)
    {
        return DB::select("
        SELECT DISTINCT ON (t.id) t.*,
            CASE WHEN EXISTS (SELECT 1 FROM task_users WHERE task_id = t.id AND user_id = ?) THEN TRUE ELSE FALSE END AS assignedToMe,
            CASE WHEN t.creator_id = ? THEN TRUE ELSE FALSE END AS createdByMe,
            COALESCE(
                (
                    SELECT jsonb_agg(jsonb_build_object('id', u.id, 'name', u.name, 'email', u.email))
                    FROM users u
                    INNER JOIN task_users tu ON tu.user_id = u.id
                    WHERE tu.task_id = t.id
                ), '[]'::jsonb
            ) AS assignedUsers
        FROM tasks t
        WHERE EXISTS (SELECT 1 FROM task_users WHERE task_id = t.id AND user_id = ?) OR t.creator_id = ?
        ORDER BY t.id, t.created_at DESC
    ", [$userId, $userId, $userId, $userId]);
    }

    public static function find($taskId, $userId)
    {
        return DB::selectOne("
            SELECT t.*
            FROM tasks t
            INNER JOIN task_users tu ON tu.task_id = t.id
            WHERE t.id = ? AND tu.user_id = ?
        ", [$taskId, $userId]);
    }

    public static function create($data)
    {
        DB::insert("
            INSERT INTO tasks (title, description, status, creator_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, now(), now())
        ", [
            $data['title'],
            $data['description'],
            $data['status'] ?? 'pendiente',
            $data['creator_id']
        ]);

        $task = DB::selectOne("SELECT * FROM tasks WHERE creator_id = ? ORDER BY created_at DESC LIMIT 1", [$data['creator_id']]);

        // Asignar automáticamente al creador
        self::assignUser($task->id, $data['creator_id']);

        return $task;
    }

    public static function updateTask($taskId, $data)
    {
        DB::update("
            UPDATE tasks
            SET title = ?, description = ?, status = ?, updated_at = now()
            WHERE id = ?
        ", [$data['title'], $data['description'], $data['status'], $taskId]);

        return DB::selectOne("SELECT * FROM tasks WHERE id = ?", [$taskId]);
    }

    public static function deleteTask($taskId, $userId)
    {
        // Registrar la eliminación en el historial sin borrar la tarea
        DB::insert("
            INSERT INTO task_history (task_id, user_id, action, details, timestamp)
            VALUES (?, ?, 'eliminada', ?, now())
        ", [$taskId, $userId, "Tarea marcada como eliminada por usuario ID " . $userId]);

        // Marcar la tarea como eliminada en lugar de borrarla
        return DB::update("
            UPDATE tasks
            SET status = 'eliminada', updated_at = now()
            WHERE id = ?
        ", [$taskId]);
    }

    public static function assignUser($taskId, $userId)
    {
        // Verificar si el usuario ya está asignado
        $alreadyAssigned = DB::selectOne("
            SELECT * FROM task_users WHERE task_id = ? AND user_id = ?
        ", [$taskId, $userId]);

        if ($alreadyAssigned) {
            return false;
        }

        // Asignar usuario a la tarea
        DB::insert("
            INSERT INTO task_users (task_id, user_id) VALUES (?, ?)
        ", [$taskId, $userId]);

        // Registrar en el historial
        DB::insert("
            INSERT INTO task_history (task_id, user_id, action, details, timestamp)
            VALUES (?, ?, 'asignada', ?, now())
        ", [$taskId, $userId, "Usuario ID " . $userId . " asignado a tarea"]);

        return true;
    }

    public static function getAssignedUsers($taskId)
    {
        return DB::select("
            SELECT u.id, u.name, u.email
            FROM users u
            INNER JOIN task_users tu ON tu.user_id = u.id
            WHERE tu.task_id = ?
        ", [$taskId]);
    }
}
