<?php
session_start();
require 'includes/lang.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="<?= LANG ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Página no encontrada | Algorya</title>
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="estilos.css">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <script src="tema.js"></script>
    <style>
        .error-number {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(6rem, 20vw, 12rem);
            font-weight: 900;
            letter-spacing: -.05em;
            line-height: 1;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            user-select: none;
        }
        .floating {
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-12px); }
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<!-- Navbar mínimo -->
<nav class="navbar sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand text-decoration-none d-flex align-items-center gap-2" href="index.php">
            <div style="width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 1L13 14H10.5L9.5 11H6.5L5.5 14H3L8 1ZM7.2 9H8.8L8 6.5Z" fill="white"/></svg>
            </div>
            <div class="d-flex align-items-baseline gap-1">
                <span class="fw-black text-primary" style="font-family:'Outfit',sans-serif;font-size:1.2rem;letter-spacing:-.04em;">Algorya</span><span class="premium-muted fw-semibold" style="font-size:.75rem;">.store</span>
            </div>
        </a>
        <div id="darkModeToggle" style="cursor:pointer;">
            <i class="bi bi-moon-stars-fill fs-6"></i>
        </div>
    </div>
</nav>

<!-- Contenido 404 -->
<div class="flex-grow-1 d-flex align-items-center">
    <div class="container text-center py-5">

        <div class="floating mb-2">
            <div class="error-number">404</div>
        </div>

        <h1 class="fw-black premium-text mb-3" style="font-family:'Outfit',sans-serif;font-size:clamp(1.5rem,4vw,2.2rem);letter-spacing:-.03em;">
            <?= LANG === 'es' ? 'Esta página no existe' : 'This page does not exist' ?>
        </h1>

        <p class="premium-muted mx-auto mb-5" style="max-width:440px;font-size:1rem;line-height:1.6;">
            <?= LANG === 'es'
                ? 'Puede que el enlace esté roto, el producto haya sido eliminado o hayas escrito mal la URL.'
                : 'The link may be broken, the product may have been removed, or you may have mistyped the URL.' ?>
        </p>

        <!-- Acciones -->
        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center align-items-center mb-5">
            <a href="index.php" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow-sm" style="font-family:'Outfit',sans-serif;">
                <i class="bi bi-house-fill me-2"></i>
                <?= LANG === 'es' ? 'Volver al catálogo' : 'Back to catalogue' ?>
            </a>
            <button onclick="history.back()" class="btn btn-outline-secondary btn-lg rounded-pill px-5 fw-semibold">
                <i class="bi bi-arrow-left me-2"></i>
                <?= LANG === 'es' ? 'Página anterior' : 'Previous page' ?>
            </button>
        </div>

        <!-- Sugerencia de búsqueda -->
        <div class="premium-muted mb-4" style="font-size:.9rem;">
            <?= LANG === 'es' ? '¿Buscabas algo en concreto?' : 'Looking for something specific?' ?>
        </div>
        <form action="index.php" method="GET" class="d-flex justify-content-center">
            <div class="input-group" style="max-width:400px;">
                <input type="text" name="nombre" class="form-control form-control-lg premium-input shadow-none rounded-start-pill"
                       placeholder="<?= LANG === 'es' ? 'Buscar productos...' : 'Search products...' ?>">
                <button type="submit" class="btn btn-primary rounded-end-pill px-4 fw-bold">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>

    </div>
</div>

<footer class="text-center py-4" style="border-top:1px solid var(--border-color);">
    <p class="mb-0 premium-muted small fw-bold">
        <i class="bi bi-box-seam-fill text-primary"></i> Algorya &copy; <?= date('Y') ?>
    </p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>