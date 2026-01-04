<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

class AccountApproved extends Notification
{
    use Queueable;
    protected $user;
    protected $password; 
    protected $setPasswordUrl;
    /**
     * Create a new notification instance.
     */
    public function __construct(User $user, string $password, string $token)
    { 
        $this->user = $user;
        $this->password = $password;
        $this->setPasswordUrl = url('/set-initial-password?token=' . $token . '&email=' . urlencode($user->email));
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
            ->subject('Votre compte a été approuvé')
            ->view('emails.account-approved', [
                'user' => $this->user,
                'password' => $this->password,
                'setPasswordUrl' => $this->setPasswordUrl
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
