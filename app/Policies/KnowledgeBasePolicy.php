<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\KnowledgeBase;
use App\Models\User;

/**
 * Authorization policy for KnowledgeBase article management.
 *
 * Admins have full access. Managers and agents can view articles
 * within their own department. Only admins and managers can create,
 * update, or delete articles (scoped to their department for managers).
 */
final class KnowledgeBasePolicy
{
    /**
     * Determine whether the user can view any knowledge base articles.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager', 'agent'], true);
    }

    /**
     * Determine whether the user can view the given knowledge base article.
     */
    public function view(User $user, KnowledgeBase $article): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Managers and agents can only view articles in their own department
        return $user->department_id === $article->department_id;
    }

    /**
     * Determine whether the user can create knowledge base articles.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager'], true);
    }

    /**
     * Determine whether the user can update the given knowledge base article.
     */
    public function update(User $user, KnowledgeBase $article): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Managers can only update articles in their own department
        return $user->isManager()
            && $user->department_id === $article->department_id;
    }

    /**
     * Determine whether the user can delete the given knowledge base article.
     */
    public function delete(User $user, KnowledgeBase $article): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Managers can only delete articles in their own department
        return $user->isManager()
            && $user->department_id === $article->department_id;
    }
}
