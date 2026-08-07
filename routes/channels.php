<?php

use Illuminate\Support\Facades\Broadcast;


Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('employee.{id}.breaks', function ($user, $id) {
    $employee = \App\Models\Employee::find($id);
    return $employee && (int) $user->id === (int) $employee->user_id;
}, ['guards' => ['api', 'web']]);

Broadcast::channel('employee.{id}.attendance', function ($user, $id) {
    $employee = \App\Models\Employee::find($id);
    return $employee && (int) $user->id === (int) $employee->user_id;
}, ['guards' => ['api', 'web']]);
Broadcast::channel('employee.{id}.leaves', function ($user, $id) {
    $employee = \App\Models\Employee::find($id);
    return $employee && (int) $user->id === (int) $employee->user_id;
}, ['guards' => ['api', 'web']]);

Broadcast::channel('admin-notifications', function ($user) {
    return $user->hasAnyRole(['admin', 'hr', 'administrator']);
}, ['guards' => ['api', 'web']]);
