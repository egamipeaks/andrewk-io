<?php

namespace App\Http\Controllers;

use App\Models\Client;

class HoursUpdateEmailPreviewController extends Controller
{
    public function preview(Client $client): string
    {
        if (! auth()->check()) {
            abort(404);
        }

        if (! auth()->user()->canPreviewInvoiceEmails()) {
            abort(403, 'Unauthorized - You do not have permission to preview emails.');
        }

        return $client->hoursUpdateEmail(today('America/Chicago'))->render();
    }
}
