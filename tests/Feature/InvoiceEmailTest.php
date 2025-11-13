<?php

use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Mail\InvoiceEmail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceEmailSend;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    // Create an admin user
    $this->admin = User::factory()->create([
        'email' => 'admin@andrewk.io',
        'email_verified_at' => now(),
    ]);

    // Create a client and invoice for testing
    $this->client = Client::factory()->create([
        'email' => 'client@example.com',
    ]);

    $this->invoice = Invoice::factory()->create([
        'client_id' => $this->client->id,
        'due_date' => now()->addDays(30),
    ]);
});

describe('Invoice Model Email Methods', function () {
    it('sends invoice email to client', function () {
        Mail::fake();

        $this->invoice->sendInvoiceEmail();

        Mail::assertSent(InvoiceEmail::class, function ($mail) {
            return $mail->hasTo($this->client->email) &&
                   $mail->invoice->id === $this->invoice->id;
        });

        assertDatabaseHas('invoice_email_sends', [
            'invoice_id' => $this->invoice->id,
            'email' => $this->client->email,
        ]);
    });

    it('sends test email to admin email', function () {
        Mail::fake();

        // Set the admin email in config
        config(['mail.admin_email' => 'test-admin@example.com']);

        $this->invoice->sendTestEmail();

        Mail::assertSent(InvoiceEmail::class, function ($mail) {
            return $mail->hasTo('test-admin@example.com') &&
                   $mail->invoice->id === $this->invoice->id;
        });

        // Test emails should NOT create email send records
        assertDatabaseCount('invoice_email_sends', 0);
    });

    it('creates email send record when sending invoice email', function () {
        Mail::fake();

        expect(InvoiceEmailSend::count())->toBe(0);

        $this->invoice->sendInvoiceEmail();

        assertDatabaseCount('invoice_email_sends', 1);
        assertDatabaseHas('invoice_email_sends', [
            'invoice_id' => $this->invoice->id,
        ]);
    });

    it('does not create email send record when sending test email', function () {
        Mail::fake();
        config(['mail.admin_email' => 'test-admin@example.com']);

        expect(InvoiceEmailSend::count())->toBe(0);

        $this->invoice->sendTestEmail();

        // Test emails should NOT create records (they're just for preview)
        assertDatabaseCount('invoice_email_sends', 0);
    });
});

describe('Filament EditInvoice Page Actions', function () {
    it('can send invoice email to client', function () {
        Mail::fake();

        Livewire::actingAs($this->admin)
            ->test(EditInvoice::class, [
                'record' => $this->invoice->id,
            ])
            ->assertActionExists('Send')
            ->call('sendInvoiceEmail');

        Mail::assertSent(InvoiceEmail::class, function ($mail) {
            return $mail->hasTo($this->client->email);
        });

        assertDatabaseHas('invoice_email_sends', [
            'invoice_id' => $this->invoice->id,
        ]);
    });

    it('can send test email to admin', function () {
        Mail::fake();
        config(['mail.admin_email' => 'test-admin@example.com']);

        Livewire::actingAs($this->admin)
            ->test(EditInvoice::class, [
                'record' => $this->invoice->id,
            ])
            ->assertActionExists('Send Test Email')
            ->call('sendTestEmail');

        Mail::assertSent(InvoiceEmail::class, function ($mail) {
            return $mail->hasTo('test-admin@example.com');
        });

        // Test emails should NOT create email send records
        assertDatabaseCount('invoice_email_sends', 0);
    });

    it('shows correct admin email when sending test email', function () {
        Mail::fake();
        config(['mail.admin_email' => 'specific-admin@example.com']);

        Livewire::actingAs($this->admin)
            ->test(EditInvoice::class, [
                'record' => $this->invoice->id,
            ])
            ->call('sendTestEmail');

        // Verify email was sent to correct address
        Mail::assertSent(InvoiceEmail::class, function ($mail) {
            return $mail->hasTo('specific-admin@example.com');
        });
    });

    it('test email action has correct styling', function () {
        Livewire::actingAs($this->admin)
            ->test(EditInvoice::class, [
                'record' => $this->invoice->id,
            ])
            ->assertActionExists('Send Test Email')
            ->assertActionHasColor('Send Test Email', 'info')
            ->assertActionHasIcon('Send Test Email', 'heroicon-o-beaker');
    });
});

describe('Email Configuration', function () {
    it('uses default admin email when env variable not set', function () {
        // Set to default value
        config(['mail.admin_email' => 'admin@example.com']);

        expect(config('mail.admin_email'))->toBe('admin@example.com');
    });

    it('uses custom admin email when env variable is set', function () {
        config(['mail.admin_email' => 'custom@example.com']);

        expect(config('mail.admin_email'))->toBe('custom@example.com');
    });

    it('uses client email_from when set', function () {
        Mail::fake();

        $client = Client::factory()->create([
            'email' => 'custom-client@example.com',
            'email_from' => 'custom@andrewk.io',
        ]);

        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'due_date' => now()->addDays(30),
        ]);

        $invoice->sendInvoiceEmail();

        Mail::assertSent(InvoiceEmail::class, function ($mail) {
            return $mail->hasFrom('custom@andrewk.io');
        });
    });

    it('uses default mail.from.address when client email_from is not set', function () {
        Mail::fake();

        config(['mail.from.address' => 'default@andrewk.io']);

        $client = Client::factory()->create([
            'email' => 'another-client@example.com',
            'email_from' => null,
        ]);

        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'due_date' => now()->addDays(30),
        ]);

        $invoice->sendInvoiceEmail();

        Mail::assertSent(InvoiceEmail::class, function ($mail) {
            return $mail->hasFrom('default@andrewk.io');
        });
    });
});

describe('Integration Tests', function () {
    it('can send multiple test emails for the same invoice', function () {
        Mail::fake();
        config(['mail.admin_email' => 'test-admin@example.com']);

        $this->invoice->sendTestEmail();
        $this->invoice->sendTestEmail();
        $this->invoice->sendTestEmail();

        Mail::assertSentCount(3);

        // Test emails should NOT create records
        assertDatabaseCount('invoice_email_sends', 0);
    });

    it('can send both regular and test emails for the same invoice', function () {
        Mail::fake();
        config(['mail.admin_email' => 'test-admin@example.com']);

        $this->invoice->sendInvoiceEmail();
        $this->invoice->sendTestEmail();

        Mail::assertSentCount(2);

        Mail::assertSent(InvoiceEmail::class, function ($mail) {
            return $mail->hasTo($this->client->email);
        });

        Mail::assertSent(InvoiceEmail::class, function ($mail) {
            return $mail->hasTo('test-admin@example.com');
        });

        // Only the real email should create a record, not the test email
        assertDatabaseCount('invoice_email_sends', 1);
        assertDatabaseHas('invoice_email_sends', [
            'invoice_id' => $this->invoice->id,
            'email' => $this->client->email,
        ]);
    });
});
