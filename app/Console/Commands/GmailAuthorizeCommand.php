<?php

namespace App\Console\Commands;

use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Illuminate\Console\Command;

class GmailAuthorizeCommand extends Command
{
    protected $signature = 'gmail:authorize';

    protected $description = "Autorizza via OAuth2 l'account Google da cui inviare le email e genera il refresh token da salvare in .env";

    public function handle(): int
    {
        $clientId = config('services.google_mail.client_id') ?: $this->ask('Google OAuth Client ID');
        $clientSecret = config('services.google_mail.client_secret') ?: $this->secret('Google OAuth Client Secret');

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->addScope(Gmail::GMAIL_SEND);

        $code = $this->captureAuthorizationCode($client);

        if ($code === null) {
            return self::FAILURE;
        }

        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            $this->error('Errore nello scambio del codice: '.($token['error_description'] ?? $token['error']));

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
     * Apre un piccolo server locale sul loopback per catturare il redirect
     * OAuth2 (tipo client "App desktop", nessun redirect URI da registrare
     * su Google Cloud Console: è ammesso qualunque porta su 127.0.0.1).
     */
    private function captureAuthorizationCode(GoogleClient $client): ?string
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);

        if (! $server) {
            $this->error("Impossibile aprire un server locale sul loopback: {$errstr}");

            return null;
        }

        $address = stream_socket_get_name($server, false);
        $port = (int) substr($address, strrpos($address, ':') + 1);
        $redirectUri = "http://127.0.0.1:{$port}/callback";

        $client->setRedirectUri($redirectUri);

        $this->info("Apri quest'URL, accedi con l'account Google da cui inviare le email e autorizza l'app:");
        $this->line($client->createAuthUrl());
        $this->line("In attesa del redirect su {$redirectUri} (timeout 120s)...");

        $connection = stream_socket_accept($server, 120);
        fclose($server);

        if (! $connection) {
            $this->error("Timeout: nessuna risposta ricevuta su {$redirectUri}.");

            return null;
        }

        $request = fread($connection, 8192);
        fwrite(
            $connection,
            "HTTP/1.1 200 OK\r\nContent-Type: text/html; charset=utf-8\r\n\r\n".
            '<html><body>Autorizzazione completata, puoi chiudere questa finestra.</body></html>'
        );
        fclose($connection);

        if (! preg_match('#^GET /callback\?(\S*) HTTP#', (string) $request, $matches)) {
            $this->error('Richiesta di callback non riconosciuta.');

            return null;
        }

        parse_str($matches[1], $query);

        if (isset($query['error'])) {
            $this->error('Autorizzazione negata da Google: '.$query['error']);

            return null;
        }

        return $query['code'] ?? null;
    }
}
