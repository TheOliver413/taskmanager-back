<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            // Validación de datos
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users',
                'password' => 'required|string|min:6'
            ]);

            // Crear usuario
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

            // Generar token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'message' => 'Usuario registrado exitosamente'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['error' => 'Error de validación', 'details' => $e->errors()], 422);
        } catch (QueryException $e) {
            Log::error('Error al registrar usuario: ' . $e->getMessage());
            return response()->json(['error' => 'Error en la base de datos, intenta más tarde'], 500);
        } catch (\Exception $e) {
            Log::error('Error inesperado en registro: ' . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error inesperado'], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['error' => 'Credenciales incorrectas'], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'message' => 'Inicio de sesión exitoso'
            ]);

        } catch (ValidationException $e) {
            return response()->json(['error' => 'Error de validación', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error inesperado en login: ' . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error inesperado'], 500);
        }
    }

    public function me(Request $request)
    {
        try {
            return response()->json($request->user());
        } catch (\Exception $e) {
            Log::error('Error al obtener perfil del usuario: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener datos del usuario'], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();
            return response()->json(['message' => 'Sesión cerrada correctamente']);
        } catch (\Exception $e) {
            Log::error('Error en logout: ' . $e->getMessage());
            return response()->json(['error' => 'Error al cerrar sesión'], 500);
        }
    }
}
