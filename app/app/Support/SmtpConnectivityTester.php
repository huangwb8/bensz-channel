<?php

namespace App\Support;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class SmtpConnectivityTester
{
    public function sendTestMessage(array $config, string $recipient): void
    {
        $scheme = $config['scheme'] ?? null;

        if (! is_string($scheme) || $scheme === '') {
            $scheme = ((int) ($config['port'] ?? 0)) === 465 ? 'smtps' : 'smtp';
        }

        $transport = (new EsmtpTransportFactory)->create(new Dsn(
            $scheme,
            (string) $config['host'],
            $config['username'] ?: null,
            $config['password'] ?: null,
            $config['port'] ?: null,
            [
                'auto_tls' => true,
            ],
        ));

        $mailer = new Mailer($transport);

        $message = (new Email)
            ->from(new Address((string) $config['from_address'], (string) $config['from_name']))
            ->to($recipient)
            ->subject('Bensz Channel SMTP 测试邮件')
            ->text("这是一封来自 Bensz Channel 管理后台的 SMTP 测试邮件。\n\n如果你收到了这封邮件，说明当前 SMTP 配置可用。");

        $mailer->send($message);
    }
}
