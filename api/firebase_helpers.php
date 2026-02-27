<?php

require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function send_json(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function read_json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Verify a Firebase ID token and return an array with at least 'uid' and 'email'.
 * Replace the body of this function with your Firebase Admin SDK verification
 * (for example using kreait/firebase-php or the official Admin SDK you have configured).
 */
function verify_firebase_id_token(string $idToken): ?array
{
    // DEVELOPMENT ONLY:
    // This temporarily trusts the client and does NOT actually verify
    // the ID token. It simply returns a fake payload shape so that the
    // registration/login APIs can populate MySQL.
    //
    // Replace this with real Firebase Admin SDK verification in production.

    $payload = json_decode(base64_decode(str_replace('_', '/', str_replace('-', '+', explode('.', $idToken)[1] ?? ''))), true);
    if (!is_array($payload) || empty($payload['sub']) || empty($payload['email'])) {
        return null;
    }

    return [
        'uid'   => $payload['sub'],
        'email' => $payload['email'],
    ];
}

