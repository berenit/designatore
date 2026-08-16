<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GmailAuthorizeCommand extends Command
{
    protected $signature = 'gmail:authorize';

    protected $description = "Autorizza via OAuth2 (Device Flow) l'account Google da cui inviare le email e genera il refresh token da salvare in .env";

    private const DEVICE_CODE_URL = 'https://oauth2.googleapis.com/device/code';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const GMAIL_SEND_SCOPE = 'https://www.googleapis.com/auth/gmail.send';

    public function handle(): int
    {
        $clientId = config('services.google_mail.client_id') ?: $this->ask('Google OAuth Client ID');
        $clientSecret = config('services.google_mail.client_secret') ?: $this->secret('Google OAuth Client Secret');

        $device = Http::asForm()->post(self::DEVICE_CODE_URL, [
            'client_id' => $clientId,
            'scope' => self::GMAIL_SEND_SCOPE,
        ]);

        if ($device->failed()) {
            $this->error('Richiesta del device code fallita: '.$device->body());

            return self::FAILURE;
        }

        $device = $device->json();

        $this->newLine();
        $this->info("Vai su {$device['verification_url']} da un browser qualsiasi (anche sul telefono) e inserisci questo codice:");
        $this->line('');
        $this->line('    '.$device['user_code']);
        $this->line('');
        $this->line("Accedi con l'account Google da cui devono partire le email e autorizza l'app.");
        $this->line('In attesa della conferma...');

        $token = $this->pollForToken($clientId, $clientSecret, $device['device_code'], $device['interval'] ?? 5, $device['expires_in'] ?? 1800);

        if ($token === null) {
            return self::FAILURE;
        }

        if (! isset($token['refresh_token'])) {
            $this->error(
                "Nessun refresh token ricevuto. Se l'app era già stata autorizzata in passato, revoca l'accesso su ".
                'https://myaccount.google.com/permissions e riprova.'
            );

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Autorizzazione completata. Aggiungi queste variabili al file .env:');
        $this->line('GMAIL_CLIENT_ID='.$clientId);
        $this->line('GMAIL_CLIENT_SECRET='.$clientSecret);
        $this->line('GMAIL_REFRESH_TOKEN='.$token['refresh_token']);

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function pollForToken(string $clientId, string $clientSecret, string $deviceCode, int $interval, int $expiresIn): ?array
    {
        $deadline = time() + $expiresIn;

        while (time() < $deadline) {
            sleep($interval);

            $response = Http::asForm()->post(self::TOKEN_URL, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'device_code' => $deviceCode,
                'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            ])->json();

            if (isset($response['access_token'])) {
                return $response;
            }

            $error = $response['error'] ?? null;

            if ($error === 'authorization_pending') {
                continue;
            }

            if ($error === 'slow_down') {
                $interval += 5;

                continue;
            }

            $this->error('Autorizzazione fallita: '.($response['error_description'] ?? $error ?? 'errore sconosciuto'));

            return null;
        }

        $this->error('Tempo scaduto in attesa della conferma su Google.');

        return null;
    }
}
