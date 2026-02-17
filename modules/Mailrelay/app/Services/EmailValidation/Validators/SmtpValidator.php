<?php

namespace Modules\Mailrelay\Services\EmailValidation\Validators;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Services\EmailValidation\ValidatorInterface;

class SmtpValidator implements ValidatorInterface
{
    private int $timeout = 10;

    private int $cacheTtl = 86400; // 24 hours

    private string $fromEmail = 'verify@mailrelay.com';

    public function validate(string $email): array
    {
        $domain = $this->extractDomain($email);

        if (! $domain) {
            return [
                'valid' => false,
                'score' => 0,
                'details' => ['error' => 'Invalid email format'],
                'message' => 'Cannot extract domain from email',
            ];
        }

        // Check cache
        $cacheKey = "smtp_validation:{$email}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($email, $domain) {
            return $this->performSmtpCheck($email, $domain);
        });
    }

    public function getName(): string
    {
        return 'smtp';
    }

    private function extractDomain(string $email): ?string
    {
        if (! str_contains($email, '@')) {
            return null;
        }

        $parts = explode('@', $email);

        return end($parts);
    }

    private function performSmtpCheck(string $email, string $domain): array
    {
        // Get MX records for the domain
        $mxHosts = [];
        $mxWeights = [];

        if (! getmxrr($domain, $mxHosts, $mxWeights)) {
            // No MX records, try A record
            if (checkdnsrr($domain, 'A')) {
                $mxHosts = [$domain];
                $mxWeights = [0];
            } else {
                return [
                    'valid' => false,
                    'score' => 0,
                    'details' => ['error' => 'No MX or A records found'],
                    'message' => 'Domain has no mail server records',
                ];
            }
        }

        // Sort MX records by priority (lower weight = higher priority)
        array_multisort($mxWeights, SORT_ASC, $mxHosts);

        // Try each MX host until we get a response
        $lastError = null;
        foreach ($mxHosts as $mxHost) {
            $result = $this->checkSmtpServer($mxHost, $email, $domain);

            if ($result !== null) {
                return $result;
            }

            $lastError = "Failed to connect to MX host: {$mxHost}";
        }

        return [
            'valid' => false,
            'score' => 0,
            'details' => ['error' => $lastError ?? 'Could not connect to any mail server'],
            'message' => 'SMTP validation failed',
        ];
    }

    private function checkSmtpServer(string $mxHost, string $email, string $domain): ?array
    {
        $connection = @fsockopen($mxHost, 25, $errno, $errstr, $this->timeout);

        if (! $connection) {
            Log::debug("SMTP connection failed to {$mxHost}: {$errstr}");

            return null;
        }

        try {
            $details = [
                'mx_host' => $mxHost,
                'responses' => [],
            ];

            // Read greeting
            $response = $this->readResponse($connection);
            $details['responses']['greeting'] = $response;

            if (! $this->isSuccessResponse($response, 220)) {
                return $this->buildFailureResponse('Server greeting failed', $details);
            }

            // Send HELO
            $heloResponse = $this->sendCommand($connection, "HELO {$domain}");
            $details['responses']['helo'] = $heloResponse;

            if (! $this->isSuccessResponse($heloResponse, 250)) {
                return $this->buildFailureResponse('HELO command failed', $details);
            }

            // Send MAIL FROM
            $mailFromResponse = $this->sendCommand($connection, "MAIL FROM:<{$this->fromEmail}>");
            $details['responses']['mail_from'] = $mailFromResponse;

            if (! $this->isSuccessResponse($mailFromResponse, 250)) {
                return $this->buildFailureResponse('MAIL FROM command failed', $details);
            }

            // Send RCPT TO (the actual email validation)
            $rcptToResponse = $this->sendCommand($connection, "RCPT TO:<{$email}>");
            $details['responses']['rcpt_to'] = $rcptToResponse;

            // Check RCPT TO response
            $isValid = $this->isSuccessResponse($rcptToResponse, 250);

            // Some servers return 251 (user not local, will forward)
            if (! $isValid && $this->isSuccessResponse($rcptToResponse, 251)) {
                $isValid = true;
            }

            // Send QUIT
            $quitResponse = $this->sendCommand($connection, 'QUIT');
            $details['responses']['quit'] = $quitResponse;

            $score = $isValid ? 100 : 0;

            // Check for specific error codes
            $message = $isValid ? 'Email address exists on SMTP server' : 'Email address not found on SMTP server';

            if (! $isValid) {
                if (str_contains($rcptToResponse, '550')) {
                    $message = 'Email address does not exist (550)';
                } elseif (str_contains($rcptToResponse, '551')) {
                    $message = 'User not local (551)';
                } elseif (str_contains($rcptToResponse, '552')) {
                    $message = 'Mailbox full (552)';
                } elseif (str_contains($rcptToResponse, '553')) {
                    $message = 'Invalid mailbox name (553)';
                }
            }

            return [
                'valid' => $isValid,
                'score' => $score,
                'details' => $details,
                'message' => $message,
            ];

        } finally {
            @fclose($connection);
        }
    }

    private function sendCommand($connection, string $command): string
    {
        fwrite($connection, $command."\r\n");

        return $this->readResponse($connection);
    }

    private function readResponse($connection): string
    {
        $response = '';

        while ($line = fgets($connection, 1024)) {
            $response .= $line;

            // SMTP responses end when line starts with code and space (not code and dash)
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }

        return trim($response);
    }

    private function isSuccessResponse(string $response, int $expectedCode): bool
    {
        return str_starts_with($response, (string) $expectedCode);
    }

    private function buildFailureResponse(string $message, array $details): array
    {
        return [
            'valid' => false,
            'score' => 0,
            'details' => $details,
            'message' => $message,
        ];
    }
}
