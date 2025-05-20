<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class TaskUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $task;
    public $assignedUsers;

    public function __construct($task)
    {
        $this->task = $task;

        // Obtener los usuarios asignados directamente desde la base de datos
        $this->assignedUsers = DB::select("
            SELECT u.id, u.name, u.email
            FROM users u
            INNER JOIN task_users tu ON tu.user_id = u.id
            WHERE tu.task_id = ?
        ", [$task->id]);
    }

    public function broadcastOn()
    {
        return ['tasks-channel'];
    }

    public function broadcastAs()
    {
        return 'TaskUpdatedEvent';
    }
}
