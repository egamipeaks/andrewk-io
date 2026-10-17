<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('previewHoursUpdate')
                ->label('Preview Hours Update')
                ->url(fn (): string => route('client.hours-update.preview', $this->record))
                ->openUrlInNewTab()
                ->color('warning')
                ->icon('heroicon-o-eye'),
            Actions\Action::make('sendHoursUpdate')
                ->label('Send Hours Update')
                ->icon('heroicon-o-clock')
                ->color('info')
                ->requiresConfirmation()
                ->modalDescription(fn (): string => "Email {$this->record->email} the un-invoiced hours for this month.")
                ->action(fn () => $this->sendHoursUpdate()),
            Actions\DeleteAction::make(),
        ];
    }

    public function sendHoursUpdate(): void
    {
        if (! $this->record->sendHoursUpdate(today('America/Chicago'))) {
            Notification::make()
                ->title('Nothing to send')
                ->body('There are no un-invoiced hours this month.')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title('Hours Update Sent')
            ->success()
            ->send();
    }
}
