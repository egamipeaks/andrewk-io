<?php

use App\Enums\Currency;
use App\Enums\HoursUpdateFrequency;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Mail\HoursUpdateEmail;
use App\Models\Client;
use App\Models\HoursUpdateSend;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;

beforeEach(function () {
    Mail::fake();

    $this->client = Client::factory()->create([
        'email' => 'client@example.com',
        'currency' => 'USD',
        'hourly_rate' => 100,
    ]);
});

describe('unbilled hours by project', function () {
    it('groups this month\'s unbilled hours by project with General last', function () {
        $asOf = Carbon::parse('2026-10-16');
        $website = Project::factory()->for($this->client)->create(['name' => 'Website']);
        $api = Project::factory()->for($this->client)->create(['name' => 'API']);

        TimeEntry::factory()->forProject($website)->create(['date' => '2026-10-02', 'hours' => 3]);
        TimeEntry::factory()->forProject($website)->create(['date' => '2026-10-10', 'hours' => 1.5]);
        TimeEntry::factory()->forProject($api)->create(['date' => '2026-10-05', 'hours' => 2]);
        TimeEntry::factory()->create(['client_id' => $this->client->id, 'date' => '2026-10-06', 'hours' => 1]);

        $groups = $this->client->unbilledHoursByProject($asOf);

        expect($groups->pluck('name')->all())->toBe(['API', 'Website', 'General'])
            ->and($groups->pluck('hours')->all())->toBe([2.0, 4.5, 1.0])
            ->and($groups->pluck('amount')->all())->toBe([200.0, 450.0, 100.0]);
    });

    it('excludes invoiced entries, other months and future dates', function () {
        $asOf = Carbon::parse('2026-10-16');

        TimeEntry::factory()->create(['client_id' => $this->client->id, 'date' => '2026-10-03', 'hours' => 2]);
        TimeEntry::factory()->billed()->create(['client_id' => $this->client->id, 'date' => '2026-10-04', 'hours' => 5]);
        TimeEntry::factory()->create(['client_id' => $this->client->id, 'date' => '2026-09-30', 'hours' => 7]);
        TimeEntry::factory()->create(['client_id' => $this->client->id, 'date' => '2026-10-20', 'hours' => 9]);

        $groups = $this->client->unbilledHoursByProject($asOf);

        expect($groups->sum('hours'))->toBe(2.0);
    });

    it('converts amounts into the client currency', function () {
        $this->client->update(['currency' => 'CAD']);
        TimeEntry::factory()->create(['client_id' => $this->client->id, 'date' => '2026-10-03', 'hours' => 2]);

        $groups = $this->client->unbilledHoursByProject(Carbon::parse('2026-10-16'));

        expect($groups->first()['amount'])->toBe(Currency::CAD->fromUsd(200));
    });
});

describe('sending an hours update', function () {
    it('emails the client and logs the send', function () {
        TimeEntry::factory()->create(['client_id' => $this->client->id, 'date' => '2026-10-03', 'hours' => 2.5]);

        $sent = $this->client->sendHoursUpdate(Carbon::parse('2026-10-16'));

        expect($sent)->toBeTrue();
        Mail::assertSent(HoursUpdateEmail::class, fn (HoursUpdateEmail $mail) => $mail->hasTo('client@example.com'));
        assertDatabaseHas('hours_update_sends', [
            'client_id' => $this->client->id,
            'email' => 'client@example.com',
            'hours' => 2.5,
        ]);
    });

    it('skips sending when there are no unbilled hours this month', function () {
        TimeEntry::factory()->billed()->create(['client_id' => $this->client->id, 'date' => '2026-10-03']);

        $sent = $this->client->sendHoursUpdate(Carbon::parse('2026-10-16'));

        expect($sent)->toBeFalse();
        Mail::assertNothingSent();
        assertDatabaseCount('hours_update_sends', 0);
    });

    it('renders the not a bill notice with project rows and total', function () {
        $project = Project::factory()->for($this->client)->create(['name' => 'Website']);
        TimeEntry::factory()->forProject($project)->create(['date' => '2026-10-03', 'hours' => 3]);
        $asOf = Carbon::parse('2026-10-16');

        $mail = new HoursUpdateEmail($this->client, $this->client->unbilledHoursByProject($asOf), $asOf);

        $mail->assertHasSubject('Hours update for October 2026 (not a bill)');
        $mail->assertSeeInHtml('This is not a bill.');
        $mail->assertSeeInHtml('Website');
        $mail->assertSeeInHtml('Not yet invoiced (estimate)');
        $mail->assertSeeInHtml('$300');
    });
});

describe('send-hours-updates command', function () {
    it('sends to clients whose frequency is due today', function (string $today, array $expectedEmails) {
        travelTo(Carbon::parse("{$today} 09:00", 'America/Chicago'));

        $weekly = Client::factory()->create(['email' => 'weekly@example.com', 'hours_update_frequency' => HoursUpdateFrequency::Weekly]);
        $midMonth = Client::factory()->create(['email' => 'mid@example.com', 'hours_update_frequency' => HoursUpdateFrequency::MidMonth]);
        $off = Client::factory()->create(['email' => 'off@example.com', 'hours_update_frequency' => HoursUpdateFrequency::Off]);
        $inactive = Client::factory()->inactive()->create(['email' => 'inactive@example.com', 'hours_update_frequency' => HoursUpdateFrequency::Weekly]);

        foreach ([$weekly, $midMonth, $off, $inactive] as $client) {
            TimeEntry::factory()->create(['client_id' => $client->id, 'date' => Carbon::parse($today)->startOfMonth()]);
        }

        artisan('send-hours-updates')->assertSuccessful();

        Mail::assertSentCount(count($expectedEmails));

        foreach ($expectedEmails as $email) {
            Mail::assertSent(HoursUpdateEmail::class, fn (HoursUpdateEmail $mail) => $mail->hasTo($email));
        }
    })->with([
        'a Monday' => ['2026-10-12', ['weekly@example.com']],
        'the 15th' => ['2026-10-15', ['mid@example.com']],
        'Monday the 15th' => ['2027-03-15', ['weekly@example.com', 'mid@example.com']],
        'any other day' => ['2026-10-14', []],
    ]);

    it('skips due clients without unbilled hours', function () {
        travelTo(Carbon::parse('2026-10-12 09:00', 'America/Chicago'));
        Client::factory()->create(['hours_update_frequency' => HoursUpdateFrequency::Weekly]);

        artisan('send-hours-updates')
            ->expectsOutputToContain('Sent 0 of 1 due hours updates, 0 failed.')
            ->assertSuccessful();

        Mail::assertNothingSent();
    });
});

describe('send-hours-updates command resilience', function () {
    beforeEach(function () {
        travelTo(Carbon::parse('2026-10-12 09:00', 'America/Chicago'));
    });

    it('keeps sending to other clients when one send fails', function () {
        $broken = Client::factory()->create(['email' => 'broken@example.com', 'hours_update_frequency' => HoursUpdateFrequency::Weekly]);
        $working = Client::factory()->create(['email' => 'ok@example.com', 'hours_update_frequency' => HoursUpdateFrequency::Weekly]);
        TimeEntry::factory()->create(['client_id' => $broken->id, 'date' => '2026-10-05']);
        TimeEntry::factory()->create(['client_id' => $working->id, 'date' => '2026-10-05']);

        HoursUpdateSend::creating(function (HoursUpdateSend $send): void {
            if ($send->email === 'broken@example.com') {
                throw new RuntimeException('Mail provider unavailable');
            }
        });

        artisan('send-hours-updates')
            ->expectsOutputToContain('Failed: Mail provider unavailable')
            ->expectsOutputToContain('Sent 1 of 2 due hours updates, 1 failed.')
            ->assertFailed();

        assertDatabaseHas('hours_update_sends', ['client_id' => $working->id]);
    });

    it('does not send twice on the same day', function () {
        $client = Client::factory()->create(['hours_update_frequency' => HoursUpdateFrequency::Weekly]);
        TimeEntry::factory()->create(['client_id' => $client->id, 'date' => '2026-10-05']);

        artisan('send-hours-updates')->assertSuccessful();
        artisan('send-hours-updates')
            ->expectsOutputToContain('Skipped, already sent today.')
            ->assertSuccessful();

        Mail::assertSentCount(1);
    });

    it('still sends when the last update was on an earlier day', function () {
        $client = Client::factory()->create(['hours_update_frequency' => HoursUpdateFrequency::Weekly]);
        TimeEntry::factory()->create(['client_id' => $client->id, 'date' => '2026-10-05']);
        HoursUpdateSend::factory()->for($client)->create(['sent_at' => Carbon::parse('2026-10-11 23:30', 'America/Chicago')]);

        artisan('send-hours-updates')->assertSuccessful();

        Mail::assertSentCount(1);
    });
});

describe('client admin', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create([
            'email' => 'admin@andrewk.io',
            'email_verified_at' => now(),
        ]);
    });

    it('saves the hours update frequency', function () {
        Livewire::actingAs($this->admin)
            ->test(EditClient::class, ['record' => $this->client->id])
            ->fillForm(['hours_update_frequency' => HoursUpdateFrequency::Weekly->value])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($this->client->refresh()->hours_update_frequency)->toBe(HoursUpdateFrequency::Weekly);
    });

    it('sends an hours update from the edit page', function () {
        TimeEntry::factory()->create(['client_id' => $this->client->id, 'date' => today('America/Chicago')]);

        Livewire::actingAs($this->admin)
            ->test(EditClient::class, ['record' => $this->client->id])
            ->callAction('sendHoursUpdate')
            ->assertNotified('Hours Update Sent');

        Mail::assertSent(HoursUpdateEmail::class);
        assertDatabaseCount('hours_update_sends', 1);
    });

    it('cannot send an hours update without going through the action', function () {
        TimeEntry::factory()->create(['client_id' => $this->client->id, 'date' => today('America/Chicago')]);

        expect(fn () => Livewire::actingAs($this->admin)
            ->test(EditClient::class, ['record' => $this->client->id])
            ->call('sendHoursUpdate'))
            ->toThrow(Exception::class);

        Mail::assertNothingSent();
    });

    it('warns when there is nothing to send', function () {
        Livewire::actingAs($this->admin)
            ->test(EditClient::class, ['record' => $this->client->id])
            ->callAction('sendHoursUpdate')
            ->assertNotified('Nothing to send');

        Mail::assertNothingSent();
    });

    it('links to the preview from the edit page', function () {
        Livewire::actingAs($this->admin)
            ->test(EditClient::class, ['record' => $this->client->id])
            ->assertActionHasUrl('previewHoursUpdate', route('client.hours-update.preview', $this->client));
    });
});

describe('hours update preview', function () {
    it('renders the email for an admin', function () {
        $admin = User::factory()->create(['email' => 'admin@andrewk.io', 'email_verified_at' => now()]);
        $project = Project::factory()->for($this->client)->create(['name' => 'Website']);
        TimeEntry::factory()->forProject($project)->create(['date' => today('America/Chicago'), 'hours' => 2]);

        actingAs($admin)
            ->get(route('client.hours-update.preview', $this->client))
            ->assertSuccessful()
            ->assertSee('This is not a bill.')
            ->assertSee('Website');
    });

    it('shows an empty state when there are no un-invoiced hours', function () {
        $admin = User::factory()->create(['email' => 'admin@andrewk.io', 'email_verified_at' => now()]);

        actingAs($admin)
            ->get(route('client.hours-update.preview', $this->client))
            ->assertSuccessful()
            ->assertSee('No un-invoiced hours this month.');
    });

    it('is forbidden for non-admin users', function () {
        $user = User::factory()->create(['email' => 'user@example.com', 'email_verified_at' => now()]);

        actingAs($user)
            ->get(route('client.hours-update.preview', $this->client))
            ->assertForbidden();
    });

    it('is not found for guests', function () {
        get(route('client.hours-update.preview', $this->client))->assertNotFound();
    });
});
