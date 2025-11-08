<?php

use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    // Create an admin user who can access Filament and preview emails
    $this->admin = User::factory()->create([
        'email' => 'admin@andrewk.io',
        'email_verified_at' => now(),
    ]);

    // Create a non-admin user who cannot access Filament
    $this->regularUser = User::factory()->create([
        'email' => 'user@example.com',
        'email_verified_at' => now(),
    ]);

    // Create an unverified admin user
    $this->unverifiedAdmin = User::factory()->create([
        'email' => 'unverified@andrewk.io',
        'email_verified_at' => null,
    ]);

    // Create a client and invoice for testing
    $this->client = Client::factory()->create([
        'name' => 'Test Client',
        'email' => 'client@example.com',
    ]);

    $this->invoice = Invoice::factory()->create([
        'client_id' => $this->client->id,
        'due_date' => now()->addDays(30),
        'note' => 'Test invoice note',
    ]);

    // Add some invoice lines
    InvoiceLine::factory()->count(3)->create([
        'invoice_id' => $this->invoice->id,
    ]);
});

describe('Filament Preview Email Button', function () {
    it('shows Preview Email button on EditInvoice page', function () {
        Livewire::actingAs($this->admin)
            ->test(EditInvoice::class, [
                'record' => $this->invoice->id,
            ])
            ->assertActionExists('Preview Email');
    });

    it('Preview Email button has correct styling and icon', function () {
        Livewire::actingAs($this->admin)
            ->test(EditInvoice::class, [
                'record' => $this->invoice->id,
            ])
            ->assertActionExists('Preview Email')
            ->assertActionHasColor('Preview Email', 'warning')
            ->assertActionHasIcon('Preview Email', 'heroicon-o-eye');
    });

    it('Preview Email button appears before Send button', function () {
        Livewire::actingAs($this->admin)
            ->test(EditInvoice::class, [
                'record' => $this->invoice->id,
            ])
            ->assertActionExists('Preview Email')
            ->assertActionExists('Send')
            ->assertActionExists('Send Test Email');
    });
});

describe('Email Preview Route Authorization', function () {
    it('allows admin users to preview invoice emails', function () {
        actingAs($this->admin)
            ->get(route('invoice.email.preview', $this->invoice))
            ->assertOk();
    });

    it('denies access to regular users', function () {
        actingAs($this->regularUser)
            ->get(route('invoice.email.preview', $this->invoice))
            ->assertForbidden()
            ->assertSee('Unauthorized - You do not have permission to preview invoice emails');
    });

    it('denies access to unverified admin users', function () {
        actingAs($this->unverifiedAdmin)
            ->get(route('invoice.email.preview', $this->invoice))
            ->assertForbidden()
            ->assertSee('Unauthorized - You do not have permission to preview invoice emails');
    });

    it('denies access to unauthenticated users', function () {
        get(route('invoice.email.preview', $this->invoice))
            ->assertNotFound();
    });
});

describe('Email Preview Content', function () {
    it('renders the email preview with invoice details', function () {
        $response = actingAs($this->admin)
            ->get(route('invoice.email.preview', $this->invoice));

        $response->assertOk()
            ->assertSee('You have received an invoice')
            ->assertSeeText('Billed To: '.$this->client->name)
            ->assertSee($this->invoice->due_date->format('F j, Y'))
            ->assertSee($this->invoice->formattedTotal());
    });

    it('displays invoice note when present', function () {
        $response = actingAs($this->admin)
            ->get(route('invoice.email.preview', $this->invoice));

        $response->assertOk()
            ->assertSee('Note:')
            ->assertSee($this->invoice->note);
    });

    it('displays invoice lines in the email preview', function () {
        $response = actingAs($this->admin)
            ->get(route('invoice.email.preview', $this->invoice));

        $response->assertOk()
            ->assertSee('Description')
            ->assertSee('Rate')
            ->assertSee('Total');

        // Check that invoice lines are displayed
        foreach ($this->invoice->invoiceLines as $line) {
            $response->assertSee($line->description);
        }
    });

    it('does not display note section when invoice has no note', function () {
        $invoiceWithoutNote = Invoice::factory()->create([
            'client_id' => $this->client->id,
            'due_date' => now()->addDays(30),
            'note' => null,
        ]);

        $response = actingAs($this->admin)
            ->get(route('invoice.email.preview', $invoiceWithoutNote));

        $response->assertOk()
            ->assertDontSee('Note:');
    });
});

describe('User Permission Methods', function () {
    it('correctly identifies users who can preview invoice emails', function () {
        expect($this->admin->canPreviewInvoiceEmails())->toBeTrue();
        expect($this->regularUser->canPreviewInvoiceEmails())->toBeFalse();
        expect($this->unverifiedAdmin->canPreviewInvoiceEmails())->toBeFalse();
    });

    it('correctly identifies users who can access Filament panel', function () {
        // We need to mock the Panel instance for this test
        $panel = Mockery::mock(\Filament\Panel::class);

        expect($this->admin->canAccessPanel($panel))->toBeTrue();
        expect($this->regularUser->canAccessPanel($panel))->toBeFalse();
        expect($this->unverifiedAdmin->canAccessPanel($panel))->toBeFalse();
    });
});

describe('Invoice Model Not Found', function () {
    it('returns 404 when invoice does not exist', function () {
        actingAs($this->admin)
            ->get(route('invoice.email.preview', 999999))
            ->assertNotFound();
    });
});
