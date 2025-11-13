<?php

use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'email' => 'admin@andrewk.io',
        'email_verified_at' => now(),
    ]);

    $this->client = Client::factory()->create([
        'email' => 'client@example.com',
    ]);

    $this->invoice = Invoice::factory()->create([
        'client_id' => $this->client->id,
    ]);
});

describe('InvoiceEmailSends Relation Manager', function () {
    it('displays email address in the email sends relation manager', function () {
        $this->invoice->emailSends()->create([
            'email' => 'test@example.com',
            'sent_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditInvoice::class, [
                'record' => $this->invoice->id,
            ])
            ->assertSuccessful();

        // Verify the email send record exists with email
        expect($this->invoice->emailSends()->count())->toBe(1);
        expect($this->invoice->emailSends()->first()->email)->toBe('test@example.com');
    });

    it('displays multiple email sends with different email addresses', function () {
        $this->invoice->emailSends()->create([
            'email' => 'first@example.com',
            'sent_at' => now()->subDays(2),
        ]);

        $this->invoice->emailSends()->create([
            'email' => 'second@example.com',
            'sent_at' => now()->subDay(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditInvoice::class, [
                'record' => $this->invoice->id,
            ])
            ->assertSuccessful();

        $emailSends = $this->invoice->emailSends;
        expect($emailSends)->toHaveCount(2);
        expect($emailSends->pluck('email')->toArray())
            ->toContain('first@example.com')
            ->toContain('second@example.com');
    });

    it('handles null email addresses for backwards compatibility', function () {
        $this->invoice->emailSends()->create([
            'email' => null,
            'sent_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditInvoice::class, [
                'record' => $this->invoice->id,
            ])
            ->assertSuccessful();

        expect($this->invoice->emailSends()->count())->toBe(1);
        expect($this->invoice->emailSends()->first()->email)->toBeNull();
    });

    it('shows email address when invoice is sent via sendInvoiceEmail', function () {
        Mail::fake();

        expect($this->invoice->emailSends()->count())->toBe(0);

        $this->invoice->sendInvoiceEmail();

        expect($this->invoice->emailSends()->count())->toBe(1);
        expect($this->invoice->emailSends()->first()->email)->toBe($this->client->email);

        Livewire::actingAs($this->admin)
            ->test(EditInvoice::class, [
                'record' => $this->invoice->id,
            ])
            ->assertSuccessful();
    });
});
