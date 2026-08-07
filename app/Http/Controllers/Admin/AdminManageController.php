<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminManageController extends Controller
{
    /**
     * Display the User Management dashboard.
     */
    public function index()
    {
        // Require manage-settings permission (same as Role management)
        $this->authorize('manage-settings');
        
        $roles = Role::all();
        $teams = \App\Models\Team::orderBy('name')->get(['id', 'name']);
        return view('admin.users.user_management', compact('roles', 'teams'));
    }

    /**
     * Get user's managed teams.
     */
    public function getUserTeams(User $user)
    {
        $this->authorize('manage-settings');
        return response()->json($user->managedTeams->pluck('id'));
    }

    /**
     * Get users data for DataTable.
     */
    public function data()
    {
        $this->authorize('manage-settings');

        $users = User::with('roles', 'employee')->select('users.*');

        return DataTables::of($users)
            ->addIndexColumn()
            ->addColumn('roles_list', function($user) {
                return $user->roles->pluck('name')->map(function($role) {
                    $class = $role === 'admin' ? 'bg-danger' : ($role === 'manager' ? 'bg-primary' : 'bg-info');
                    return '<span class="badge ' . $class . '">' . ucfirst($role) . '</span>';
                })->implode(' ');
            })
            ->addColumn('linked_personnel', function($user) {
                $name = $user->employee ? $user->employee->name : $user->name;
                return '<div>
                            <div class="fw-bold">' . $name . '</div>
                            <div class="text-muted small">' . $user->email . '</div>
                        </div>';
            })
            ->filterColumn('linked_personnel', function($query, $keyword) {
                $query->where(function($q) use ($keyword) {
                    $q->where('users.name', 'like', "%{$keyword}%")
                      ->orWhere('users.email', 'like', "%{$keyword}%")
                      ->orWhereHas('employee', function($q2) use ($keyword) {
                          $q2->where('name', 'like', "%{$keyword}%");
                      });
                });
            })
            ->addColumn('action', function ($user) {
                $promoteBtn = '';
                $deleteBtn = '';
                
                // Only show delete/reset for Admins in this view, or allow promoting Employees
                if ($user->hasRole('admin')) {
                    // Prevent self-deletion
                    if (auth()->id() !== $user->id) {
                        $deleteBtn = '<button class="btn-saas-action delete-admin" data-id="'.$user->id.'" data-name="'.$user->name.'" title="Terminate Identity">
                                        <i class="fas fa-trash-alt text-danger"></i> <span>Delete</span>
                                      </button>';
                    }
                } else {
                    $promoteBtn = '<button class="btn-saas-action promote-user" data-id="'.$user->id.'" data-name="'.$user->name.'" title="Promote to Admin">
                                    <i class="fas fa-arrow-up text-success"></i> <span>Promote</span>
                                   </button>';
                }
                
                return '
                    <div class="d-flex align-items-center justify-content-end gap-2 text-nowrap">
                        '.$promoteBtn.'
                        <button class="btn-saas-action change-role" data-id="'.$user->id.'" data-name="'.$user->name.'" data-role="'.$user->roles->first()?->name.'" title="Modify Access Layer">
                            <i class="fas fa-user-shield text-primary"></i> <span>Role</span>
                        </button>
                        <button class="btn-saas-action reset-password" data-id="'.$user->id.'" data-name="'.$user->name.'" title="Reset Authorization Token">
                            <i class="fas fa-key text-warning"></i> <span>Reset</span>
                        </button>
                        '.$deleteBtn.'
                    </div>
                ';
            })
            ->rawColumns(['roles_list', 'linked_personnel', 'action'])
            ->make(true);
    }

    /**
     * Terminate an admin identity.
     */
    public function destroy(User $user)
    {
        $this->authorize('manage-settings');

        if (auth()->id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => "Negative. You cannot terminate your own active session."
            ], 403);
        }

        try {
            DB::beginTransaction();
            
            // If they are strictly an admin (no employee record), delete user.
            // If they have employee record, maybe just remove the user? Or delete all?
            // User requested 'Delete' option. Usually means full user deletion.
            $user->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Identity terminated. Access protocols have been purged."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => "Termination failed: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset a user's password.
     */
    public function updatePassword(Request $request, User $user)
    {
        $this->authorize('manage-settings');

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'success' => true,
            'message' => "Authorization token for {$user->name} has been recalibrated."
        ]);
    }

    /**
     * Promote a user to Admin.
     */
    public function promote(User $user)
    {
        $this->authorize('manage-settings');

        DB::beginTransaction();
        try {
            $user->syncRoles(['admin']);
            
            // 🧹 Clean up Employee record for new Admins
            if ($user->employee) {
                $user->employee->delete();
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => "{$user->name} has been promoted to Admin protocols."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update user role.
     */
    public function updateRole(Request $request, User $user)
    {
        $this->authorize('manage-settings');

        $request->validate([
            'role' => 'required|exists:roles,name',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'exists:teams,id'
        ]);

        $user->syncRoles([$request->role]);
        
        // 🧹 If promoted to Admin, clean up Employee record
        if ($request->role === 'admin' && $user->employee) {
            $user->employee->delete();
        }

        // Expert Implementation: Atomic Sync of Team Clusters
        if ($request->has('team_ids')) {
            $user->managedTeams()->sync($request->team_ids);
        } else {
            $user->managedTeams()->detach();
        }

        return response()->json([
            'success' => true,
            'message' => "Access layer for {$user->name} updated to " . ucfirst($request->role)
        ]);
    }

    /**
     * Create a new Admin user or promote existing email.
     */
    public function storeAdmin(Request $request)
    {
        $this->authorize('manage-settings');

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'nullable|string|min:8',
        ]);

        $user = User::where('email', $request->email)->first();

        try {
            DB::beginTransaction();

            if ($user) {
                // User exists, promote them
                $user->syncRoles(['admin']);
                
                // 🧹 Clean up Employee record
                if ($user->employee) {
                    $user->employee->delete();
                }

                $message = "Existing identity found. {$user->name} has been promoted to Admin protocols.";
            } else {
                // New user
                $request->validate(['password' => 'required|string|min:8']);
                
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);

                $user->assignRole('admin');
                $message = "New Admin identity created successfully.";
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => "Authorization failed: " . $e->getMessage()
            ], 500);
        }
    }
}
