<?php

namespace App\Policies;

use App\Core\Permissions\EnsuresChurchPermissionTeam;
use App\Models\TaskTemplate;
use App\Models\User;

class TaskTemplatePolicy
{
    use EnsuresChurchPermissionTeam;

    public function viewAny(User $user): bool
    {
        return $this->withChurchPermissions(
            $user,
            fn () => $user->can('task_templates.view') || $user->can('task_templates.manage')
        );
    }

    public function view(User $user, TaskTemplate $template): bool
    {
        return $this->sameChurch($user, $template)
            && $this->withChurchPermissions(
                $user,
                fn () => $user->can('task_templates.view') || $user->can('task_templates.manage')
            );
    }

    public function create(User $user): bool
    {
        return $this->withChurchPermissions(
            $user,
            fn () => $user->can('task_templates.manage')
        );
    }

    public function update(User $user, TaskTemplate $template): bool
    {
        return $this->sameChurch($user, $template)
            && $this->withChurchPermissions(
                $user,
                fn () => $user->can('task_templates.manage')
            );
    }

    public function delete(User $user, TaskTemplate $template): bool
    {
        return $this->sameChurch($user, $template)
            && $this->withChurchPermissions(
                $user,
                fn () => $user->can('task_templates.manage')
            );
    }

    protected function sameChurch(User $user, TaskTemplate $template): bool
    {
        return $user->church_id !== null
            && (int) $user->church_id === (int) $template->church_id;
    }
}
