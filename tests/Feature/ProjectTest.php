<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Project;
use App\Models\TimeEntry;

describe('Project relationships', function () {
    it('belongs to a client and the client has many projects', function () {
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);

        expect($project->client->is($client))->toBeTrue()
            ->and($client->projects->pluck('id')->all())->toBe([$project->id]);
    });

    it('has many time entries', function () {
        $project = Project::factory()->create();
        $entry = TimeEntry::factory()->forProject($project)->create();

        expect($project->timeEntries->pluck('id')->all())->toBe([$entry->id])
            ->and($entry->project->is($project))->toBeTrue();
    });

    it('allows time entries without a project', function () {
        $entry = TimeEntry::factory()->create(['project_id' => null]);

        expect($entry->project)->toBeNull();
    });

    it('nulls project on time entries and invoice lines when the project is deleted', function () {
        $project = Project::factory()->create();
        $entry = TimeEntry::factory()->forProject($project)->create();
        $invoice = Invoice::factory()->create(['client_id' => $project->client_id]);
        $line = InvoiceLine::factory()->hourly()->create([
            'invoice_id' => $invoice->id,
            'project_id' => $project->id,
        ]);

        $project->delete();

        expect($entry->fresh()->project_id)->toBeNull()
            ->and($line->fresh()->project_id)->toBeNull();
    });
});

describe('Project hours', function () {
    it('sums hours used across all entries', function () {
        $project = Project::factory()->create();
        TimeEntry::factory()->forProject($project)->create(['hours' => 3.5]);
        TimeEntry::factory()->forProject($project)->create(['hours' => 2]);
        TimeEntry::factory()->create(['client_id' => $project->client_id, 'hours' => 10]);

        expect($project->hoursUsed())->toBe(5.5);
    });

    it('returns zero hours used when there are no entries', function () {
        $project = Project::factory()->create();

        expect($project->hoursUsed())->toBe(0.0);
    });

    it('uses the preloaded hours_used sum when present', function () {
        $project = Project::factory()->create();
        TimeEntry::factory()->forProject($project)->create(['hours' => 4]);

        $loaded = Project::query()->withSum('timeEntries as hours_used', 'hours')->find($project->id);

        expect($loaded->hoursUsed())->toBe(4.0);
    });

    it('returns null hours remaining without a budget', function () {
        $project = Project::factory()->create(['budget_hours' => null]);

        expect($project->hoursRemaining())->toBeNull();
    });

    it('returns hours remaining against the budget, including negative values', function () {
        $project = Project::factory()->withBudget(10)->create();
        TimeEntry::factory()->forProject($project)->create(['hours' => 12.5]);

        expect($project->hoursRemaining())->toBe(-2.5);
    });
});

describe('Project options for a client', function () {
    it('lists active projects for the client ordered by name', function () {
        $client = Client::factory()->create();
        $beta = Project::factory()->create(['client_id' => $client->id, 'name' => 'Beta']);
        $alpha = Project::factory()->create(['client_id' => $client->id, 'name' => 'Alpha']);
        Project::factory()->inactive()->create(['client_id' => $client->id, 'name' => 'Old']);
        Project::factory()->create(['name' => 'Other client project']);

        expect(Project::optionsForClient($client->id))->toBe([
            $alpha->id => 'Alpha',
            $beta->id => 'Beta',
        ]);
    });

    it('includes a given inactive project so existing entries keep it', function () {
        $client = Client::factory()->create();
        $old = Project::factory()->inactive()->create(['client_id' => $client->id, 'name' => 'Old']);

        expect(Project::optionsForClient($client->id, $old->id))->toBe([$old->id => 'Old']);
    });

    it('does not include a given project from another client', function () {
        $client = Client::factory()->create();
        $foreign = Project::factory()->create();

        expect(Project::optionsForClient($client->id, $foreign->id))->toBe([]);
    });

    it('returns no options without a client', function () {
        Project::factory()->create();

        expect(Project::optionsForClient(null))->toBe([]);
    });
});

describe('Project client integrity', function () {
    it('rejects a time entry whose project belongs to another client', function () {
        $project = Project::factory()->create();
        $otherClient = Client::factory()->create();

        TimeEntry::factory()->create([
            'client_id' => $otherClient->id,
            'project_id' => $project->id,
        ]);
    })->throws(InvalidArgumentException::class);

    it('rejects an invoice line whose project belongs to another client', function () {
        $project = Project::factory()->create();
        $invoice = Invoice::factory()->create();

        InvoiceLine::factory()->hourly()->create([
            'invoice_id' => $invoice->id,
            'project_id' => $project->id,
        ]);
    })->throws(InvalidArgumentException::class);

    it('accepts an invoice line whose project matches the invoice client', function () {
        $project = Project::factory()->create();
        $invoice = Invoice::factory()->create(['client_id' => $project->client_id]);

        $line = InvoiceLine::factory()->hourly()->create([
            'invoice_id' => $invoice->id,
            'project_id' => $project->id,
        ]);

        expect($line->project->is($project))->toBeTrue();
    });
});
