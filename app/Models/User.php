<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'referee_id', 'must_change_password', 'pending_email'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Ruolo degli arbitri: vedono solo la dashboard e gestiscono il proprio profilo. */
    public const ROLE_REFEREE = 'arbitro';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    /** Scheda arbitro collegata (anche per un designatore che è pure arbitro). */
    public function referee()
    {
        return $this->belongsTo(Referee::class);
    }

    /** Vero se l'utente è solo arbitro, quindi senza accesso alle funzioni di gestione. */
    public function isReferee(): bool
    {
        return $this->role === self::ROLE_REFEREE;
    }

    /** Email di recupero password in italiano, indipendente dalla lingua configurata. */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $notification = new ResetPassword($token);

        $notification->toMailUsing(function (self $user, string $token) {
            $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('Recupero password')
                ->greeting('Ciao,')
                ->line('Hai ricevuto questa email perché è stato chiesto il recupero della password del tuo account.')
                ->action('Scegli una nuova password', route('password.reset', ['token' => $token, 'email' => $user->email]))
                ->line("Il link scade tra {$minutes} minuti.")
                ->line('Se non hai chiesto tu il recupero, ignora questa email.')
                ->salutation('Grazie!');
        });

        $this->notify($notification);
    }
}
