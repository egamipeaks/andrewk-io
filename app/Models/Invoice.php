<?php

namespace App\Models;

use App\Mail\InvoiceEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Mail;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'paid',
        'due_date',
        'note',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function getTotalAttribute()
    {
        return $this->invoiceLines->sum('subtotal');
    }

    public function formattedTotal(): string
    {
        return '$' . number_format($this->total, 2);
    }

    public function emailSends(): HasMany
    {
        return $this->hasMany(InvoiceEmailSend::class);
    }

    public function sendInvoiceEmail()
    {
        Mail::to($this->client->email)->send(new InvoiceEmail($this));

        $this->emailSends()->create([
            'sent_at' => now(),
        ]);
    }
}
