<?php
/**
 * Integrasi BAYAR GG dengan WooCommerce Blocks (block checkout).
 *
 * Membuat gateway klasik tampil di checkout berbasis blok (Cart/Checkout Blocks).
 *
 * @package BayarGG_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_BayarGG_Blocks_Support extends AbstractPaymentMethodType {

    /** @var string */
    protected $name = 'bayargg';

    /** @var array */
    private $gateway_settings = [];

    public function initialize() {
        $this->settings = get_option('woocommerce_bayargg_settings', []);
        $this->gateway_settings = is_array($this->settings) ? $this->settings : [];
    }

    /**
     * Apakah metode aktif (mengikuti pengaturan gateway).
     *
     * @return bool
     */
    public function is_active() {
        return !empty($this->gateway_settings['enabled']) && 'yes' === $this->gateway_settings['enabled'];
    }

    /**
     * Daftarkan & kembalikan handle script untuk block checkout.
     *
     * @return string[]
     */
    public function get_payment_method_script_handles() {
        $handle = 'bayargg-blocks';
        $src    = BAYARGG_WC_URL . 'assets/js/blocks.js';

        wp_register_script(
            $handle,
            $src,
            ['wc-blocks-registry', 'wp-element', 'wp-html-entities'],
            defined('BAYARGG_WC_VERSION') ? BAYARGG_WC_VERSION : '1.0.0',
            true
        );

        return [$handle];
    }

    /**
     * Data yang dikirim ke script block checkout.
     *
     * @return array
     */
    public function get_payment_method_data() {
        return [
            'title'       => $this->gateway_settings['title'] ?? 'QRIS / E-Wallet (BAYAR GG)',
            'description' => $this->gateway_settings['description'] ?? 'Bayar pakai QRIS — bisa dipindai semua e-wallet & mobile banking.',
            'icon'        => apply_filters('bayargg_wc_icon', BAYARGG_WC_URL . 'assets/bayargg-logo.png'),
            'supports'    => ['products'],
        ];
    }
}
