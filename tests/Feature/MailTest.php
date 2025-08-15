<?php

use App\Mail\InvoiceEmail;
use App\Models\Client;
use App\Models\Invoice;

it('can create invoice email with correct subject and recipient', function () {
    $client = Client::factory()->create([
        'email' => 'test@example.com',
    ]);

    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'due_date' => now()->addDays(30),
    ]);

    $mail = new InvoiceEmail($invoice);

    expect($mail->invoice)->toBe($invoice);

    $envelope = $mail->envelope();
    expect($envelope->subject)->toContain('Invoice (#'.$invoice->id.')');
    expect($envelope->subject)->toContain($invoice->due_date->format('F jS, Y'));
    expect($envelope->from->address)->toBe(config('mail.from.address'));
    expect($envelope->from->name)->toBe('Andrew Krzynowek');
});

it('renders invoice email with markdown template', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
    ]);

    $mail = new InvoiceEmail($invoice);
    $content = $mail->content();

    expect($content->markdown)->toBe('emails.invoice');
});

it('has no attachments by default', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
    ]);

    $mail = new InvoiceEmail($invoice);

    expect($mail->attachments())->toBeEmpty();
});
