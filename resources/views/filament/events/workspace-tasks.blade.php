@php
    $manager = $this->activeRelationManager === '1'
        ? \App\Filament\Resources\Events\RelationManagers\PeopleTasksRelationManager::class
        : \App\Filament\Resources\Events\RelationManagers\OrderTasksRelationManager::class;
@endphp

<div>
    @livewire($manager, ['ownerRecord' => $this->getRecord(), 'pageClass' => get_class($this)], key($manager.'-'.$this->getRecord()->id))
</div>
