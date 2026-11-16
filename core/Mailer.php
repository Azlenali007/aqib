<?php
declare(strict_types=1);

class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody): array
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid recipient email address.'];
        }

        $smtpHost = (string) get_setting('smtp_host', '');
        $smtpPort = (int) get_setting('smtp_port', 587);
        $smtpUser = (string) get_setting('smtp_user', '');
        $smtpPass = Security::decrypt((string) get_setting('smtp_pass', ''));
        $smtpEnc  = (string) get_setting('smtp_encryption', 'tls');
        $fromEmail = (string) get_setting('smtp_from_email', get_setting('support_email', 'noreply@example.com'));
        $fromName  = (string) get_setting('smtp_from_name', get_setting('site_name', 'SMM Panel'));

        if ($smtpHost !== '' && $smtpUser !== '') {
            return self::sendSmtp($smtpHost, $smtpPort, $smtpUser, $smtpPass, $smtpEnc, $fromEmail, $fromName, $to, $subject, $htmlBody);
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . sprintf('"%s" <%s>', addslashes($fromName), $fromEmail),
            'Reply-To: ' . $fromEmail,
        ];
        $ok = @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
        if (!$ok) {
            Logger::file('mail.log', "mail() failed to $to: $subject");
            return ['success' => false, 'error' => 'Mail delivery failed (configure SMTP in Admin Settings).'];
        }
        return ['success' => true];
    }

    private static function sendSmtp(
        string $host,
        int $port,
        string $user,
        string $pass,
        string $enc,
        string $fromEmail,
        string $fromName,
        string $to,
        string $subject,
        string $htmlBody
    ): array {
        $transport = ($enc === 'ssl') ? "ssl://$host" : $host;
        $fp = @stream_socket_client("$transport:$port", $errno, $errstr, 12);
        if (!$fp) {
            Logger::file('mail.log', "SMTP connect failed ($host:$port): $errstr");
            return ['success' => false, 'error' => "SMTP connection error: $errstr"];
        }
        stream_set_timeout($fp, 12);
        $read = static function () use ($fp): string {
            $data = '';
            while ($line = fgets($fp, 515)) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            return $data;
        };
        $cmd = static function (string $c) use ($fp, $read): string {
            fwrite($fp, $c . "\r\n");
            return $read();
        };

        $read();
        $cmd('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        if ($enc === 'tls') {
            $resp = $cmd('STARTTLS');
            if (str_starts_with($resp, '220')) {
                @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $cmd('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            }
        }
        $cmd('AUTH LOGIN');
        $cmd(base64_encode($user));
        $authResp = $cmd(base64_encode($pass));
        if (!str_starts_with($authResp, '235')) {
            fclose($fp);
            return ['success' => false, 'error' => 'SMTP authentication failed.'];
        }
        $cmd("MAIL FROM:<$fromEmail>");
        $cmd("RCPT TO:<$to>");
        $cmd('DATA');
        $msg = "From: \"$fromName\" <$fromEmail>\r\n"
            . "To: <$to>\r\n"
            . "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
            . $htmlBody . "\r\n.";
        $sendResp = $cmd($msg);
        $cmd('QUIT');
        fclose($fp);

        if (!str_starts_with($sendResp, '250')) {
            return ['success' => false, 'error' => 'SMTP server rejected message: ' . trim($sendResp)];
        }
        return ['success' => true];
    }
}
