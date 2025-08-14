<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Send')->action('sendInvoiceEmail'),
            Actions\DeleteAction::make(),
        ];
    }

    public function sendInvoiceEmail()
    {
        $this->record->sendInvoiceEmail();

        Notification::make()
            ->title('Email Sent')
            ->success()
            ->send();
    }
}
