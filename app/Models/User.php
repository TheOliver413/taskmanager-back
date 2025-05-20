<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = ['name', 'email', 'password'];

    public function tasks()
    {
        return DB::select("
            SELECT t.*
            FROM tasks t
            INNER JOIN task_users tu ON tu.task_id = t.id
            WHERE tu.user_id = ?
        ", [$this->id]);
    }

    public static function allUsersWithTaskCount()
    {
        return DB::select("
            SELECT u.id, u.name, u.email,
                (SELECT COUNT(*) FROM task_users tu WHERE tu.user_id = u.id) AS task_count
            FROM users u
            ORDER BY name ASC
        ");
    }

    public static function findUserWithTaskCount($id)
    {
        return DB::selectOne("
            SELECT u.id, u.name, u.email,
                (SELECT COUNT(*) FROM task_users tu WHERE tu.user_id = u.id) AS task_count
            FROM users u
            WHERE u.id = ?
        ", [$id]);
    }
}

