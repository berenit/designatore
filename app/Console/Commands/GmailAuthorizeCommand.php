<?php

namespace App\Console\Commands;

use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Illuminate\Console\Command;

class GmailAuthorizeCommand extends Command
{
    protected $signature = 'gmail:authorize {--port=8901 : Porta locale su cui ricevere il redirect OAuth2}';

    protected $description = "Autorizza via OAuth2 l'account Google da cui inviare le email e genera il refresh token da salvare in .env";

    public function handle(): int
    {
        $clientId = config('services.google_mail.client_id') ?: $this->ask('Google OAuth Client ID');
        $clientSecret = config('services.google_mail.client_secret') ?: $this->secret('Google OAuth Client Secret');
        $port = (int) $this->option('port');

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->addScope(Gmail::GMAIL_SEND);
        // Senza uno state esplicito, il client aggiunge "state" vuoto
        // all'URL (senza "="), che Google rifiuta con "invalid_request".
        $client->setState(bin2hex(random_bytes(16)));

        $code = $this->captureAuthorizationCode($client, $port);

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
     * Riceve il redirect OAuth2 su una porta fissa (di default 8901), in
     * ascolto su tutte le interfacce. Su un server remoto/headless, va
     * raggiunta dal browser locale con un tunnel SSH:
     *   ssh -L 8901:localhost:8901 utente@server
     * Il tipo di credenziale OAuth2 deve essere "App desktop": Google
     * accetta qualunque porta su un redirect loopback (127.0.0.1/localhost)
     * senza doverla registrare in anticipo.
     */
    private function captureAuthorizationCode(GoogleClient $client, int $port): ?string
    {
        $server = stream_socket_server("tcp://0.0.0.0:{$port}", $errno, $errstr);

        if (! $server) {
            $this->error("Impossibile aprire un server locale sulla porta {$port}: {$errstr}");

            return null;
        }

        $redirectUri = "http://127.0.0.1:{$port}/callback";
        $client->setRedirectUri($redirectUri);

        $this->info("Apri quest'URL, accedi con l'account Google da cui inviare le email e autorizza l'app:");
        $this->line($client->createAuthUrl());
        $this->line("In attesa del redirect su {$redirectUri} (timeout 300s)...");
        $this->line('Se questo comando gira su un server remoto senza browser, apri un tunnel SSH prima di procedere:');
        $this->line("  ssh -L {$port}:localhost:{$port} <utente>@<host-remoto>");

        $connection = stream_socket_accept($server, 300);
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
