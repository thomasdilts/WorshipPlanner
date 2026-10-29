<?php

namespace Modules\Abilities\Services;

use App\Core\Modules\ChurchModuleManager;
use App\Enums\FieldUsage;
use App\Models\Church;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Support\UserAvatar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UserAbilities
{
    public function isEnabled(?Church $church): bool
    {
        if ($church === null) {
            return false;
        }

        return app(ChurchModuleManager::class)->isEnabled($church, 'abilities');
    }

    /**
     * Task templates that can appear as abilities (person optional or required).
     *
     * @return array<int, string> id => name
     */
    public function templateOptionsForChurch(int $churchId): array
    {
        return $this->personCapableTemplatesQuery($churchId)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @param  list<int|string>  $taskTemplateIds
     */
    public function sync(User $user, array $taskTemplateIds): void
    {
        if ($user->church_id === null) {
            $user->abilityTemplates()->sync([]);

            return;
        }

        $ids = $this->personCapableTemplatesQuery((int) $user->church_id)
            ->whereIn('id', $taskTemplateIds)
            ->pluck('id')
            ->all();

        $user->abilityTemplates()->sync($ids);
    }

    /**
     * Church users who may be assigned as the person for this template.
     * When abilities is off, returns all church users.
     * Always includes $keepUserId so an existing assignee stays selectable.
     *
     * @return array<int, string> id => HTML label
     */
    public function assignablePersonOptions(Church $church, ?int $taskTemplateId, ?int $keepUserId = null): array
    {
        $query = User::query()
            ->where('church_id', $church->id)
            ->orderBy('display_name')
            ->orderBy('name');

        if ($this->isEnabled($church)) {
            if ($taskTemplateId === null) {
                $users = $keepUserId
                    ? $query->whereKey($keepUserId)->get()
                    : collect();

                return $this->mapUserOptions($users);
            }

            $query->where(function (Builder $builder) use ($taskTemplateId, $keepUserId): void {
                $builder->whereHas(
                    'abilityTemplates',
                    fn (Builder $q) => $q->where('task_templates.id', $taskTemplateId)
                );

                if ($keepUserId !== null) {
                    $builder->orWhere('users.id', $keepUserId);
                }
            });
        }

        return $this->mapUserOptions($query->get());
    }

    public function userCanPerform(User $user, TaskTemplate $template): bool
    {
        if (! $this->isEnabled($user->church)) {
            return true;
        }

        if (! $template->usage('person')->isUsed()) {
            return true;
        }

        return $user->abilityTemplates()
            ->where('task_templates.id', $template->id)
            ->exists();
    }

    /**
     * @return Builder<TaskTemplate>
     */
    protected function personCapableTemplatesQuery(int $churchId): Builder
    {
        return TaskTemplate::query()
            ->where('church_id', $churchId)
            ->whereIn('person_usage', [
                FieldUsage::Optional->value,
                FieldUsage::Required->value,
            ]);
    }

    /**
     * @param  Collection<int, User>  $users
     * @return array<int, string>
     */
    protected function mapUserOptions(Collection $users): array
    {
        return $users
            ->mapWithKeys(fn (User $user): array => [
                $user->id => UserAvatar::html($user)->toHtml(),
            ])
            ->all();
    }
}
