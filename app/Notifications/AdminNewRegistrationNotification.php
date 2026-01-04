<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\RegistrationRequest; 
use Illuminate\Support\Str;

class AdminNewRegistrationNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $request;
    protected $approvalToken;

    public function __construct(RegistrationRequest $Request)
    {
        $this->request = $Request;
        $this->approvalToken = Str::random(64);
        
        // Sauvegarder le token dans la demande d'inscription
        $Request->update(['approval_token' => $this->approvalToken]);
    }
     

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
         return (new MailMessage)
            ->subject('Nouvelle demande d\'inscription')
            ->view('emails.admin-registration-request', [
                'request' => $this->request,
                'approvalToken' => $this->approvalToken
            ]);
    }  

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
