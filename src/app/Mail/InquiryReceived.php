<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InquiryReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Inquiry $inquiry,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "【お問い合わせ受付】{$this->inquiry->inquiry_number}",
        );
    }

    public function content(): Content
    {
        $message = $this->inquiry->messages()->where('message_type', 'initial_inquiry')->first();

        return new Content(
            text: 'emails.inquiry-received',
            with: [
                'inquiry' => $this->inquiry,
                'initialMessage' => $message,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
