<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Task::with(['assignedTo', 'createdBy', 'relatedCustomer']);

        if ($user->role->isTeller()) {
            $query->where('assigned_to', $user->id);
        } elseif ($user->role->isManager()) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhere('assigned_role', $user->role->value);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $tasks = $query->orderByRaw("FIELD(priority, 'Urgent', 'High', 'Medium', 'Low')")
            ->orderBy('due_at', 'asc')
            ->paginate(20);

        $users = User::where('is_active', true)->get();

        return view('tasks.index', compact('tasks', 'users'));
    }

    public function show(Task $task)
    {
        $task->load(['assignedTo', 'createdBy', 'completedBy', 'relatedCustomer', 'relatedTransaction']);

        return view('tasks.show', compact('task'));
    }

    public function create(Request $request)
    {
        $users = User::where('is_active', true)->get();
        $categories = ['Compliance', 'Customer', 'Operations', 'Admin', 'Approval'];
        $priorities = ['Urgent', 'High', 'Medium', 'Low'];

        $relatedCustomerId = $request->input('customer_id');
        $relatedTransactionId = $request->input('transaction_id');

        return view('tasks.create', compact('users', 'categories', 'priorities', 'relatedCustomerId', 'relatedTransactionId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:Compliance,Customer,Operations,Admin,Approval',
            'priority' => 'required|in:Urgent,High,Medium,Low',
            'assigned_to' => 'nullable|exists:users,id',
            'assigned_role' => 'nullable|string',
            'due_at' => 'nullable|date',
            'related_customer_id' => 'nullable|exists:customers,id',
            'related_transaction_id' => 'nullable|exists:transactions,id',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['status'] = Task::STATUS_PENDING;

        if (! isset($validated['assigned_to']) && ! isset($validated['assigned_role'])) {
            $validated['assigned_to'] = Auth::id();
        }

        $task = Task::create($validated);

        return redirect()->route('tasks.show', $task)
            ->with('success', 'Task created successfully');
    }

    public function acknowledge(Task $task)
    {
        if ($task->acknowledged_at) {
            return back()->with('error', 'Task already acknowledged');
        }

        $task->acknowledge();

        return back()->with('success', 'Task acknowledged');
    }

    public function complete(Request $request, Task $task)
    {
        $validated = $request->validate([
            'completion_notes' => 'nullable|string',
        ]);

        $task->complete($validated['completion_notes'] ?? null);

        return redirect()->route('tasks.index')
            ->with('success', 'Task completed successfully');
    }

    public function cancel(Request $request, Task $task)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $task->update([
            'status' => Task::STATUS_CANCELLED,
            'notes' => $validated['notes'] ?? $task->notes,
        ]);

        return redirect()->route('tasks.index')
            ->with('success', 'Task cancelled');
    }

    public function escalate(Task $task)
    {
        $task->escalate();

        return back()->with('success', 'Task escalated to '.$task->priority);
    }

    public function myTasks()
    {
        $tasks = Task::with(['assignedTo', 'createdBy'])
            ->assignedTo(Auth::id())
            ->whereNotIn('status', [Task::STATUS_COMPLETED, Task::STATUS_CANCELLED])
            ->orderByRaw("FIELD(priority, 'Urgent', 'High', 'Medium', 'Low')")
            ->orderBy('due_at', 'asc')
            ->paginate(20);

        return view('tasks.my-tasks', compact('tasks'));
    }

    public function overdue()
    {
        $tasks = Task::with(['assignedTo', 'createdBy'])
            ->overdue()
            ->orderBy('due_at', 'asc')
            ->paginate(20);

        return view('tasks.overdue', compact('tasks'));
    }

    public function stats()
    {
        $stats = [
            'total' => Task::count(),
            'pending' => Task::pending()->count(),
            'in_progress' => Task::inProgress()->count(),
            'overdue' => Task::overdue()->count(),
            'completed_today' => Task::where('status', Task::STATUS_COMPLETED)
                ->whereDate('completed_at', today())->count(),
        ];

        return response()->json($stats);
    }
}
