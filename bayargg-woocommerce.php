<?php
/**
 * Plugin Name:       BAYAR GG for WooCommerce
 * Plugin URI:        https://github.com/bayar-global-gateway/bayargg-woocommerce
 * Description:       Terima pembayaran QRIS & e-wallet (QRIS BAYAR GG, BRI/GoPay/Livin Merchant QRIS, OVO) di WooCommerce lewat BAYAR GG. Satu QRIS bisa dipindai semua e-wallet & mobile banking, status order otomatis lunas via webhook.
 * Version:           1.0.0
 * Author:            BAYAR GG
 * Author URI:        https://www.bayar.gg
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bayargg-woocommerce
 * Requires at least: 5.6
 * Requires PHP:      7.4
 * WC requires at least: 5.0
 * WC tested up to:   9.5
 *
 * @package BayarGG_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit; // No direct access.
}

define('BAYARGG_WC_VERSION', '1.0.0');
define('BAYARGG_WC_FILE', __FILE__);
define('BAYARGG_WC_PATH', plugin_dir_path(__FILE__));
define('BAYARGG_WC_URL', plugin_dir_url(__FILE__));

/**
 * Kompatibilitas WooCommerce High-Performance Order Storage (HPOS).
 */
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

/**
 * Inisialisasi plugin setelah semua plugin dimuat (memastikan WooCommerce ada).
 */
add_action('plugins_loaded', function () {
    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>BAYAR GG for WooCommerce</strong> memerlukan plugin <strong>WooCommerce</strong> aktif untuk berfungsi.</p></div>';
        });
        return;
    }

    require_once BAYARGG_WC_PATH . 'includes/class-bayargg-api.php';
    require_once BAYARGG_WC_PATH . 'includes/class-wc-bayargg-gateway.php';

    add_filter('woocommerce_payment_gateways', function ($gateways) {
        $gateways[] = 'WC_BayarGG_Gateway';
        return $gateways;
    });
}, 11);

/**
 * Tautan "Pengaturan" di halaman daftar plugin.
 */
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=bayargg');
    array_unshift($links, '<a href="' . esc_url($settings_url) . '">' . esc_html__('Pengaturan', 'bayargg-woocommerce') . '</a>');
    return $links;
});
