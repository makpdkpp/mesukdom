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

final class TrialExpiryReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly User $owner,
        public readonly int $days,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->days <= 0
            ? 'Your trial ends today'
            : "Your trial ends in {$this->days} days";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.trial-expiry-reminder');
    }
}
