<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Project;
use Filament\Forms;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('client_id')
                ->label('Client')
                ->relationship(
                    'client',
                    'name',
                    modifyQueryUsing: fn (Builder $query, ?Project $record) => $query
                        ->where('is_active', true)
                        ->when($record, fn (Builder $query) => $query->orWhere('id', $record->client_id)),
                )
                ->searchable()
                ->preload()
                ->required()
                ->disabledOn('edit')
                ->helperText('A project can\'t move to another client once created.'),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('budget_hours')
                ->label('Budget')
                ->numeric()
                ->minValue(0)
                ->step(0.5)
                ->suffix('hrs')
                ->helperText('Leave blank for no budget.'),
            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->inline(false)
                ->helperText('Inactive projects can\'t be picked for new time entries.'),
        ]);
    }
}
