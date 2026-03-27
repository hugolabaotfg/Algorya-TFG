<?php
// =============================================================================
// ALGORYA - includes/en.php
// Translation dictionary in ENGLISH.
// Keys must be identical to es.php
// =============================================================================

return [

    // GENERAL
    'marca_nombre' => 'Algorya',
    'marca_tld' => '.store',
    'pie_derechos' => '© %s All rights reserved.',
    'pie_autor' => 'Final Degree Project — ASIR — by Hugo Labao González',

    // NAVBAR
    'nav_carrito' => 'Cart',
    'nav_entrar' => 'Sign in',
    'nav_registrarse' => 'Sign up',
    'nav_cerrar_sesion' => 'Log out',
    'nav_gestion' => 'Management',
    'nav_pedidos' => 'Orders',
    'nav_clientes' => 'Customers',
    'nav_estadisticas' => 'Statistics',
    'nav_enviar_email' => 'Send Email',
    'nav_anadir_producto' => 'Add Product',
    'nav_modo_oscuro' => 'Toggle Dark Mode',

    // LANGUAGE SELECTOR
    'lang_selector_titulo' => 'Language',
    'lang_es' => 'Español',
    'lang_en' => 'English',

    // INDEX
    'hero_titulo' => 'Discover what\'s trending',
    'hero_subtitulo' => 'Our algorithm scans the global market to bring you the top-selling products before anyone else.',
    'catalogo_titulo' => 'Today\'s Catalogue',
    'catalogo_sincronizado' => 'Synced today',
    'catalogo_vacio_titulo' => 'The catalogue is empty',
    'catalogo_vacio_texto' => 'The API will sync overnight.',
    'producto_stock' => 'Stock:',
    'btn_anadir_carro' => 'Add to cart',
    'btn_anadiendo' => 'Adding...',
    'toast_añadido' => 'Successfully added to cart!',
    'paginacion_anterior' => 'Prev',
    'paginacion_siguiente' => 'Next',

    // LOGIN MODAL
    'modal_login_subtitulo' => 'Enter your credentials to continue',
    'modal_login_email' => 'Email address',
    'modal_login_password' => 'Password',
    'modal_login_btn' => 'Sign in',
    'modal_login_sin_cuenta' => 'Don\'t have an account?',
    'modal_login_registro' => 'Register for free',
    'modal_login_error' => 'Wrong credentials or unverified account. Please try again.',
    'modal_login_mostrar' => 'Show/hide password',

    // LOGIN
    'login_titulo' => 'System Access',
    'login_subtitulo' => 'Enter your credentials to continue',
    'login_email' => 'Email Address',
    'login_password' => 'Password',
    'login_btn' => 'Sign in',
    'login_sin_cuenta' => 'Don\'t have an account? Register for free',
    'login_volver' => 'Back to catalogue',

    // REGISTRO
    'registro_titulo' => 'Create an account',
    'registro_subtitulo' => 'Join Algorya and manage your orders',
    'registro_nombre' => 'Full name',
    'registro_email' => 'Email address',
    'registro_pass' => 'Password',
    'registro_pass_hint' => 'At least 8 characters, one uppercase letter and one number.',
    'registro_pass_confirm' => 'Confirm password',
    'registro_pass_repite' => 'Repeat your password',
    'registro_caps_aviso' => 'Caps Lock is on!',
    'registro_pass_ok' => 'Passwords match.',
    'registro_pass_error' => 'Passwords do not match.',
    'registro_btn' => 'Create Account',
    'registro_ya_cuenta' => 'Already have an account? Sign in here',
    'registro_volver' => 'Back to catalogue',

    // CARRITO
    'carrito_titulo' => 'Your Shopping Cart',
    'carrito_col_producto' => 'Product',
    'carrito_col_cantidad' => 'Qty',
    'carrito_col_precio' => 'Price',
    'carrito_total' => 'Total:',
    'carrito_vaciar' => 'Empty cart',
    'carrito_vaciar_confirm' => 'Are you sure you want to empty the entire cart?',
    'carrito_pagar' => 'Proceed to Secure Checkout',
    'carrito_login_pagar' => 'Sign in to checkout',
    'carrito_vacio_titulo' => 'Your cart is empty',
    'carrito_vacio_texto' => 'Go back to the catalogue to discover our exclusive daily deals.',
    'carrito_ir_tienda' => 'Go to the Store',
    'carrito_seguir' => 'Continue Shopping',

    // CHECKOUT
    'checkout_titulo' => 'Confirm your order',
    'checkout_resumen' => 'Order summary',
    'checkout_cantidad' => 'Qty:',
    'checkout_total' => 'Total to pay:',
    'checkout_sandbox_titulo' => 'Sandbox Mode (Testing)',
    'checkout_sandbox_texto' => 'No real charges will be made.',
    'checkout_sandbox_tarj' => 'Use Stripe\'s test card:',
    'checkout_btn_pagar' => 'Pay %s € with Stripe',
    'checkout_seguro' => 'Secure payment processed by Stripe. Algorya never stores your card details.',
    'checkout_volver' => 'Back to cart',

    // PAYMENT SUCCESS
    'success_titulo' => 'Payment complete!',
    'success_subtitulo' => 'Your order %s has been successfully registered.',
    'success_total' => 'Amount paid:',
    'success_sandbox' => 'Sandbox Mode: Simulated payment via Stripe Test. No real charges were made. A receipt has been sent to your email.',
    'success_btn_seguir' => 'Continue shopping',
    'success_btn_pedidos' => 'View my orders',

    // PAYMENT CANCELLED
    'cancel_titulo' => 'Payment cancelled',
    'cancel_texto' => 'Don\'t worry, no charges have been made. Your items are still waiting in your cart.',
    'cancel_btn_carrito' => 'Back to cart',
    'cancel_btn_seguir' => 'Continue shopping',

    // SINGLE PRODUCT
    'producto_descripcion' => 'Description',
    'producto_stock' => 'Stock:',
    'producto_en_stock' => 'In stock',
    'producto_sin_stock' => 'Out of stock',
    'producto_btn_anadir' => 'Add to cart',
    'producto_volver' => 'Back to catalogue',

    // PERFIL
    'perfil_titulo' => 'My Profile',
    'perfil_miembro_desde' => 'Member since',
    'perfil_ajustes' => 'Account Settings',
    'perfil_datos' => 'My Personal Data',
    'perfil_seguridad' => 'Security & Password',
    'perfil_notificaciones' => 'Notifications',
    'perfil_cerrar_sesion' => 'Log Out',
    'perfil_historial' => 'My Order History',
    'perfil_col_pedido' => 'Order',
    'perfil_col_fecha' => 'Date',
    'perfil_col_metodo' => 'Method',
    'perfil_col_total' => 'Total',
    'perfil_col_estado' => 'Status',
    'perfil_estado_procesado' => 'Processed',
    'perfil_sin_compras' => 'You haven\'t placed any orders yet',
    'perfil_sin_compras_texto' => 'When you make your first order, it will appear here.',
    'perfil_ir_compras' => 'Go shopping',
    'perfil_volver' => 'Back to store',

    // AJUSTES
    'ajustes_titulo' => 'Account Settings',
    'ajustes_volver' => 'Back to Profile',
    'ajustes_tab_datos' => 'Personal Data',
    'ajustes_tab_seguridad' => 'Security',
    'ajustes_tab_notif' => 'Notifications',
    'ajustes_datos_desc' => 'Update your profile name. The email address cannot be changed.',
    'ajustes_nombre' => 'Full name',
    'ajustes_email' => 'Email address',
    'ajustes_email_readonly' => '(cannot be changed)',
    'ajustes_guardar' => 'Save Changes',
    'ajustes_seg_desc' => 'To change your password you must first confirm your current password.',
    'ajustes_pass_actual' => 'Current password',
    'ajustes_pass_nueva' => 'New password',
    'ajustes_pass_nueva_hint' => 'At least 8 characters',
    'ajustes_pass_confirmar' => 'Confirm new password',
    'ajustes_pass_repite' => 'Repeat new password',
    'ajustes_actualizar_pass' => 'Update Password',
    'ajustes_2fa_titulo' => 'Two-Factor Authentication (2FA)',
    'ajustes_2fa_texto' => 'Add an extra layer of security to your account.',
    'ajustes_2fa_btn' => 'Coming soon',
    'ajustes_notif_desc' => 'Choose which emails you want to receive from Algorya.',
    'ajustes_notif_promos' => 'Promotional emails',
    'ajustes_notif_promos_text' => 'Receive alerts about new trending products and special offers.',
    'ajustes_notif_pedidos' => 'Order updates',
    'ajustes_notif_pedidos_text' => 'We\'ll notify you when your order status changes or is shipped.',
    'ajustes_guardar_notif' => 'Save Preferences',
    'ajustes_pass_coinciden' => 'Passwords match.',
    'ajustes_pass_no_coinciden' => 'Passwords do not match.',

    // GENERIC MESSAGES
    'error_generico' => 'An error occurred. Please try again.',
    'exito_generico' => 'Operation completed successfully.',
];