<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TaskHistory;
use Illuminate\Support\Facades\Log;

class TaskHistoryController extends Controller
{
    public function index()
    {
        try {
            $history = TaskHistory::getAll();
            return response()->json($history);
        } catch (\Exception $e) {
            Log::error("Error en index() - Historial de tareas: " . $e->getMessage());
            return response()->json(['error' => 'Error al obtener el historial de tareas'], 500);
        }
    }
}
