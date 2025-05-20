<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class TaskHistory
{
    public static function getAll()
    {
        return DB::select("
            SELECT th.id, th.task_id, th.action, th.details, th.timestamp,
                json_build_object('id', u.id, 'name', u.name, 'email', u.email) AS user,
                json_build_object('id', t.id, 'title', t.title, 'description', t.description, 'status', t.status) AS task
            FROM task_history th
            INNER JOIN users u ON u.id = th.user_id
            INNER JOIN tasks t ON t.id = th.task_id
            ORDER BY th.timestamp DESC
        ");
    }
}

