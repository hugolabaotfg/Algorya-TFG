<?php
// =============================================================================
// ALGORYA - includes/lang.php
// Motor central del sistema de internacionalización (i18n).
//
// ¿Cómo funciona?
//   1. Comprueba si el usuario acaba de cambiar de idioma (?lang=es o ?lang=en)
//   2. Si es así, guarda la preferencia en una cookie durante 1 año
//   3. Si no, lee la cookie para saber qué idioma prefiere el usuario
//   4. Si no hay cookie, usa español por defecto
//   5. Carga el archivo de traducciones correspondiente (es.php o en.php)
//   6. Define la función t('clave') que devuelve la frase traducida
//
// USO en cualquier archivo PHP:
//   require 'includes/lang.php';          ← Cargar el motor (una sola vez al inicio)
//   echo t('btn_anadir_carro');            ← Devuelve "Añadir al carro" o "Add to cart"
//   echo t('saludo', ['Hugo']);            ← Con parámetros: "Hola, Hugo" / "Hello, Hugo"
// =============================================================================

// Idiomas disponibles en la aplicación
$idiomas_disponibles = ['es', 'en'];
$idioma_por_defecto = 'es';

// ─────────────────────────────────────────────────────────────────────────────
// PASO 1: ¿El usuario acaba de cambiar de idioma desde el selector del navbar?
// El selector envía ?lang=es o ?lang=en en la URL
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_GET['lang']) && in_array($_GET['lang'], $idiomas_disponibles)) {
    $idioma_elegido = $_GET['lang'];

    // Guardar en cookie: dura 1 año, aplica a toda la web (path '/'), sin JS (httponly)
    setcookie('algorya_lang', $idioma_elegido, [
        'expires' => time() + (365 * 24 * 60 * 60), // 1 año en segundos
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    // Actualizar también $_COOKIE para que esta misma petición ya use el nuevo idioma
    $_COOKIE['algorya_lang'] = $idioma_elegido;
}

// ─────────────────────────────────────────────────────────────────────────────
// PASO 2: Determinar el idioma activo
// Prioridad: cookie guardada > español por defecto
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_COOKIE['algorya_lang']) && in_array($_COOKIE['algorya_lang'], $idiomas_disponibles)) {
    $idioma_activo = $_COOKIE['algorya_lang'];
} else {
    $idioma_activo = $idioma_por_defecto;
}

// Variable global accesible desde cualquier archivo que haga require de lang.php
define('LANG', $idioma_activo);

// ─────────────────────────────────────────────────────────────────────────────
// PASO 3: Cargar el archivo de traducciones del idioma activo
// El archivo devuelve un array asociativo: ['clave' => 'frase traducida']
// ─────────────────────────────────────────────────────────────────────────────
$traducciones = require __DIR__ . '/' . LANG . '.php';

// ─────────────────────────────────────────────────────────────────────────────
// FUNCIÓN t() — La función principal del sistema i18n
//
// Parámetros:
//   $clave    → El identificador de la frase (ej: 'btn_anadir_carro')
//   $params   → Array opcional de valores para sustituir %s en la frase
//               (ej: t('saludo', [$nombre]) → "Hola, Hugo")
//
// Si la clave no existe en el archivo de traducciones, devuelve la clave
// misma entre corchetes para que sea fácil detectarla durante el desarrollo.
// ─────────────────────────────────────────────────────────────────────────────
function t(string $clave, array $params = []): string
{
    global $traducciones;

    $texto = $traducciones[$clave] ?? "[{$clave}]"; // Fallback visible si falta la clave

    // Si hay parámetros, sustituimos los %s en orden
    if (!empty($params)) {
        $texto = vsprintf($texto, $params);
    }

    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

// Versión sin htmlspecialchars para usar dentro de atributos HTML ya escapados
// o cuando necesitas el HTML crudo (usar con cuidado)
function t_raw(string $clave, array $params = []): string
{
    global $traducciones;
    $texto = $traducciones[$clave] ?? "[{$clave}]";
    if (!empty($params)) {
        $texto = vsprintf($texto, $params);
    }
    return $texto;
}