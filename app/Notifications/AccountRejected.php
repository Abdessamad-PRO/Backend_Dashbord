<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\RegistrationRequest;
class AccountRejected extends Notification
{
    use Queueable;
    protected $request;
    /**
     * Create a new notification instance.
     */
    public function __construct(RegistrationRequest $request)
    {
        $this->request = $request;
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
        $message = (new MailMessage)
            ->subject('Demande d\'inscription refusée')
            ->greeting('Bonjour ' . $this->request->prenom . ',')
            ->line('Nous sommes désolés de vous informer que votre demande d\'inscription a été refusée.');
        
        if ($this->request->rejection_reason) {
            $message->line('Raison: ' . $this->request->rejection_reason);
        }
        
        return $message->line('Si vous pensez qu\'il s\'agit d\'une erreur, veuillez contacter l\'administrateur.')
            ->line('Merci de votre compréhension.');
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
