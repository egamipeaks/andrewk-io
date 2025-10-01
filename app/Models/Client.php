<?php

namespace App\Models;

use App\Enums\Currency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'email_from',
        'currency',
        'hourly_rate',
    ];

    protected $casts = [
        'currency' => Currency::class,
    ];

    protected $attributes = [
        'currency' => 'USD',
    ];

    /**
     * Get the invoices associated with the client.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
