<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the broadcast event channels for your
| application. The channel authorization callbacks are used to check
| if the authenticated user can listen to the channel.
|
*/

// Conversation channel - customer + assigned agent + AI
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    // Customer can access their own conversations
    if ($user->id === $conversation->user_id) {
        return true;
    }

    // Assigned agent can access
    if ($user->id === $conversation->agent_id) {
        return true;
    }

    // Admin/Manager can access any conversation in their department
    if (in_array($user->role, ['admin', 'manager'])) {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->department_id === $conversation->department_id;
    }

    return false;
});

// Agent presence channel - all agents in a department
Broadcast::channel('agents.{departmentId}', function ($user, $departmentId) {
    // Only agents, managers, and admins can join
    if (! in_array($user->role, ['agent', 'manager', 'admin'])) {
        return false;
    }

    // Admin can access all departments
    if ($user->role === 'admin') {
        return true;
    }

    // Manager and agent can only access their own department
    return $user->department_id == $departmentId;
});

// Dashboard channel - managers and admins only
Broadcast::channel('dashboard', function ($user) {
    return in_array($user->role, ['admin', 'manager']);
});
