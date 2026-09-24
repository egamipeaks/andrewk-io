<?php

use App\Mail\InvoiceEmail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Project;

beforeEach(function () {
    $this->client = Client::factory()->create(['currency' => 'USD']);
    $this->invoice = Invoice::factory()->create([
        'client_id' => $this->client->id,
        'currency' => 'USD',
        'conversion_rate' => 1,
    ]);
});

describe('InvoiceLine hours formatting', function () {
    it('formats hours', function (float $hours, string $expected) {
        expect(InvoiceLine::formatHours($hours))->toBe($expected);
    })->with([
        'minutes' => [0.5, '30 min'],
        'one hour' => [1.0, '1 hr'],
        'whole hours' => [3.0, '3 hrs'],
        'fractional hours' => [2.5, '2.50 hrs'],
    ]);
});

describe('Invoice line grouping', function () {
    it('reports whether any line has a project', function () {
        InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id]);

        expect($this->invoice->fresh()->hasProjectLines())->toBeFalse();

        $project = Project::factory()->create(['client_id' => $this->client->id]);
        InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'project_id' => $project->id]);

        expect($this->invoice->fresh()->hasProjectLines())->toBeTrue();
    });

    it('groups lines by project name with Other last and subtotals each group', function () {
        $zeta = Project::factory()->create(['client_id' => $this->client->id, 'name' => 'Zeta']);
        $alpha = Project::factory()->create(['client_id' => $this->client->id, 'name' => 'Alpha']);

        InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'project_id' => $zeta->id, 'hourly_rate' => 100, 'hours' => 2]);
        InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'project_id' => null, 'hourly_rate' => 100, 'hours' => 1]);
        InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'project_id' => $alpha->id, 'hourly_rate' => 100, 'hours' => 3]);
        InvoiceLine::factory()->fixed()->create(['invoice_id' => $this->invoice->id, 'project_id' => $alpha->id, 'amount' => 50]);

        $groups = $this->invoice->fresh()->linesGroupedByProject();

        expect($groups->pluck('name')->all())->toBe(['Alpha', 'Zeta', 'Other'])
            ->and($groups[0]['lines'])->toHaveCount(2)
            ->and($groups[0]['formattedHours'])->toBe('3 hrs')
            ->and($groups[0]['formattedSubtotal'])->toBe($this->invoice->currency->format(350))
            ->and($groups[2]['formattedSubtotal'])->toBe($this->invoice->currency->format(100));
    });

    it('omits hours for a group with only fixed lines', function () {
        $project = Project::factory()->create(['client_id' => $this->client->id]);
        InvoiceLine::factory()->fixed()->create(['invoice_id' => $this->invoice->id, 'project_id' => $project->id, 'amount' => 500]);

        $group = $this->invoice->fresh()->linesGroupedByProject()->first();

        expect($group['formattedHours'])->toBeNull();
    });
});

describe('Invoice email rendering', function () {
    it('renders a flat list without headings when no line has a project', function () {
        InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'description' => 'Plain work']);

        $html = (new InvoiceEmail($this->invoice->fresh()))->render();

        expect($html)
            ->toContain('Plain work')
            ->not->toContain('Subtotal')
            ->not->toContain('Other')
            ->not->toContain('<pre>');
    });

    it('renders project headings, subtotals and Other in order', function () {
        $project = Project::factory()->withBudget(37.25)->create(['client_id' => $this->client->id, 'name' => 'Website Redesign']);
        InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'project_id' => $project->id, 'description' => 'Header layout', 'hourly_rate' => 100, 'hours' => 2]);
        InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'project_id' => null, 'description' => 'Hosting support', 'hourly_rate' => 100, 'hours' => 1]);

        $html = (new InvoiceEmail($this->invoice->fresh()))->render();

        expect($html)
            ->toMatch('/Website Redesign.*Header layout.*Subtotal.*Other.*Hosting support/s')
            ->not->toContain('<pre>')
            ->not->toContain('37.25')
            ->not->toContain('Budget');
    });
});
