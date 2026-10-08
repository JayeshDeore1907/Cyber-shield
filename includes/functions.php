<?php
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function base_url(string $path=''): string { return $path === '' ? 'index.php' : 'index.php?page=' . urlencode($path); }
function csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(419); exit('Invalid request token.');
    }
}
function analyze_scam(string $message): array {
    $rules = [
        ['otp', 'Requests for OTP/PIN', 25],
        ['one time password', 'Requests for one-time password', 25],
        ['urgent', 'Urgency / pressure language', 15],
        ['immediately', 'Urgency / pressure language', 12],
        ['click', 'Suspicious link/click request', 12],
        ['upi', 'UPI/payment reference', 8],
        ['refund', 'Refund lure', 10],
        ['kyc', 'KYC verification lure', 12],
        ['verify your account', 'Account verification lure', 12],
        ['job offer', 'Potential job scam indicator', 12],
        ['investment', 'Potential investment scam indicator', 12],
        ['crypto', 'Potential crypto/investment lure', 10],
        ['prize', 'Prize/reward lure', 10],
        ['lottery', 'Lottery lure', 10],
        ['password', 'Credential request', 18],
        ['cvv', 'Card credential request', 20],
    ];
    $lower = strtolower($message); $score = 0; $matches = [];
    foreach ($rules as [$needle,$reason,$points]) {
        if (strpos($lower, $needle) !== false) { $score += $points; $matches[$reason] = true; }
    }
    $score = min(100, $score);
    $level = $score >= 55 ? 'HIGH' : ($score >= 30 ? 'MEDIUM' : 'LOW');
    $advice = $level === 'HIGH'
        ? 'Do not click links, send money, or share OTP/PIN/CVV/passwords. Verify the sender independently.'
        : ($level === 'MEDIUM' ? 'Treat the message cautiously and verify the claim through an official channel.' : 'No strong scam markers were detected by the rule-based checker; still verify unfamiliar requests.');
    return ['score'=>$score,'level'=>$level,'matches'=>array_keys($matches),'advice'=>$advice];
}
function load_xml(string $file): SimpleXMLElement {
    libxml_use_internal_errors(true);
    $xml = simplexml_load_file($file);
    if ($xml === false) throw new RuntimeException('Could not load XML data.');
    return $xml;
}
