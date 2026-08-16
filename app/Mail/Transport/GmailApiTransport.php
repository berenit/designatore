<?php

namespace App\Mail\Transport;

use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Google\Service\Gmail\Message as GmailApiMessage;
use RuntimeException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class GmailApiTransport extends AbstractTransport
{
    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $refreshToken,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $client = $this->authorizedClient();
        $gmail = new Gmail($client);

        $gmailMessage = new GmailApiMessage();
        $gmailMessage->setRaw($this->encodeRaw($message->toString()));

        $gmail->users_messages->send('me', $gmailMessage);
    }

    private function authorizedClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->addScope(Gmail::GMAIL_SEND);

        $token = $client->fetchAccessTokenWithRefreshToken($this->refreshToken);

        if (isset($token['error'])) {
            throw new RuntimeException(
                'Impossibile ottenere un access token Gmail: '.($token['error_description'] ?? $token['error'])
            );
        }

        $client->setAccessToken($token);

        return $client;
    }

    // Gmail API richiede il MIME grezzo in base64url (RFC 4648 §5), non il
    // base64 standard prodotto da base64_encode().
    private function encodeRaw(string $rfc2822Message): string
    {
        return rtrim(strtr(base64_encode($rfc2822Message), '+/', '-_'), '=');
    }

    public function __toString(): string
    {
        return 'gmail-api';
    }
}
