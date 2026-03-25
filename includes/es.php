<?php
// =============================================================================
// ALGORYA - includes/es.php
// Diccionario de traducciones en ESPAÑOL.
//
// Formato: 'clave_unica' => 'Frase en español'
// Las claves son identificadores internos — nunca se muestran al usuario.
// Usa %s como marcador de posición para valores dinámicos.
// =============================================================================

return [

    // ── GENERAL / MARCA ───────────────────────────────────────────────────────
    'marca_nombre' => 'Algorya',
    'marca_tld' => '.store',
    'pie_derechos' => '© %s Todos los derechos reservados.',
    'pie_autor' => 'Proyecto Final de Grado de ASIR realizado por Hugo Labao González',

    // ── NAVBAR ────────────────────────────────────────────────────────────────
    'nav_carrito' => 'Carrito',
    'nav_entrar' => 'Entrar',
    'nav_registrarse' => 'Registrarse',
    'nav_cerrar_sesion' => 'Cerrar sesión',
    'nav_gestion' => 'Gestión',
    'nav_pedidos' => 'Pedidos',
    'nav_clientes' => 'Clientes',
    'nav_estadisticas' => 'Estadísticas',
    'nav_enviar_email' => 'Enviar Email',
    'nav_anadir_producto' => 'Añadir Producto',
    'nav_modo_oscuro' => 'Alternar Modo Oscuro',

    // ── SELECTOR DE IDIOMA ────────────────────────────────────────────────────
    'lang_selector_titulo' => 'Idioma',
    'lang_es' => 'Español',
    'lang_en' => 'English',

    // ── PÁGINA PRINCIPAL (index.php) ──────────────────────────────────────────
    'hero_titulo' => 'Descubre lo más viral',
    'hero_subtitulo' => 'Nuestro algoritmo rastrea el mercado global para traerte los productos top ventas antes que nadie.',
    'catalogo_titulo' => 'Catálogo del día',
    'catalogo_sincronizado' => 'Sincronizado hoy',
    'catalogo_vacio_titulo' => 'El catálogo está vacío',
    'catalogo_vacio_texto' => 'La API se sincronizará esta madrugada.',
    'producto_stock' => 'Stock:',
    'btn_anadir_carro' => 'Añadir al carro',
    'btn_anadiendo' => 'Añadiendo...',
    'toast_añadido' => '¡Añadido al carrito con éxito!',
    'paginacion_anterior' => 'Ant',
    'paginacion_siguiente' => 'Sig',

    // ── MODAL DE LOGIN (en index.php) ─────────────────────────────────────────
    'modal_login_subtitulo' => 'Introduce tus credenciales para continuar',
    'modal_login_bienvenido' => 'Bienvenido de nuevo',
    'modal_login_email' => 'Correo electrónico',
    'modal_login_password' => 'Contraseña',
    'modal_login_btn' => 'Iniciar Sesión',
    'modal_login_sin_cuenta' => '¿No tienes cuenta?',
    'modal_login_registro' => 'Regístrate gratis',
    'modal_login_error' => 'Credenciales incorrectas o cuenta no verificada. Inténtalo de nuevo.',
    'modal_login_mostrar' => 'Mostrar/ocultar contraseña',

    // ── LOGIN (login.php) ─────────────────────────────────────────────────────
    'login_titulo' => 'Acceso al Sistema',
    'login_subtitulo' => 'Introduce tus credenciales para continuar',
    'login_email' => 'Correo Electrónico',
    'login_password' => 'Contraseña',
    'login_btn' => 'Entrar',
    'login_sin_cuenta' => '¿No tienes cuenta? Regístrate gratis',
    'login_volver' => 'Volver al catálogo',

    // ── REGISTRO (registro.php) ───────────────────────────────────────────────
    'registro_titulo' => 'Crear una cuenta',
    'registro_subtitulo' => 'Únete a Algorya y gestiona tus pedidos',
    'registro_nombre' => 'Nombre completo',
    'registro_email' => 'Correo electrónico',
    'registro_pass' => 'Contraseña',
    'registro_pass_hint' => 'Mínimo 8 caracteres, una mayúscula y un número.',
    'registro_pass_confirm' => 'Confirmar contraseña',
    'registro_pass_repite' => 'Repite la contraseña',
    'registro_caps_aviso' => '¡Bloq Mayús activado!',
    'registro_pass_ok' => 'Las contraseñas coinciden.',
    'registro_pass_error' => 'Las contraseñas no coinciden.',
    'registro_btn' => 'Confirmar Registro',
    'registro_ya_cuenta' => '¿Ya tienes cuenta? Inicia sesión aquí',
    'registro_volver' => 'Volver al catálogo',

    // ── CARRITO (carrito.php) ─────────────────────────────────────────────────
    'carrito_titulo' => 'Tu Carrito de la Compra',
    'carrito_col_producto' => 'Producto',
    'carrito_col_cantidad' => 'Cantidad',
    'carrito_col_precio' => 'Precio',
    'carrito_total' => 'Total:',
    'carrito_vaciar' => 'Vaciar',
    'carrito_vaciar_confirm' => '¿Seguro que quieres vaciar todo el carrito?',
    'carrito_pagar' => 'Procesar Pago Seguro',
    'carrito_login_pagar' => 'Inicia sesión para pagar',
    'carrito_vacio_titulo' => 'Tu carrito está vacío',
    'carrito_vacio_texto' => 'Vuelve al catálogo para descubrir nuestras ofertas exclusivas del día.',
    'carrito_ir_tienda' => 'Ir a la Tienda',
    'carrito_seguir' => 'Seguir Comprando',

    // ── CHECKOUT (checkout.php) ───────────────────────────────────────────────
    'checkout_titulo' => 'Confirmar tu pedido',
    'checkout_resumen' => 'Resumen de productos',
    'checkout_cantidad' => 'Cantidad:',
    'checkout_total' => 'Total a pagar:',
    'checkout_sandbox_titulo' => 'Modo Sandbox (Pruebas)',
    'checkout_sandbox_texto' => 'No se realizarán cargos reales.',
    'checkout_sandbox_tarj' => 'Usa la tarjeta de prueba de Stripe:',
    'checkout_btn_pagar' => 'Pagar %s € con Stripe',
    'checkout_seguro' => 'Pago seguro procesado por Stripe. Algorya nunca almacena datos de tu tarjeta.',
    'checkout_volver' => 'Volver al carrito',

    // ── ÉXITO DEL PAGO (checkout_success.php) ────────────────────────────────
    'success_titulo' => '¡Pago completado!',
    'success_subtitulo' => 'Tu pedido %s ha sido registrado correctamente.',
    'success_total' => 'Total pagado:',
    'success_sandbox' => 'Modo Sandbox: Pago simulado con Stripe Test. No se han realizado cargos reales. Te hemos enviado un recibo a tu correo.',
    'success_btn_seguir' => 'Seguir comprando',
    'success_btn_pedidos' => 'Ver mis pedidos',

    // ── CANCELACIÓN DEL PAGO (checkout_cancel.php) ───────────────────────────
    'cancel_titulo' => 'Pago cancelado',
    'cancel_texto' => 'No te preocupes, no se ha realizado ningún cargo. Tus productos siguen en el carrito esperándote.',
    'cancel_btn_carrito' => 'Volver al carrito',
    'cancel_btn_seguir' => 'Seguir comprando',

    // ── PRODUCTO INDIVIDUAL (producto.php) ───────────────────────────────────
    'producto_descripcion' => 'Descripción',
    'producto_precio' => 'Precio',
    'producto_disponibilidad' => 'Disponibilidad',
    'producto_en_stock' => 'En stock',
    'producto_sin_stock' => 'Sin stock',
    'producto_btn_anadir' => 'Añadir al carrito',
    'producto_volver' => 'Volver al catálogo',

    // ── ADMIN (común) ─────────────────────────────────────────────────────────
    'admin_titulo_pedidos' => 'Historial de Pedidos',
    'admin_titulo_usuarios' => 'Gestión de Clientes',
    'admin_titulo_stats' => 'Panel de Estadísticas',
    'admin_volver' => 'Volver al catálogo',

    // ── MENSAJES DE ERROR / ÉXITO GENÉRICOS ───────────────────────────────────
    'error_generico' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
    'exito_generico' => 'Operación completada con éxito.',

];