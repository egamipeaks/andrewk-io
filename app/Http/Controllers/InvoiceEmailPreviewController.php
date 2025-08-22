<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceEmail;
use App\Models\Invoice;

class InvoiceEmailPreviewController extends Controller
{
    public function preview(Invoice $invoice)
    {
        // Check if user is authenticated
        if (! auth()->check()) {
            abort(404);
        }

        // Check if user can preview invoice emails
        if (! auth()->user()->canPreviewInvoiceEmails()) {
            abort(403, 'Unauthorized - You do not have permission to preview invoice emails.');
        }

        // Generate the email preview
        $mailable = new InvoiceEmail($invoice);

        // Render the email preview
        return $mailable->render();
    }
}
