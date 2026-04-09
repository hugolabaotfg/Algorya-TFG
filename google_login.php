<?php
// =============================================================================
// ALGORYA - google_login.php
// Genera el state CSRF y redirige al servidor de Google para autenticación.
// =============================================================================
session_start();

define('GOOGLE_CLIENT_ID',    '793894236164-1mh14kaqdtb9sck59atuk2tglc9knvgt.apps.googleusercontent.com');
define('GOOGLE_REDIRECT_URI', 'https://algorya.store/auth_google.php');

// Generar state aleatorio para protección CSRF
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

// Construir URL de autorización de Google
$params = http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'prompt'        => 'select_account', // Siempre muestra selector de cuenta
]);

header("Location: https://accounts.google.com/o/oauth2/v2/auth?$params");
exit();