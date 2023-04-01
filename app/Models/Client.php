<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
    ];

    /**
     * Get the invoices associated with the client.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
