<?php
// =============================================================================
// ALGORYA - includes/seo.php
// Meta tags SEO, Open Graph y favicon centralizados.
// Uso: require 'includes/seo.php'; dentro del <head> de cada página.
//
// Variables opcionales a definir ANTES de hacer el require:
//   $seo_titulo      — Título de la página (sin "| Algorya")
//   $seo_descripcion — Descripción para Google y redes sociales
//   $seo_imagen      — URL absoluta de la imagen (Open Graph)
//   $seo_tipo        — "website" (default) o "product"
// =============================================================================

$seo_titulo      = isset($seo_titulo)      ? $seo_titulo      : 'Algorya — Descubre lo más viral';
$seo_descripcion = isset($seo_descripcion) ? $seo_descripcion : 'Algorya rastrea el mercado global para traerte los productos top ventas antes que nadie. Compra tendencias al mejor precio.';
$seo_imagen      = isset($seo_imagen)      ? $seo_imagen      : 'https://algorya.store/img/og-default.png';
$seo_tipo        = isset($seo_tipo)        ? $seo_tipo        : 'website';
$seo_url         = 'https://algorya.store' . ($_SERVER['REQUEST_URI'] ?? '/');
?>
    <!-- SEO básico -->
    <meta name="description" content="<?= htmlspecialchars($seo_descripcion) ?>">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Algorya">
    <link rel="canonical" href="<?= htmlspecialchars($seo_url) ?>">

    <!-- Open Graph (WhatsApp, Facebook, LinkedIn) -->
    <meta property="og:type"        content="<?= $seo_tipo ?>">
    <meta property="og:title"       content="<?= htmlspecialchars($seo_titulo) ?> | Algorya">
    <meta property="og:description" content="<?= htmlspecialchars($seo_descripcion) ?>">
    <meta property="og:image"       content="<?= htmlspecialchars($seo_imagen) ?>">
    <meta property="og:url"         content="<?= htmlspecialchars($seo_url) ?>">
    <meta property="og:site_name"   content="Algorya">
    <meta property="og:locale"      content="<?= LANG === 'es' ? 'es_ES' : 'en_GB' ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= htmlspecialchars($seo_titulo) ?> | Algorya">
    <meta name="twitter:description" content="<?= htmlspecialchars($seo_descripcion) ?>">
    <meta name="twitter:image"       content="<?= htmlspecialchars($seo_imagen) ?>">

    <!-- Favicon -->
    <link rel="icon"             type="image/x-icon"  href="/favicon.ico">
    <link rel="icon"             type="image/svg+xml" href="/favicon.svg">
    <link rel="icon"             type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180"      href="/apple-touch-icon.png">
    <link rel="manifest"                               href="/site.webmanifest">
    <meta name="theme-color" content="#3b82f6">