<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

final class EmailService
{
    public function __construct(private readonly array $mailConfig)
    {
    }

    public function sendForgotPassword(string $toEmail, string $toName, string $resetLink, string $templatePath): void
    {
        $template = file_get_contents($templatePath) ?: '';
        $html = str_replace(['{{name}}', '{{reset_link}}'], [htmlspecialchars($toName), htmlspecialchars($resetLink)], $template);

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $this->mailConfig['host'];
            $mail->Port = $this->mailConfig['port'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->mailConfig['username'];
            $mail->Password = $this->mailConfig['password'];
            $mail->SMTPSecure = $this->mailConfig['encryption'];

            $mail->setFrom($this->mailConfig['from_email'], $this->mailConfig['from_name']);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = 'Redefinição de senha';
            $mail->Body = $html;

            $mail->send();
        } catch (Exception) {
            // Intencionalmente silencioso para não expor detalhes sensíveis.
        }
    }

    public function sendWelcomeEmail(string $toEmail, string $toName, string $verifyLink, string $templatePath): void
    {
        $template = file_get_contents($templatePath) ?: '';
        $html = str_replace(['{{name}}', '{{verify_link}}'], [htmlspecialchars($toName), htmlspecialchars($verifyLink)], $template);

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $this->mailConfig['host'];
            $mail->Port = $this->mailConfig['port'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->mailConfig['username'];
            $mail->Password = $this->mailConfig['password'];
            $mail->SMTPSecure = $this->mailConfig['encryption'];

            $mail->setFrom($this->mailConfig['from_email'], $this->mailConfig['from_name']);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = 'Bem-vindo à Nexora - Confirme seu e-mail';
            $mail->Body = $html;

            $mail->send();
        } catch (Exception) {
            // Intencionalmente silencioso para não expor detalhes sensíveis.
        }
    }
}
