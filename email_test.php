<?php
require 'vendor/autoload.php';

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

// Encode password in case of special characters
$transport = Transport::fromDsn('smtp://info@hazz.lk:' . urlencode('Hazz@2025') . '@mail.hazz.lk:587?encryption=tls&verify_peer=0&verify_peer_name=0');
$mailer = new Mailer($transport);

$email = (new Email())
    ->from('info@hazz.lk', 'BMS IMS')
    ->to('student@example.com')
    ->subject('Test Email via Symfony Mailer')
    ->html('<h3>This is a test email</h3>');

try {
    $mailer->send($email);
    echo "Email sent!";
} catch (\Exception $e) {
    echo "Email sending failed: " . $e->getMessage();
}
