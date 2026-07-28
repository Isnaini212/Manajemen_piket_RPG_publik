<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class CustomVerifyEmail extends VerifyEmail
{
    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Panggilan Petualang - Verifikasi Akun')
            ->greeting('Salam Petualang, ' . $notifiable->name . '!')
            ->line('Selamat datang di markas besar Piket RPG. Sebelum kamu bisa memulai quest pertamamu, kami membutuhkan konfirmasi identitas dari sistem akademi.')
            ->action('Verifikasi Akun', $verificationUrl)
            ->line('Jika kamu tidak pernah mendaftar sebagai petualang, abaikan pesan ini.')
            ->salutation('Tertanda, Administrator Akademi Piket RPG');
    }
}
