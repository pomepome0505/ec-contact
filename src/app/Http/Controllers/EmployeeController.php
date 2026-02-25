<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employeeService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Employee/Index', [
            'employees' => $this->employeeService->getList(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Employee/Create');
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $this->employeeService->store($request->validated());

        return redirect()->route('employees.index');
    }

    public function edit(int $employeeId): Response
    {
        $user = User::findOrFail($employeeId);

        return Inertia::render('Employee/Edit', [
            'employee' => [
                'id' => $user->id,
                'login_id' => $user->login_id,
                'name' => $user->name,
                'is_active' => $user->is_active,
                'is_admin' => $user->is_admin,
            ],
        ]);
    }

    public function update(UpdateEmployeeRequest $request, int $employeeId): RedirectResponse
    {
        $user = User::findOrFail($employeeId);
        $this->employeeService->update($user, $request->validated());

        return redirect()->route('employees.index');
    }

    public function toggleActive(int $employeeId): RedirectResponse
    {
        $user = User::findOrFail($employeeId);

        /** @var int $currentUserId */
        $currentUserId = Auth::id();

        try {
            $this->employeeService->toggleActive($user, $currentUserId);
        } catch (\LogicException $e) {
            return back()->withErrors(['toggleActive' => $e->getMessage()]);
        }

        return redirect()->route('employees.index');
    }

    public function destroy(int $employeeId): RedirectResponse
    {
        $user = User::findOrFail($employeeId);

        /** @var int $currentUserId */
        $currentUserId = Auth::id();

        try {
            $this->employeeService->delete($user, $currentUserId);
        } catch (\LogicException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        return redirect()->route('employees.index');
    }

    public function resetPassword(int $employeeId): JsonResponse
    {
        $user = User::findOrFail($employeeId);

        /** @var int $currentUserId */
        $currentUserId = Auth::id();

        try {
            $password = $this->employeeService->resetPassword($user, $currentUserId);
        } catch (\LogicException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json(['password' => $password]);
    }
}
