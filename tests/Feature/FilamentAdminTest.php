<?php

use App\Filament\Resources\ClientResource\Pages\CreateClient;
use App\Filament\Resources\ClientResource\Pages\EditClient;
use App\Filament\Resources\InvoiceResource\Pages\CreateInvoice;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Models\User;
use App\Models\Client;
use App\Models\Invoice;
use Filament\Actions\DeleteAction;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Livewire\Livewire;
use App\Filament\Resources\ClientResource;
use App\Filament\Resources\InvoiceResource;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    // Create an admin user with the correct email domain and verified email
    $this->admin = User::factory()->create([
        'email' => 'admin@andrewk.io',
        'email_verified_at' => now(),
    ]);
});

it('can log into filament admin panel', function () {
    // Test that the login page is accessible
    $response = get('/admin/login');
    $response->assertStatus(200);

    // Test login via Livewire component - using the Auth Page
    Livewire::test(Login::class)
        ->fillForm([
            'email' => $this->admin->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertRedirect('/admin');
});

it('can access admin dashboard when authenticated as admin', function () {
    $response = actingAs($this->admin)->get('/admin');

    $response->assertStatus(200);
    $response->assertSee('Dashboard');
});

it('can access clients resource list page as admin', function () {
    $response = actingAs($this->admin)->get('/admin/clients');

    $response->assertStatus(200);
    $response->assertSee('Clients');
});

it('can view clients in the table', function () {
    // Create some test clients
    $clients = Client::factory()->count(3)->create();

    $response = actingAs($this->admin)->get('/admin/clients');

    $response->assertStatus(200);
    foreach ($clients as $client) {
        $response->assertSee($client->name);
        $response->assertSee($client->email);
    }
});

it('can access create client page', function () {
    $response = actingAs($this->admin)->get('/admin/clients/create');

    $response->assertStatus(200);
    $response->assertSee('Create Client');
});

it('can create a new client through Filament', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateClient::class)
        ->fillForm([
            'name' => 'Test Client',
            'email' => 'test@client.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('clients', [
        'name' => 'Test Client',
        'email' => 'test@client.com',
    ]);
});

it('can edit an existing client', function () {
    $client = Client::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@email.com',
    ]);

    Livewire::actingAs($this->admin)
        ->test(EditClient::class, [
            'record' => $client->id,
        ])
        ->fillForm([
            'name' => 'Updated Name',
            'email' => 'updated@email.com',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas('clients', [
        'id' => $client->id,
        'name' => 'Updated Name',
        'email' => 'updated@email.com',
    ]);
});

it('can access invoices resource list page as admin', function () {
    $response = actingAs($this->admin)->get('/admin/invoices');

    $response->assertStatus(200);
    $response->assertSee('Invoices');
});

it('can view invoices in the table', function () {
    // Create a client and invoices
    $client = Client::factory()->create();
    $invoices = Invoice::factory()->count(2)->create([
        'client_id' => $client->id,
    ]);

    $response = actingAs($this->admin)->get('/admin/invoices');

    $response->assertStatus(200);
    $response->assertSee($client->name);
    foreach ($invoices as $invoice) {
        $response->assertSee($invoice->id);
    }
});

it('can create a new invoice through Filament', function () {
    $client = Client::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(CreateInvoice::class)
        ->fillForm([
            'client_id' => $client->id,
            'paid' => false,
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'note' => 'Test invoice note',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('invoices', [
        'client_id' => $client->id,
        'paid' => false,
        'note' => 'Test invoice note',
    ]);
});

it('can edit an existing invoice', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'paid' => false,
        'note' => 'Original note',
    ]);

    Livewire::actingAs($this->admin)
        ->test(EditInvoice::class, [
            'record' => $invoice->id,
        ])
        ->fillForm([
            'paid' => true,
            'note' => 'Updated note',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    assertDatabaseHas('invoices', [
        'id' => $invoice->id,
        'paid' => true,
        'note' => 'Updated note',
    ]);
});

it('validates required fields when creating a client', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateClient::class)
        ->fillForm([
            'name' => '',
            'email' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['name', 'email']);
});

it('validates email format when creating a client', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateClient::class)
        ->fillForm([
            'name' => 'Test Client',
            'email' => 'invalid-email',
        ])
        ->call('create')
        ->assertHasFormErrors(['email']);
});

it('can delete a client', function () {
    $client = Client::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(EditClient::class, [
            'record' => $client->id,
        ])
        ->callAction(DeleteAction::class);

    $this->assertDatabaseMissing('clients', [
        'id' => $client->id,
    ]);
});

it('can delete an invoice', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(EditInvoice::class, [
            'record' => $invoice->id,
        ])
        ->callAction(DeleteAction::class);

    $this->assertDatabaseMissing('invoices', [
        'id' => $invoice->id,
    ]);
});
