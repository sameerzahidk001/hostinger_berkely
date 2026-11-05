<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CertificateIssuedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $subject;
    public $body;
    public $attachmentPath;
    public $attachmentName;

    public function __construct($user, string $subject, string $body, ?string $attachmentPath = null, ?string $attachmentName = null)
    {
        $this->user = $user;
        $this->subject = $subject;
        $this->body = $body;
        $this->attachmentPath = $attachmentPath;
        $this->attachmentName = $attachmentName;
    }

    public function build()
    {
        $mail = $this->subject($this->subject)
            ->view('emails.layout')
            ->with([
                'subject' => $this->subject,
                'body' => $this->body,
            ]);

        if ($this->attachmentPath && is_file($this->attachmentPath)) {
            $mail->attach($this->attachmentPath, [
                'as' => $this->attachmentName ?: 'certificate.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
