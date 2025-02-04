<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenerateLabels extends Mailable
{
    use Queueable, SerializesModels;

    public $attachment;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($attachment, $subject)
    {
        $this->attachment = $attachment;
        $this->subject = $subject;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        $content = "Please see attached documents for labels and invoices.";
        return new Content(
            //html: $content
            view: 'emails.plain_html',
            with: ['content'=>$content],

        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        if(is_array($this->attachment)){
            return $this->attachment;
        }
        return [$this->attachment];
    }
}
