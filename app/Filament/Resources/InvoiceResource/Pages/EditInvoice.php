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
            Actions\Action::make('Send Test Email')
                ->action('sendTestEmail')
                ->color('info')
                ->icon('heroicon-o-beaker'),
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

    public function sendTestEmail()
    {
        $this->record->sendTestEmail();
        $adminEmail = config('mail.admin_email');

        Notification::make()
            ->title('Test Email Sent')
            ->body('Test email sent to: '.$adminEmail)
            ->success()
            ->send();
    }
}
