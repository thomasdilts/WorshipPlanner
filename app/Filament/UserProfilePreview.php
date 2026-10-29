<?php

namespace App\Filament;

use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class UserProfilePreview extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    #[On('show-user-profile')]
    public function showProfile(int $userId): void
    {
        $this->mountAction('viewUserProfile', ['userId' => $userId]);
    }

    public function viewUserProfileAction(): Action
    {
        return Action::make('viewUserProfile')
            ->record(fn (array $arguments): User => $this->resolvePerson($arguments))
            ->modalHeading(fn (User $record): string => $record->displayName())
            ->modalWidth(Width::TwoExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('Close'))
            ->schema(fn (Schema $schema): Schema => UserInfolist::configure($schema));
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    protected function resolvePerson(array $arguments): User
    {
        $person = UserResource::getEloquentQuery()
            ->with(['church', 'roles', 'abilityTemplates'])
            ->findOrFail((int) ($arguments['userId'] ?? 0));

        Gate::authorize('view', $person);

        return $person;
    }

    public function render(): View
    {
        return view('filament.users.profile-preview');
    }
}
