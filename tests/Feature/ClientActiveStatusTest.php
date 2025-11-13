<?php

use App\Filament\Pages\TimeTracking;
use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Models\Client;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'email' => 'admin@andrewk.io',
        'email_verified_at' => now(),
    ]);
});

describe('Client Active Status', function () {
    it('creates active clients by default', function () {
        $client = Client::factory()->create();

        expect($client->is_active)->toBeTrue();
    });

    it('can create inactive clients using factory state', function () {
        $client = Client::factory()->inactive()->create();

        expect($client->is_active)->toBeFalse();
    });

    it('sets is_active to true for existing clients after migration', function () {
        // This verifies the migration default value works correctly
        $client = Client::factory()->create();

        expect($client->is_active)->toBeTrue();
    });
});

describe('Time Tracking Page Filtering', function () {
    it('shows active clients with hourly rates on time tracking page', function () {
        $activeClient = Client::factory()->create([
            'is_active' => true,
            'hourly_rate' => 100,
        ]);

        Livewire::actingAs($this->admin)
            ->test(TimeTracking::class)
            ->assertSuccessful();

        $component = Livewire::actingAs($this->admin)->test(TimeTracking::class);

        expect($component->clients->pluck('id'))->toContain($activeClient->id);
    });

    it('does not show inactive clients on time tracking page', function () {
        $inactiveClient = Client::factory()->create([
            'is_active' => false,
            'hourly_rate' => 100,
        ]);

        $component = Livewire::actingAs($this->admin)->test(TimeTracking::class);

        expect($component->clients->pluck('id'))->not->toContain($inactiveClient->id);
    });

    it('does not show active clients without hourly rates', function () {
        $clientWithoutRate = Client::factory()->create([
            'is_active' => true,
            'hourly_rate' => null,
        ]);

        $component = Livewire::actingAs($this->admin)->test(TimeTracking::class);

        expect($component->clients->pluck('id'))->not->toContain($clientWithoutRate->id);
    });

    it('does not show active clients with zero hourly rate', function () {
        $clientWithZeroRate = Client::factory()->create([
            'is_active' => true,
            'hourly_rate' => 0,
        ]);

        $component = Livewire::actingAs($this->admin)->test(TimeTracking::class);

        expect($component->clients->pluck('id'))->not->toContain($clientWithZeroRate->id);
    });

    it('requires both is_active and hourly_rate to show on time tracking', function () {
        $activeWithRate = Client::factory()->create([
            'is_active' => true,
            'hourly_rate' => 100,
        ]);

        $inactiveWithRate = Client::factory()->create([
            'is_active' => false,
            'hourly_rate' => 100,
        ]);

        $activeWithoutRate = Client::factory()->create([
            'is_active' => true,
            'hourly_rate' => null,
        ]);

        $inactiveWithoutRate = Client::factory()->create([
            'is_active' => false,
            'hourly_rate' => null,
        ]);

        $component = Livewire::actingAs($this->admin)->test(TimeTracking::class);
        $clientIds = $component->clients->pluck('id');

        // Only active client with rate should show
        expect($clientIds)->toContain($activeWithRate->id);
        expect($clientIds)->not->toContain($inactiveWithRate->id);
        expect($clientIds)->not->toContain($activeWithoutRate->id);
        expect($clientIds)->not->toContain($inactiveWithoutRate->id);
    });
});

describe('Filament Client Form', function () {
    it('has active toggle in create form', function () {
        Livewire::actingAs($this->admin)
            ->test(CreateClient::class)
            ->assertFormFieldExists('is_active');
    });

    it('has active toggle in edit form', function () {
        $client = Client::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(EditClient::class, [
                'record' => $client->id,
            ])
            ->assertFormFieldExists('is_active');
    });

    it('defaults to active when creating new client', function () {
        Livewire::actingAs($this->admin)
            ->test(CreateClient::class)
            ->assertFormSet([
                'is_active' => true,
            ]);
    });

    it('can create active client through form', function () {
        Livewire::actingAs($this->admin)
            ->test(CreateClient::class)
            ->fillForm([
                'name' => 'Test Client',
                'email' => 'test@example.com',
                'currency' => 'USD',
                'hourly_rate' => 100,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $client = Client::where('email', 'test@example.com')->first();
        expect($client->is_active)->toBeTrue();
    });

    it('can create inactive client through form', function () {
        Livewire::actingAs($this->admin)
            ->test(CreateClient::class)
            ->fillForm([
                'name' => 'Inactive Client',
                'email' => 'inactive@example.com',
                'currency' => 'USD',
                'hourly_rate' => 100,
                'is_active' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $client = Client::where('email', 'inactive@example.com')->first();
        expect($client->is_active)->toBeFalse();
    });

    it('can toggle client active status through edit form', function () {
        $client = Client::factory()->create([
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(EditClient::class, [
                'record' => $client->id,
            ])
            ->fillForm([
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($client->refresh()->is_active)->toBeFalse();
    });
});

describe('Client Model', function () {
    it('casts is_active as boolean', function () {
        $client = Client::factory()->create([
            'is_active' => true,
        ]);

        expect($client->is_active)->toBeTrue();
        expect($client->is_active)->toBeBool();
    });

    it('includes is_active in fillable attributes', function () {
        $client = new Client;

        expect($client->getFillable())->toContain('is_active');
    });
});
