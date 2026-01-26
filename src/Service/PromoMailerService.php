<?php

namespace App\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class PromoMailerService
{
    private $mailer;
    private $twig;

    public function __construct(MailerInterface $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function sendPromoCode(string $to, string $code, ?string $description = null, ?string $expiration = null): void
    {
        $body = $this->twig->render('email/promo_code.html.twig', [
            'code' => $code,
            'description' => $description,
            'expiration' => $expiration
        ]);
        $email = (new Email())
            ->from('no-reply@konvix.com')
            ->to($to)
            ->subject('Votre code promo Konvix')
            ->html($body);
        $this->mailer->send($email);
    }
}
