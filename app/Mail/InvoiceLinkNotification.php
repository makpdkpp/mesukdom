<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class InvoiceLinkNotification extends Mailable
{
    use Queueable, SerializesModels;

    public readonly string $invoiceUrl;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly ?Customer $customer,
        public readonly string $notificationType = 'invoice_link',
    ) {
        $this->invoiceUrl = $invoice->signedResidentUrl();
    }

    public function envelope(): Envelope
    {
        $subject = $this->notificationType === 'overdue_warning'
            ? 'Overdue Invoice Reminder - '.$this->invoice->invoice_no
            : 'Your Invoice is Ready - '.$this->invoice->invoice_no;

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.invoice-link-notification');
    }
}
