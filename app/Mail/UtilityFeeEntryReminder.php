<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class UtilityFeeEntryReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly User $owner,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reminder to record utility fees - '.$this->tenant->name);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.utility-fee-entry-reminder');
    }
}
