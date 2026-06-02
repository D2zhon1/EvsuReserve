<?php

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

function evsu_load_env(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    }

    $envFile = dirname(__DIR__) . '/.env';
    if (is_file($envFile) && class_exists(Dotenv\Dotenv::class)) {
        Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
    }
}

function evsu_mail_from(): array
{
    evsu_load_env();

    $address = $_ENV['MAIL_FROM_ADDRESS'] ?? getenv('MAIL_FROM_ADDRESS') ?: 'noreply@evsu.edu.ph';
    $name    = $_ENV['MAIL_FROM_NAME'] ?? getenv('MAIL_FROM_NAME') ?: 'EVSU Reserve';

    return [$address, $name];
}

function evsu_send_email(string $to, string $subject, string $htmlBody, ?string $textBody = null): bool
{
    evsu_load_env();

    [$fromAddress, $fromName] = evsu_mail_from();
    $textBody = $textBody ?? strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

    $driver = strtolower($_ENV['MAIL_DRIVER'] ?? getenv('MAIL_DRIVER') ?: 'log');

    if ($driver === 'log') {
        $logDir = dirname(__DIR__) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $line = sprintf(
            "[%s] TO: %s | SUBJECT: %s\n%s\n\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $textBody
        );
        file_put_contents($logDir . '/mail.log', $line, FILE_APPEND | LOCK_EX);
        return true;
    }

    if (!class_exists(PHPMailer::class)) {
        error_log('EVSU Reserve: PHPMailer not installed. Run composer install.');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = evsu_email_wrap($htmlBody);
        $mail->AltBody = $textBody;

        if ($driver === 'mail') {
            $mail->isMail();
        } else {
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST'] ?? getenv('MAIL_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME'] ?? getenv('MAIL_USERNAME') ?: '';
            $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? getenv('MAIL_PASSWORD') ?: '';
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? getenv('MAIL_ENCRYPTION') ?: PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) ($_ENV['MAIL_PORT'] ?? getenv('MAIL_PORT') ?: 587);
        }

        $mail->send();
        return true;
    } catch (MailException $e) {
        error_log('EVSU Reserve mail error: ' . $e->getMessage());
        return false;
    }
}

function evsu_email_wrap(string $content): string
{
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Source Sans 3,Arial,sans-serif;color:#1a1a1a;line-height:1.5;">'
        . '<div style="max-width:560px;margin:0 auto;padding:24px;">'
        . '<p style="color:#8b0000;font-weight:bold;font-size:18px;margin:0 0 16px;">EVSU Reserve</p>'
        . $content
        . '<p style="margin-top:24px;font-size:12px;color:#666;">This is an automated message. Please do not reply.</p>'
        . '</div></body></html>';
}

function evsu_send_order_completed_email(
    string $to,
    string $studentName,
    string $orderNumber,
    float $totalAmount,
    array $items
): bool {
    $safeName   = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');
    $safeOrder  = htmlspecialchars($orderNumber, ENT_QUOTES, 'UTF-8');
    $safeTotal  = htmlspecialchars(number_format($totalAmount, 2), ENT_QUOTES, 'UTF-8');

    $itemsHtml = '';
    $itemsText = '';
    foreach ($items as $item) {
        $name  = htmlspecialchars((string) ($item['product_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $qty   = (int) ($item['quantity'] ?? 0);
        $size  = trim((string) ($item['size'] ?? ''));
        $sizeLabel = $size !== '' ? ' (' . htmlspecialchars($size, ENT_QUOTES, 'UTF-8') . ')' : '';
        $itemsHtml .= '<li>' . $name . $sizeLabel . ' × ' . $qty . '</li>';
        $itemsText .= "- {$item['product_name']}" . ($size !== '' ? " ({$size})" : '') . " × {$qty}\n";
    }

    $html = '<p>Hello ' . $safeName . ',</p>'
        . '<p>Your order <strong>' . $safeOrder . '</strong> has been marked as <strong>completed</strong> by staff.</p>'
        . '<p>Your order is confirmed and ready for pickup.</p>'
        . '<p><strong>Total:</strong> ₱' . $safeTotal . '</p>'
        . ($itemsHtml !== '' ? '<p><strong>Items:</strong></p><ul>' . $itemsHtml . '</ul>' : '')
        . '<p>Please bring a valid ID when claiming your order at the IGP office.</p>';

    $text = "Hello {$studentName},\n\n"
        . "Your order {$orderNumber} is completed and ready for pickup.\n"
        . "Total: PHP {$safeTotal}\n\n"
        . $itemsText
        . "\nPlease bring a valid ID when claiming your order.";

    return evsu_send_email($to, "Order {$orderNumber} — Ready for pickup", $html, $text);
}
