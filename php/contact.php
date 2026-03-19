<?php
/**
 * NANOBYTE X LLC — Contact Form Handler
 * php/contact.php
 *
 * Receives POST data, validates, and sends an email.
 * Returns JSON so the JS can handle success/error gracefully.
 *
 * ─── SETUP ───────────────────────────────────────────────
 * 1. Set RECIPIENT_EMAIL to your real address.
 * 2. For production, consider using PHPMailer + SMTP
 *    instead of PHP's built-in mail() for better delivery.
 * 3. Ensure your server has PHP mail() configured, OR
 *    replace the mail() call with your SMTP/API approach.
 * ─────────────────────────────────────────────────────────
 */

declare(strict_types=1);

/* ── CONFIG ── */
define('RECIPIENT_EMAIL', 'nanobytexllc@gmail.com');   // ← change this
define('RECIPIENT_NAME',  'Nanobyte X LLC');
define('SITE_NAME',       'Nanobyte X LLC Website');
define('MAX_MESSAGE_LEN', 3000);

/* ── Headers: only accept POST, return JSON ── */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

/* ── CSRF / Origin check (basic) ── */
$origin  = $_SERVER['HTTP_ORIGIN']  ?? '';
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
