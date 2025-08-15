<?php

use App\Mail\InvoiceEmail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\InvoiceEmailSend;
use Illuminate\Support\Facades\Mail;

describe('Invoice Model', function () {
    it('calculates total from invoice lines', function () {
        $invoice = Invoice::factory()->create();
        
        InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 100.50,
        ]);
        
        InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 50.25,
        ]);
        
        expect($invoice->total)->toBe(150.75);
    });
    
    it('formats total with dollar sign and two decimals', function () {
        $invoice = Invoice::factory()->create();
        
        InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
            'amount' => 1234.5,
        ]);
        
        expect($invoice->formattedTotal())->toBe('$1,234.50');
    });
    
    it('sends invoice email and records the send', function () {
        Mail::fake();
        
        $client = Client::factory()->create([
            'email' => 'client@example.com',
        ]);
        
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
        ]);
        
        expect($invoice->emailSends()->count())->toBe(0);
        
        $invoice->sendInvoiceEmail();
        
        Mail::assertSent(InvoiceEmail::class, function ($mail) use ($client, $invoice) {
            return $mail->hasTo($client->email) && 
                   $mail->invoice->id === $invoice->id;
        });
        
        expect($invoice->emailSends()->count())->toBe(1);
        expect($invoice->emailSends()->first()->sent_at)->not->toBeNull();
    });
    
    it('has many invoice lines relationship', function () {
        $invoice = Invoice::factory()->create();
        
        InvoiceLine::factory()->count(3)->create([
            'invoice_id' => $invoice->id,
        ]);
        
        expect($invoice->invoiceLines)->toHaveCount(3);
        expect($invoice->invoiceLines()->count())->toBe(3);
    });
    
    it('belongs to a client', function () {
        $client = Client::factory()->create();
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
        ]);
        
        expect($invoice->client)->toBeInstanceOf(Client::class);
        expect($invoice->client->id)->toBe($client->id);
    });
    
    it('has many email sends relationship', function () {
        $invoice = Invoice::factory()->create();
        
        InvoiceEmailSend::factory()->count(2)->create([
            'invoice_id' => $invoice->id,
        ]);
        
        expect($invoice->emailSends)->toHaveCount(2);
        expect($invoice->emailSends()->count())->toBe(2);
    });
});

describe('Client Model', function () {
    it('has fillable attributes', function () {
        $client = Client::factory()->create([
            'name' => 'Test Client',
            'email' => 'test@example.com',
        ]);
        
        expect($client->name)->toBe('Test Client');
        expect($client->email)->toBe('test@example.com');
    });
    
    it('has many invoices relationship', function () {
        $client = Client::factory()->create();
        
        Invoice::factory()->count(3)->create([
            'client_id' => $client->id,
        ]);
        
        expect($client->invoices)->toHaveCount(3);
        expect($client->invoices()->count())->toBe(3);
    });
});

describe('InvoiceLine Model', function () {
    it('belongs to an invoice', function () {
        $invoice = Invoice::factory()->create();
        $invoiceLine = InvoiceLine::factory()->create([
            'invoice_id' => $invoice->id,
        ]);
        
        expect($invoiceLine->invoice)->toBeInstanceOf(Invoice::class);
        expect($invoiceLine->invoice->id)->toBe($invoice->id);
    });
    
    it('calculates subtotal from amount', function () {
        $invoiceLine = InvoiceLine::factory()->create([
            'description' => 'Fixed Price Service',
            'amount' => 500.00,
            'hourly_rate' => null,
            'hours' => null,
        ]);
        
        expect($invoiceLine->subtotal)->toBe(500.00);
    });
    
    it('calculates subtotal from hourly rate and hours', function () {
        $invoiceLine = InvoiceLine::factory()->create([
            'description' => 'Hourly Service',
            'amount' => null,
            'hourly_rate' => 75.00,
            'hours' => 4.5,
        ]);
        
        expect($invoiceLine->subtotal)->toBe(337.50);
    });
    
    it('returns 0 subtotal when no amount or hours', function () {
        $invoiceLine = InvoiceLine::factory()->create([
            'description' => 'Empty Service',
            'amount' => null,
            'hourly_rate' => null,
            'hours' => null,
        ]);
        
        expect($invoiceLine->subtotal)->toBe(0);
    });
    
    it('formats subtotal with dollar sign', function () {
        $invoiceLine = InvoiceLine::factory()->create([
            'amount' => 1234.5,
        ]);
        
        expect($invoiceLine->formattedSubTotal())->toBe('$1,234.50');
    });
});

describe('InvoiceEmailSend Model', function () {
    it('belongs to an invoice', function () {
        $invoice = Invoice::factory()->create();
        $emailSend = InvoiceEmailSend::factory()->create([
            'invoice_id' => $invoice->id,
        ]);
        
        expect($emailSend->invoice)->toBeInstanceOf(Invoice::class);
        expect($emailSend->invoice->id)->toBe($invoice->id);
    });
    
    it('records sent timestamp', function () {
        $emailSend = InvoiceEmailSend::factory()->create([
            'sent_at' => now(),
        ]);
        
        expect($emailSend->sent_at)->not->toBeNull();
    });
    
    it('converts sent_at to Chicago timezone', function () {
        $utcTime = '2024-01-15 18:00:00'; // 6 PM UTC
        $emailSend = InvoiceEmailSend::factory()->create([
            'sent_at' => $utcTime,
        ]);
        
        expect($emailSend->sent_at->timezone->getName())->toBe('America/Chicago');
        // UTC 18:00 = Chicago 12:00 (CST) or 13:00 (CDT)
        expect($emailSend->sent_at->format('Y-m-d'))->toBe('2024-01-15');
    });
});