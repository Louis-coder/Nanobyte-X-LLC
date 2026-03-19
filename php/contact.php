<?php
/**
 * NANOBYTE X LLC — Contact Form Handler
 * php/contact.php
 *
 * Uses PHPMailer + Gmail SMTP for reliable email delivery.
 *
 * ─── ONE-TIME SETUP ──────────────────────────────────────
 * 1. Run in your project root:
 *       composer require phpmailer/phpmailer
 *    OR manually download PHPMailer and place it in php/PHPMailer/
 *
 * 2. Go to your Gmail account:
 *    → Google Account → Security → 2-Step Verification (enable it)
 *    → Then: myaccount.google.com/apppasswords
 *    → Create App Password for "Mail" → copy the 16-char password
 *
 * 3. Fill in SMTP_USER and SMTP_PASS below.
 * ─────────────────────────────────────────────────────────
 */

declare(strict_types=1);

/* ══════════════════════════════════════
   CONFIG — EDIT THESE VALUES
══════════════════════════════════════ */
define('SMTP_HOST',  'smtp.gmail.com');
define('SMTP_USER',  'nanobytexllc@gmail.com');      // ← your Gmail address
define('SMTP_PASS',  'visq gqfe kyjz yzfp');      // ← 16-char App Password (not your real password)
define('SMTP_PORT',  587);

define('TO_EMAIL',   'nanobytexllc@gmail.com');       // ← where you RECEIVE emails
define('TO_NAME',    'Nanobyte X LLC');
define('SITE_NAME',  'Nanobyte X LLC Website');
/* ══════════════════════════════════════ */

header('Content-Type: application/json; charset=utf-8');

/* Only allow POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

/* ── Load PHPMailer ──
   Option A: via Composer (recommended — run: composer require phpmailer/phpmailer)
   Option B: manual files placed in php/PHPMailer/src/  */
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
$manualPHP        = __DIR__ . '/PHPMailer/src/PHPMailer.php';

if (file_exists($composerAutoload)) {
    require $composerAutoload;
} elseif (file_exists($manualPHP)) {
    require __DIR__ . '/PHPMailer/src/Exception.php';
    require __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require __DIR__ . '/PHPMailer/src/SMTP.php';
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server config error: PHPMailer not found. See README for setup.',
    ]);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* ── Sanitize helper ── */
function clean(string $val): string {
    return htmlspecialchars(trim(strip_tags($val)), ENT_QUOTES, 'UTF-8');
}

/* ── Collect & sanitize ── */
$firstName = clean($_POST['first_name'] ?? '');
$lastName  = clean($_POST['last_name']  ?? '');
$email     = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone     = clean($_POST['phone']    ?? '');
$service   = clean($_POST['service']  ?? '');
$message   = clean($_POST['message']  ?? '');
$fullName  = $firstName . ' ' . $lastName;

/* ── Validate ── */
$errors = [];
if (empty($firstName))                          $errors[] = 'First name is required.';
if (empty($lastName))                           $errors[] = 'Last name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
if (empty($service))                            $errors[] = 'Please select a service.';
if (strlen($message) < 10)                      $errors[] = 'Message must be at least 10 characters.';
if (strlen($message) > 3000)                    $errors[] = 'Message is too long (max 3000 chars).';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

/* ── Simple session-based rate limit (1 per 60 sec) ── */
if (session_status() === PHP_SESSION_NONE) session_start();
if ((time() - ($_SESSION['last_contact_sent'] ?? 0)) < 60) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Please wait a moment before sending again.']);
    exit;
}

/* ══════════════════════════════════════
   SEND EMAIL VIA PHPMAILER + GMAIL SMTP
══════════════════════════════════════ */
$mail = new PHPMailer(true);

try {
    /* SMTP config */
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    /* Recipients — Gmail requires From = your own address */
    $mail->setFrom(SMTP_USER, SITE_NAME);
    $mail->addAddress(TO_EMAIL, TO_NAME);
    $mail->addReplyTo($email, $fullName); // reply goes to the person who submitted

    $mail->Subject = "[{$service}] New Enquiry from {$fullName}";

    /* Plain text fallback */
    $plain  = "New contact form submission via " . SITE_NAME . "\n\n";
    $plain .= "Name:    {$fullName}\n";
    $plain .= "Email:   {$email}\n";
    $plain .= "Phone:   " . ($phone ?: '--') . "\n";
    $plain .= "Service: {$service}\n\n";
    $plain .= "Message:\n{$message}\n\n";
    $plain .= "Sent: " . date('Y-m-d H:i:s T');

    /* Styled HTML email body */
    $html = <<<HTML
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8">
    <style>
      body{font-family:Arial,sans-serif;background:#0d0d0d;color:#e8e0d0;margin:0;padding:0}
      .wrap{max-width:600px;margin:40px auto;background:#141414;border:1px solid rgba(200,146,42,.3)}
      .hd{background:#C8922A;padding:28px 36px}
      .hd h1{margin:0;font-size:1.4rem;color:#000;letter-spacing:.05em}
      .hd p{margin:6px 0 0;font-size:.75rem;color:#3a2800}
      .bd{padding:36px}
      .lbl{font-size:.65rem;letter-spacing:.18em;color:#C8922A;text-transform:uppercase;margin-bottom:6px}
      .val{font-size:1rem;color:#e8e0d0;border-left:3px solid #C8922A;padding-left:12px;margin-bottom:20px}
      .msg{background:#1a1a1a;border:1px solid rgba(200,146,42,.2);padding:20px;margin-top:8px;line-height:1.75}
      .ft{background:#0a0a0a;padding:16px 36px;font-size:.7rem;color:#8a8070;border-top:1px solid rgba(200,146,42,.1)}
      a{color:#C8922A}
    </style>
    </head>
    <body>
    <div class="wrap">
      <div class="hd">
        <h1>📩 New Contact Enquiry</h1>
        <p>SITE_NAME_PLACEHOLDER &bull; DATE_PLACEHOLDER</p>
      </div>
      <div class="bd">
        <div class="lbl">Full Name</div>
        <div class="val">FULLNAME_PLACEHOLDER</div>
        <div class="lbl">Email Address</div>
        <div class="val"><a href="mailto:EMAIL_PLACEHOLDER">EMAIL_PLACEHOLDER</a></div>
        <div class="lbl">Phone</div>
        <div class="val">PHONE_PLACEHOLDER</div>
        <div class="lbl">Service Interest</div>
        <div class="val">SERVICE_PLACEHOLDER</div>
        <div class="lbl">Message</div>
        <div class="msg">MESSAGE_PLACEHOLDER</div>
      </div>
      <div class="ft">Sent via SITE_NAME_PLACEHOLDER &mdash; Reply to this email to respond directly to FIRSTNAME_PLACEHOLDER.</div>
    </div>
    </body></html>
    HTML;

    $html = str_replace([
        'SITE_NAME_PLACEHOLDER',
        'DATE_PLACEHOLDER',
        'FULLNAME_PLACEHOLDER',
        'EMAIL_PLACEHOLDER',
        'PHONE_PLACEHOLDER',
        'SERVICE_PLACEHOLDER',
        'MESSAGE_PLACEHOLDER',
        'FIRSTNAME_PLACEHOLDER',
    ], [
        SITE_NAME,
        date('Y-m-d H:i T'),
        htmlspecialchars($fullName),
        htmlspecialchars($email),
        htmlspecialchars($phone ?: '--'),
        htmlspecialchars($service),
        nl2br(htmlspecialchars($message)),
        htmlspecialchars($firstName),
    ], $html);

    $mail->isHTML(true);
    $mail->Body    = $html;
    $mail->AltBody = $plain;

    $mail->send();

    /* ── Auto-reply to the person who submitted ── */
    $ar = new PHPMailer(true);
    $ar->isSMTP();
    $ar->Host       = SMTP_HOST;
    $ar->SMTPAuth   = true;
    $ar->Username   = SMTP_USER;
    $ar->Password   = SMTP_PASS;
    $ar->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $ar->Port       = SMTP_PORT;
    $ar->CharSet    = 'UTF-8';

    $ar->setFrom(SMTP_USER, TO_NAME);
    $ar->addAddress($email, $fullName);
    $ar->Subject = 'We received your message – ' . TO_NAME;

    $arHtml = <<<HTML
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8">
    <style>
      body{font-family:Arial,sans-serif;background:#0d0d0d;color:#e8e0d0;margin:0;padding:0}
      .wrap{max-width:600px;margin:40px auto;background:#141414;border:1px solid rgba(200,146,42,.3)}
      .hd{background:#C8922A;padding:28px 36px}
      .hd h1{margin:0;font-size:1.3rem;color:#000}
      .bd{padding:36px;line-height:1.8}
      strong{color:#C8922A}
      .ft{background:#0a0a0a;padding:16px 36px;font-size:.7rem;color:#8a8070;border-top:1px solid rgba(200,146,42,.1)}
      a{color:#C8922A}
    </style>
    </head>
    <body>
    <div class="wrap">
      <div class="hd"><h1>Message Received ✓</h1></div>
      <div class="bd">
        <p>Hi FIRSTNAME_PLACEHOLDER,</p>
        <p>Thank you for contacting <strong>TO_NAME_PLACEHOLDER</strong>. We have received your enquiry regarding <strong>SERVICE_PLACEHOLDER</strong> and will respond within <strong>24 business hours</strong>.</p>
        <p>In the meantime, feel free to reach us at <a href="mailto:TO_EMAIL_PLACEHOLDER">TO_EMAIL_PLACEHOLDER</a>.</p>
        <br><p>Best regards,<br><strong>TO_NAME_PLACEHOLDER</strong></p>
      </div>
      <div class="ft">TO_NAME_PLACEHOLDER &bull; Transforming Businesses Through Technology</div>
    </div>
    </body></html>
    HTML;

    $arHtml = str_replace([
        'FIRSTNAME_PLACEHOLDER',
        'TO_NAME_PLACEHOLDER',
        'SERVICE_PLACEHOLDER',
        'TO_EMAIL_PLACEHOLDER',
    ], [
        htmlspecialchars($firstName),
        TO_NAME,
        htmlspecialchars($service),
        TO_EMAIL,
    ], $arHtml);

    $ar->isHTML(true);
    $ar->Body    = $arHtml;
    $ar->AltBody = "Hi {$firstName},\n\nThank you for contacting " . TO_NAME . ". We received your enquiry about '{$service}' and will reply within 24 business hours.\n\nBest regards,\n" . TO_NAME;

    $ar->send();

    /* All good */
    $_SESSION['last_contact_sent'] = time();
    echo json_encode([
        'success' => true,
        'message' => "Your message has been sent! We'll get back to you within 24 hours.",
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Mail error: ' . $mail->ErrorInfo,
    ]);
}
$referer = $_SERVER['HTTP_REFERER'] ?? '';
// Uncomment and set your domain:
// $allowed = 'https://nanobytex.com';
// if ($origin !== $allowed && strpos($referer, $allowed) !== 0) {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'message' => 'Forbidden.']);
//     exit;
// }

/* ── Helper: sanitize string ── */
function clean(string $val): string {
    return htmlspecialchars(trim(strip_tags($val)), ENT_QUOTES, 'UTF-8');
}

/* ── Collect & sanitize input ── */
$firstName = clean($_POST['first_name'] ?? '');
$lastName  = clean($_POST['last_name']  ?? '');
$email     = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone     = clean($_POST['phone']    ?? '');
$service   = clean($_POST['service']  ?? '');
$message   = clean($_POST['message']  ?? '');

/* ── Validate required fields ── */
$errors = [];

if (empty($firstName))                          $errors[] = 'First name is required.';
if (empty($lastName))                           $errors[] = 'Last name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
if (empty($service))                            $errors[] = 'Please select a service.';
if (strlen($message) < 10)                      $errors[] = 'Message must be at least 10 characters.';
if (strlen($message) > MAX_MESSAGE_LEN)         $errors[] = 'Message is too long (max ' . MAX_MESSAGE_LEN . ' characters).';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => implode(' ', $errors),
    ]);
    exit;
}

/* ── Rate-limit: basic session-based throttle ── */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$lastSent = $_SESSION['last_contact_sent'] ?? 0;
if ((time() - $lastSent) < 60) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Please wait a moment before sending another message.',
    ]);
    exit;
}

/* ── Build email ── */
$fullName = $firstName . ' ' . $lastName;
$subject  = "[{$service}] New Enquiry from {$fullName}";

$body  = "You have received a new message via the Nanobyte X website.\n\n";
$body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$body .= "Name:    {$fullName}\n";
$body .= "Email:   {$email}\n";
$body .= "Phone:   " . ($phone ?: '—') . "\n";
$body .= "Service: {$service}\n";
$body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
$body .= "Message:\n{$message}\n\n";
$body .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$body .= "Sent from: " . SITE_NAME . "\n";
$body .= "Date/Time: " . date('Y-m-d H:i:s T') . "\n";

$headers  = "From: {$fullName} <{$email}>\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

/* ── Send mail ── */
$sent = mail(RECIPIENT_EMAIL, $subject, $body, $headers);

/* ── Auto-reply to sender ── */
if ($sent) {
    $autoSubject = "We received your message – " . RECIPIENT_NAME;
    $autoBody    = "Hi {$firstName},\n\n";
    $autoBody   .= "Thank you for reaching out to Nanobyte X LLC.\n";
    $autoBody   .= "We have received your enquiry regarding \"{$service}\" and will respond within 24 business hours.\n\n";
    $autoBody   .= "Best regards,\n" . RECIPIENT_NAME . "\n";
    $autoBody   .= "info@nanobytex.com\n";

    $autoHeaders  = "From: " . RECIPIENT_NAME . " <" . RECIPIENT_EMAIL . ">\r\n";
    $autoHeaders .= "Reply-To: " . RECIPIENT_EMAIL . "\r\n";
    $autoHeaders .= "Content-Type: text/plain; charset=UTF-8\r\n";

    mail($email, $autoSubject, $autoBody, $autoHeaders);

    $_SESSION['last_contact_sent'] = time();

    echo json_encode([
        'success' => true,
        'message' => 'Your message has been sent! We\'ll get back to you within 24 hours.',
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'There was a problem sending your message. Please try again or email us directly.',
    ]);
}
