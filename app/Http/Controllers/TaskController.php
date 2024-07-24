<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use App\Models\Tag;

class TaskController extends Controller
{
    // Mostrar tareas de un usuario específico o del usuario autenticado
    public function index(Request $request, $userId = null)
    {
        if ($userId) {
            $tasks = Task::where('user_id', $userId)->get();
        } else {
            $tasks = Task::where('user_id', Auth::id())->get();
        }
    
        return view('tasks.index', ['tasks' => $tasks]);
    }

    // Mostrar formulario para crear una nueva tarea
    public function create()
    {
        $tags = Tag::all();
        return view('tasks.create', compact('tags'));
    }

    // Almacenar una nueva tarea en la base de datos
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|string',
            'completed' => 'nullable|boolean',
            'tags' => 'nullable|array',
        ]);

        $task = new Task();
        $task->name = $validatedData['name'];
        $task->description = $validatedData['description'];
        $task->priority = $validatedData['priority'];
        $task->completed = $validatedData['completed'] ?? false; // Default to false if not provided
        $task->user_id = Auth::id();

        $task->save();

        if ($request->has('tags')) {
            $task->tags()->attach($request->tags);
        }

        return redirect()->route('tasks.index')->with('success', 'Tarea creada correctamente.');
    }

    // Mostrar detalles de una tarea específica
    public function show(Task $task)
    {
        $this->authorize('view', $task); // Verificar autorización
        $task->load('user');
        return view('tasks.show', compact('task'));
    }

    // Mostrar formulario para editar una tarea
    public function edit(Task $task)
    {
        $this->authorize('update', $task); // Verificar autorización
        $tags = Tag::all();
        return view('tasks.edit', compact('task', 'tags'));
    }

    // Actualizar una tarea existente en la base de datos
    public function update(Request $request, Task $task)
    {
        $this->authorize('update', $task); // Verificar autorización

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|string',
            'completed' => 'nullable|boolean',
            'tags' => 'nullable|array',
        ]);

        $task->name = $validatedData['name'];
        $task->description = $validatedData['description'];
        $task->priority = $validatedData['priority'];
        $task->completed = $validatedData['completed'] ?? false;

        $task->save();

        if ($request->has('tags')) {
            $task->tags()->sync($request->tags);
        }

        return redirect()->route('tasks.index')->with('success', 'Tarea actualizada correctamente.');
    }

    // Eliminar una tarea de la base de datos
    public function destroy(Task $task)
    {
        $this->authorize('delete', $task); // Verificar autorización

        $task->delete();
        return redirect()->route('tasks.index')->with('success', 'Tarea eliminada correctamente.');
    }

    // Marcar una tarea como completada
    public function complete(Task $task)
    {
        $this->authorize('update', $task); // Verificar autorización

        $task->completed = true;
        $task->save();
        return redirect()->route('tasks.index')->with('success', 'Tarea marcada como completada.');
    }
}
