<?php

namespace App\Mail;

use App\Models\Client;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class HoursUpdateEmail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param Collection<int, array{name: string, hours: float, amount: float}> $groups */
    public function __construct(
        public Client $client,
        public Collection $groups,
        public CarbonInterface $asOf,
    ) {}

    public function envelope(): Envelope
    {
        $fromEmail = $this->client->email_from ?? config('mail.from.address');

        return new Envelope(
            from: new Address($fromEmail, 'Andrew Krzynowek'),
            subject: "Hours update for {$this->asOf->format('F Y')} (not a bill)",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.hours-update',
        );
    }
}
