<?php

namespace App\Mail;

use App\Models\Inquiry;
use App\Models\InquiryMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InquiryReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Inquiry $inquiry,
        public InquiryMessage $replyMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "【ご回答】{$this->inquiry->inquiry_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.inquiry-reply',
            with: [
                'inquiry' => $this->inquiry,
                'replyMessage' => $this->replyMessage,
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
