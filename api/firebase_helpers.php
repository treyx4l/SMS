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

/**
 * Update a Firebase Auth user's email. Requires FIREBASE_SERVICE_ACCOUNT_PATH.
 * Returns true on success, false on failure (or if not configured).
 */
function update_firebase_user_email(string $firebaseUid, string $newEmail): bool
{
    $projectId = getenv('FIREBASE_PROJECT_ID') ?: '';
    $saPath   = getenv('FIREBASE_SERVICE_ACCOUNT_PATH') ?: '';
    $apiKey   = getenv('FIREBASE_API_KEY') ?: '';

    if (!$projectId || !$saPath || !file_exists($saPath)) {
        return false;
    }

    $sa = json_decode(file_get_contents($saPath), true);
    if (!is_array($sa) || empty($sa['private_key']) || empty($sa['client_email'])) {
        return false;
    }
    // Ensure private key has proper newlines (JSON may store as \n)
    $sa['private_key'] = str_replace('\\n', "\n", $sa['private_key']);

    $accessToken = get_firebase_access_token($sa);
    if (!$accessToken) {
        return false;
    }

    $url = 'https://identitytoolkit.googleapis.com/v1/accounts:update';
    if ($apiKey !== '') {
        $url .= '?key=' . urlencode($apiKey);
    }

    $body = json_encode([
        'localId'         => $firebaseUid,
        'email'           => $newEmail,
        'emailVerified'   => true,
        'targetProjectId'  => $projectId,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ],
    ]);

    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $code >= 200 && $code < 300;
}

/**
 * Get OAuth2 access token from service account for Firebase/Identity Toolkit API.
 */
function get_firebase_access_token(array $serviceAccount): ?string
{
    $now = time();
    $payload = [
        'iss' => $serviceAccount['client_email'],
        'sub' => $serviceAccount['client_email'],
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
        'scope' => 'https://www.googleapis.com/auth/identitytoolkit https://www.googleapis.com/auth/cloud-platform',
    ];

    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $segments = [
        base64url_encode(json_encode($header)),
        base64url_encode(json_encode($payload)),
    ];

    $signature = '';
    $privKey = openssl_pkey_get_private($serviceAccount['private_key']);
    if (!$privKey) {
        return null;
    }
    openssl_sign(implode('.', $segments), $signature, $privKey, OPENSSL_ALGO_SHA256);
    openssl_free_key($privKey);
    $segments[] = base64url_encode($signature);

    $jwt = implode('.', $segments);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST       => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    ]);

    $resp = curl_exec($ch);
    curl_close($ch);

    if ($resp === false) {
        return null;
    }

    $data = json_decode($resp, true);
    return $data['access_token'] ?? null;
}

function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

