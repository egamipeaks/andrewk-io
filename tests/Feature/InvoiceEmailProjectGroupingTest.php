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

    it('formats hours as a decimal', function (float $hours, string $expected) {
        expect(InvoiceLine::formatHoursAsDecimal($hours))->toBe($expected);
    })->with([
        'half hour' => [0.5, '0.5'],
        'whole hours' => [3.0, '3.0'],
        'quarter hour' => [0.25, '0.25'],
        'large' => [1234.5, '1,234.5'],
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
            ->and($groups[0]['formattedHours'])->toBe('3.0')
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
            ->toContain('Total hours')
            ->not->toContain('Other')
            ->not->toContain('<pre>');
    });

    it('renders project headings, subtotals and Other in order', function () {
        $project = Project::factory()->withBudget(37.25)->create(['client_id' => $this->client->id, 'name' => 'Website Redesign']);
        InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'project_id' => $project->id, 'description' => 'Header layout', 'hourly_rate' => 100, 'hours' => 2]);
        InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'project_id' => null, 'description' => 'Hosting support', 'hourly_rate' => 100, 'hours' => 1]);

        $html = (new InvoiceEmail($this->invoice->fresh()))->render();

        expect($html)
            ->toMatch('/Website Redesign.*2\.0.*Header layout.*Other.*Hosting support.*Website Redesign · 2\.0 hrs.*Other · 1\.0 hrs.*Amount due/s')
            ->not->toContain('<pre>')
            ->not->toContain('37.25')
            ->not->toContain('Budget');
    });

    it('shows the rate once in the header when every hourly line shares it', function () {
        InvoiceLine::factory()->hourly()->count(2)->create(['invoice_id' => $this->invoice->id, 'hourly_rate' => 125, 'hours' => 2]);

        $html = (new InvoiceEmail($this->invoice->fresh()))->render();

        expect($html)
            ->toContain('Hourly at $125 / hr')
            ->not->toContain('/hr</span>');
    });

    it('shows the rate on each row when hourly rates differ', function () {
        InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'hourly_rate' => 125, 'hours' => 2]);
        InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'hourly_rate' => 150, 'hours' => 1]);

        $html = (new InvoiceEmail($this->invoice->fresh()))->render();

        expect($html)
            ->not->toContain('Hourly at')
            ->toContain('@ $125/hr')
            ->toContain('@ $150/hr');
    });

    it('labels fixed lines and omits total hours for a fixed-only invoice', function () {
        InvoiceLine::factory()->fixed()->create(['invoice_id' => $this->invoice->id, 'amount' => 500]);

        $html = (new InvoiceEmail($this->invoice->fresh()))->render();

        expect($html)
            ->toContain('Fixed')
            ->not->toContain('Hourly at')
            ->not->toContain('Total hours');
    });

    it('shows the billing period from line dates', function () {
        InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'date' => '2026-09-23']);
        InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'date' => '2026-10-30']);

        $html = (new InvoiceEmail($this->invoice->fresh()))->render();

        expect($html)
            ->toContain('Period')
            ->toContain('Sep 23 – Oct 30, 2026')
            ->toContain('Sep 23');
    });

    it('omits the billing period when no line has a date', function () {
        InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'date' => null]);

        $html = (new InvoiceEmail($this->invoice->fresh()))->render();

        expect($html)->not->toContain('Period');
    });

    it('renders the note as the intro with line breaks', function () {
        $this->invoice->update(['note' => "Hi Kim,\nHere is October."]);
        InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id]);

        $html = (new InvoiceEmail($this->invoice->fresh()))->render();

        expect($html)
            ->toContain('Hi Kim,<br>')
            ->not->toContain('Note:');
    });
});

describe('Invoice billing period', function () {
    it('formats the billing period', function (array $dates, ?string $expected) {
        foreach ($dates as $date) {
            InvoiceLine::factory()->hourly()->create(['invoice_id' => $this->invoice->id, 'date' => $date]);
        }

        expect($this->invoice->fresh()->formattedBillingPeriod())->toBe($expected);
    })->with([
        'single day' => [['2026-10-05'], 'Oct 5, 2026'],
        'same year' => [['2026-10-30', '2026-09-23'], 'Sep 23 – Oct 30, 2026'],
        'across years' => [['2025-12-28', '2026-01-04'], 'Dec 28, 2025 – Jan 4, 2026'],
        'no dates' => [[null], null],
    ]);
});
