<?php
// =============================================================================
// ALGORYA - includes/es.php
// Diccionario de traducciones en ESPANOL.
// =============================================================================

return [

    // GENERAL
    'marca_nombre' => 'Algorya',
    'marca_tld' => '.store',
    'pie_derechos' => '© %s Todos los derechos reservados.',
    'pie_autor' => 'Proyecto Final de Grado de ASIR realizado por Hugo Labao González',

    // NAVBAR
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

    // SELECTOR DE IDIOMA
    'lang_selector_titulo' => 'Idioma',
    'lang_es' => 'Español',
    'lang_en' => 'English',

    // INDEX
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

    // MODAL LOGIN
    'modal_login_subtitulo' => 'Introduce tus credenciales para continuar',
    'modal_login_email' => 'Correo electrónico',
    'modal_login_password' => 'Contraseña',
    'modal_login_btn' => 'Iniciar Sesión',
    'modal_login_sin_cuenta' => '¿No tienes cuenta?',
    'modal_login_registro' => 'Regístrate gratis',
    'modal_login_error' => 'Credenciales incorrectas o cuenta no verificada. Inténtalo de nuevo.',
    'modal_login_mostrar' => 'Mostrar/ocultar contraseña',

    // LOGIN
    'login_titulo' => 'Acceso al Sistema',
    'login_subtitulo' => 'Introduce tus credenciales para continuar',
    'login_email' => 'Correo Electrónico',
    'login_password' => 'Contraseña',
    'login_btn' => 'Entrar',
    'login_sin_cuenta' => '¿No tienes cuenta? Regístrate gratis',
    'login_volver' => 'Volver al catálogo',

    // REGISTRO
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

    // CARRITO
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

    // CHECKOUT
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

    // PAGO COMPLETADO
    'success_titulo' => '¡Pago completado!',
    'success_subtitulo' => 'Tu pedido %s ha sido registrado correctamente.',
    'success_total' => 'Total pagado:',
    'success_sandbox' => 'Modo Sandbox: Pago simulado con Stripe Test. No se han realizado cargos reales. Te hemos enviado un recibo a tu correo.',
    'success_btn_seguir' => 'Seguir comprando',
    'success_btn_pedidos' => 'Ver mis pedidos',

    // PAGO CANCELADO
    'cancel_titulo' => 'Pago cancelado',
    'cancel_texto' => 'No te preocupes, no se ha realizado ningún cargo. Tus productos siguen en el carrito esperándote.',
    'cancel_btn_carrito' => 'Volver al carrito',
    'cancel_btn_seguir' => 'Seguir comprando',

    // PRODUCTO INDIVIDUAL
    'producto_descripcion' => 'Descripción',
    'producto_stock' => 'Stock:',
    'producto_en_stock' => 'En stock',
    'producto_sin_stock' => 'Sin stock',
    'producto_btn_anadir' => 'Añadir al carrito',
    'producto_volver' => 'Volver al catálogo',

    // PERFIL
    'perfil_titulo' => 'Mi Perfil',
    'perfil_miembro_desde' => 'Miembro desde',
    'perfil_ajustes' => 'Ajustes de Cuenta',
    'perfil_datos' => 'Mis Datos Personales',
    'perfil_seguridad' => 'Seguridad y Contraseña',
    'perfil_notificaciones' => 'Notificaciones',
    'perfil_cerrar_sesion' => 'Cerrar Sesión',
    'perfil_historial' => 'Mi Historial de Pedidos',
    'perfil_col_pedido' => 'Pedido',
    'perfil_col_fecha' => 'Fecha',
    'perfil_col_metodo' => 'Método',
    'perfil_col_total' => 'Total',
    'perfil_col_estado' => 'Estado',
    'perfil_estado_procesado' => 'Procesado',
    'perfil_sin_compras' => 'Aún no has realizado ninguna compra',
    'perfil_sin_compras_texto' => 'Cuando hagas tu primer pedido, aparecerá aquí.',
    'perfil_ir_compras' => 'Ir de compras',
    'perfil_volver' => 'Volver a la tienda',

    // AJUSTES
    'ajustes_titulo' => 'Ajustes de la Cuenta',
    'ajustes_volver' => 'Volver al Perfil',
    'ajustes_tab_datos' => 'Datos Personales',
    'ajustes_tab_seguridad' => 'Seguridad',
    'ajustes_tab_notif' => 'Notificaciones',
    'ajustes_datos_desc' => 'Actualiza tu nombre de perfil. El correo electrónico no puede modificarse.',
    'ajustes_nombre' => 'Nombre completo',
    'ajustes_email' => 'Correo electrónico',
    'ajustes_email_readonly' => '(no modificable)',
    'ajustes_guardar' => 'Guardar Cambios',
    'ajustes_seg_desc' => 'Para cambiar tu contraseña debes confirmar primero la contraseña actual.',
    'ajustes_pass_actual' => 'Contraseña actual',
    'ajustes_pass_nueva' => 'Nueva contraseña',
    'ajustes_pass_nueva_hint' => 'Mínimo 8 caracteres',
    'ajustes_pass_confirmar' => 'Confirmar nueva contraseña',
    'ajustes_pass_repite' => 'Repite la nueva contraseña',
    'ajustes_actualizar_pass' => 'Actualizar Contraseña',
    'ajustes_2fa_titulo' => 'Verificación en 2 Pasos (2FA)',
    'ajustes_2fa_texto' => 'Añade una capa extra de seguridad a tu cuenta.',
    'ajustes_2fa_btn' => 'Próximamente',
    'ajustes_notif_desc' => 'Elige qué correos quieres recibir de Algorya.',
    'ajustes_notif_promos' => 'Correos promocionales',
    'ajustes_notif_promos_text' => 'Recibe avisos sobre nuevos productos en tendencia y ofertas especiales.',
    'ajustes_notif_pedidos' => 'Actualizaciones de pedido',
    'ajustes_notif_pedidos_text' => 'Te avisaremos cuando tu pedido cambie de estado o sea enviado.',
    'ajustes_guardar_notif' => 'Guardar Preferencias',
    'ajustes_pass_coinciden' => 'Las contraseñas coinciden.',
    'ajustes_pass_no_coinciden' => 'Las contraseñas no coinciden.',

    // MENSAJES GENERICOS
    'error_generico' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.',
    'exito_generico' => 'Operación completada con éxito.',
];