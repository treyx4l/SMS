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
    // TODO: integrate your real Firebase Admin verification here.
    // This placeholder assumes you already have that configured.
    // Example shape of the expected return:
    // return [
    //     'uid'   => $verifiedToken->claims()->get('sub'),
    //     'email' => $verifiedToken->claims()->get('email'),
    // ];

    return null;
}

