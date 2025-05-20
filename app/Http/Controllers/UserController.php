<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index()
    {
        try {
            $users = User::allUsersWithTaskCount();
            return response()->json($users);
        } catch (\Exception $e) {
            Log::error("Error en index(): " . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error al obtener los usuarios'], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = User::findUserWithTaskCount($id);

            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            return response()->json($user);
        } catch (\Exception $e) {
            Log::error("Error en show() para usuario ID {$id}: " . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error al obtener el usuario'], 500);
        }
    }
}
