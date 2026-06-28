<?php
/**
 * Dijalankan saat plugin dihapus dari WordPress. Membersihkan opsi pengaturan.
 *
 * @package BayarGG_WooCommerce
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('woocommerce_bayargg_settings');
