<?php
/**
 * Gateway pembayaran BAYAR GG untuk WooCommerce.
 *
 * @package BayarGG_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_BayarGG_Gateway extends WC_Payment_Gateway {

    /** @var string */
    private $api_key;
    /** @var string */
    private $base_url;
    /** @var string */
    private $payment_method;
    /** @var bool */
    private $debug;

    public function __construct() {
        $this->id                 = 'bayargg';
        $this->method_title       = 'BAYAR GG';
        $this->method_description = 'Terima pembayaran QRIS &amp; e-wallet via BAYAR GG (QRIS BAYAR GG, BRI/GoPay/Livin Merchant QRIS, OVO). Pelanggan diarahkan ke halaman pembayaran QRIS, dan status order otomatis menjadi lunas lewat webhook.';
        $this->has_fields         = false;
        $this->icon               = apply_filters('bayargg_wc_icon', BAYARGG_WC_URL . 'assets/bayargg-logo.png');

        $this->init_form_fields();
        $this->init_settings();

        $this->title          = $this->get_option('title', 'QRIS / E-Wallet (BAYAR GG)');
        $this->description    = $this->get_option('description', 'Bayar pakai QRIS — bisa dipindai semua e-wallet & mobile banking.');
        $this->enabled        = $this->get_option('enabled');
        $this->api_key        = trim((string) $this->get_option('api_key'));
        $this->base_url       = $this->sanitize_base_url($this->get_option('base_url'));
        $this->payment_method = $this->get_option('payment_method', 'qris');
        $this->debug          = 'yes' === $this->get_option('debug', 'no');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

        // Webhook BAYAR GG memanggil URL ini saat pembayaran sukses:
        //   https://toko-anda.com/?wc-api=bayargg_callback
        add_action('woocommerce_api_bayargg_callback', [$this, 'handle_callback']);

        // Saat pelanggan kembali ke halaman "terima kasih", verifikasi status sekali lagi.
        add_action('woocommerce_thankyou_' . $this->id, [$this, 'maybe_verify_on_thankyou']);
    }

    /**
     * Batasi API Base URL ke host BAYAR GG (mencegah SSRF / kebocoran API key
     * ke host arbitrer bila akun admin disusupi). Fallback ke default.
     *
     * @param string|null $value
     * @return string
     */
    private function sanitize_base_url($value) {
        $default = 'https://www.bayar.gg/api';
        $url     = trim((string) $value);
        if ('' === $url) {
            return $default;
        }
        $host = wp_parse_url($url, PHP_URL_HOST);
        $host = is_string($host) ? strtolower($host) : '';
        // Izinkan hanya bayar.gg dan subdomainnya, via HTTPS.
        $ok_scheme = (0 === strpos($url, 'https://'));
        $ok_host   = ('bayar.gg' === $host || (strlen($host) > 9 && '.bayar.gg' === substr($host, -9)));
        if ($ok_scheme && $ok_host) {
            return rtrim($url, '/');
        }
        return $default;
    }

    /**
     * Tampilkan logo BAYAR GG dengan ukuran rapi di checkout (tinggi tetap ~26px),
     * sehingga tidak melar di tema apa pun.
     *
     * @return string
     */
    public function get_icon() {
        $icon_url = $this->icon ? $this->icon : (BAYARGG_WC_URL . 'assets/bayargg-logo.png');
        $icon     = sprintf(
            '<img src="%s" alt="%s" style="max-height:26px;width:auto;vertical-align:middle;margin-left:6px;" />',
            esc_url($icon_url),
            esc_attr($this->get_title())
        );

        return apply_filters('woocommerce_gateway_icon', $icon, $this->id);
    }

    /**
     * Header bermerek di atas form pengaturan (WooCommerce → Payments → BAYAR GG).
     */
    public function admin_options() {
        $logo = esc_url(BAYARGG_WC_URL . 'assets/bayargg-logo.png');
        ?>
        <div style="display:flex;align-items:center;gap:16px;padding:18px 22px;margin:0 0 18px;border-radius:14px;background:linear-gradient(120deg,#0a0e17 0%,#0d2a63 100%);box-shadow:0 6px 24px rgba(10,14,23,.18);">
            <img src="<?php echo $logo; ?>" alt="BAYAR GG" style="height:34px;width:auto;" />
            <div style="line-height:1.4;">
                <div style="color:#fff;font-size:15px;font-weight:700;">Gateway Pembayaran BAYAR GG</div>
                <div style="color:#9fc2ff;font-size:12.5px;">QRIS &amp; e-wallet untuk WooCommerce · <a href="https://www.bayar.gg/api-docs" target="_blank" rel="noopener" style="color:#5aa2ff;text-decoration:none;">Dokumentasi API</a> · <a href="https://www.bayar.gg/dashboard" target="_blank" rel="noopener" style="color:#5aa2ff;text-decoration:none;">Dashboard</a></div>
            </div>
        </div>
        <?php
        parent::admin_options();
    }

    /**
     * Form pengaturan di WooCommerce → Settings → Payments → BAYAR GG.
     */
    public function init_form_fields() {
        $callback_url = home_url('/?wc-api=bayargg_callback');

        $this->form_fields = [
            'enabled' => [
                'title'   => 'Aktifkan / Nonaktifkan',
                'type'    => 'checkbox',
                'label'   => 'Aktifkan pembayaran BAYAR GG',
                'default' => 'no',
            ],
            'title' => [
                'title'       => 'Judul',
                'type'        => 'text',
                'description' => 'Nama metode pembayaran yang dilihat pelanggan saat checkout.',
                'default'     => 'QRIS / E-Wallet (BAYAR GG)',
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => 'Deskripsi',
                'type'        => 'textarea',
                'description' => 'Keterangan singkat di bawah judul metode pembayaran.',
                'default'     => 'Bayar pakai QRIS — bisa dipindai semua e-wallet & mobile banking.',
                'desc_tip'    => true,
            ],
            'api_key' => [
                'title'       => 'API Key',
                'type'        => 'password',
                'description' => 'Ambil di Dashboard BAYAR GG → menu API / Pengaturan. Wajib diisi.',
                'default'     => '',
            ],
            'base_url' => [
                'title'       => 'API Base URL',
                'type'        => 'text',
                'description' => 'Biarkan default kecuali diinstruksikan tim BAYAR GG.',
                'default'     => 'https://www.bayar.gg/api',
                'desc_tip'    => true,
            ],
            'payment_method' => [
                'title'       => 'Metode Pembayaran',
                'type'        => 'select',
                'description' => 'Metode yang dipakai untuk membuat invoice. Pastikan metode ini aktif di akun BAYAR GG Anda.',
                'default'     => 'qris',
                'options'     => [
                    'qris'          => 'QRIS Admin (default, maks Rp 500.000)',
                    'qris_bayar_gg' => 'QRIS BAYAR GG (per-merchant, butuh aktif)',
                    'qris_user'     => 'BRI Merchant QRIS',
                    'qris_livin'    => 'Livin Merchant QRIS (Mandiri)',
                    'gopay_qris'    => 'GoPay Merchant QRIS',
                    'ovo'           => 'OVO',
                ],
            ],
            'callback_info' => [
                'title'       => 'Webhook / Callback URL',
                'type'        => 'title',
                'description' => 'Tidak perlu di-set manual — plugin mengirim URL ini otomatis setiap transaksi:<br><code>' . esc_html($callback_url) . '</code>',
            ],
            'debug' => [
                'title'       => 'Mode Debug',
                'type'        => 'checkbox',
                'label'       => 'Catat log ke WooCommerce → Status → Logs (source: bayargg)',
                'default'     => 'no',
            ],
        ];
    }

    /**
     * Proses checkout: buat payment link lalu redirect pelanggan ke halaman bayar.
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        if (!$order) {
            return ['result' => 'failure'];
        }

        if ('' === $this->api_key) {
            wc_add_notice('Pembayaran belum dikonfigurasi (API Key kosong). Hubungi pemilik toko.', 'error');
            return ['result' => 'failure'];
        }

        $api    = new BayarGG_API($this->api_key, $this->base_url);
        $amount = (int) round((float) $order->get_total());

        $payload = [
            'amount'         => $amount,
            'description'    => 'Order #' . $order->get_order_number(),
            'customer_name'  => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
            'customer_email' => $order->get_billing_email(),
            'customer_phone' => $order->get_billing_phone(),
            'payment_method' => $this->payment_method,
            'payment_url'    => 'https://www.bayar.gg/pay',
            'callback_url'   => home_url('/?wc-api=bayargg_callback'),
            'redirect_url'   => $this->get_return_url($order),
        ];

        $res = $api->create_payment($payload);

        if (empty($res['success']) || empty($res['data']['payment_url']) || empty($res['data']['invoice_id'])) {
            $msg = isset($res['error']) ? $res['error'] : 'Gagal membuat pembayaran.';
            $this->log('create_payment gagal untuk order #' . $order->get_order_number() . ': ' . $msg);
            wc_add_notice('Gagal memproses pembayaran: ' . esc_html($msg), 'error');
            return ['result' => 'failure'];
        }

        $data = $res['data'];

        $order->update_meta_data('_bayargg_invoice_id', sanitize_text_field($data['invoice_id']));
        $order->update_meta_data('_bayargg_payment_url', esc_url_raw($data['payment_url']));
        $order->update_status('pending', sprintf('Menunggu pembayaran BAYAR GG (invoice %s).', $data['invoice_id']));
        $order->save();

        $this->log('Invoice dibuat: ' . $data['invoice_id'] . ' untuk order #' . $order->get_order_number());

        if (WC()->cart) {
            WC()->cart->empty_cart();
        }

        return [
            'result'   => 'success',
            'redirect' => $data['payment_url'],
        ];
    }

    /**
     * Handler webhook BAYAR GG. Demi keamanan, status TIDAK dipercaya dari body
     * webhook — plugin memverifikasi ulang ke API sebelum menandai order lunas.
     */
    public function handle_callback() {
        $raw     = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $payload = wp_unslash($_POST); // phpcs:ignore WordPress.Security.NonceVerification
        }

        $invoice_raw = $payload['invoice_id'] ?? $payload['invoice'] ?? (isset($_GET['invoice']) ? wp_unslash($_GET['invoice']) : ''); // phpcs:ignore WordPress.Security.NonceVerification
        $invoice     = sanitize_text_field((string) $invoice_raw);

        if ('' === $invoice) {
            status_header(400);
            echo 'missing invoice';
            exit;
        }

        $order = $this->get_order_by_invoice($invoice);
        if (!$order) {
            status_header(404);
            echo 'order not found';
            exit;
        }

        // Verifikasi independen ke BAYAR GG (anti-spoof).
        $api    = new BayarGG_API($this->api_key, $this->base_url);
        $check  = $api->check_payment($invoice);
        $status = $check['data']['status'] ?? ($check['status'] ?? '');

        if ('paid' === $status) {
            $this->mark_order_paid($order, $check, $invoice, 'webhook');
            status_header(200);
            echo 'ok';
            exit;
        }

        status_header(202);
        echo 'pending';
        exit;
    }

    /**
     * Verifikasi status saat pelanggan kembali ke halaman "terima kasih"
     * (jaring pengaman jika webhook telat/terlewat).
     *
     * @param int $order_id
     */
    public function maybe_verify_on_thankyou($order_id) {
        $order = wc_get_order($order_id);
        if (!$order || $order->is_paid()) {
            return;
        }
        $invoice = $order->get_meta('_bayargg_invoice_id');
        if (!$invoice) {
            return;
        }
        $api    = new BayarGG_API($this->api_key, $this->base_url);
        $check  = $api->check_payment($invoice);
        $status = $check['data']['status'] ?? ($check['status'] ?? '');
        if ('paid' === $status) {
            $this->mark_order_paid($order, $check, $invoice, 'thankyou');
        }
    }

    /**
     * Tandai order lunas (idempoten).
     */
    private function mark_order_paid($order, array $check, $invoice, $source) {
        if ($order->is_paid()) {
            return;
        }

        // Respons API bisa flat atau ter-nest di bawah "data" — dukung keduanya.
        $d = (isset($check['data']) && is_array($check['data'])) ? $check['data'] : $check;

        // Defense-in-depth: pastikan nominal yang dibayar cocok dengan total order
        // sebelum menandai lunas (mencegah underpayment / invoice tertukar).
        $expected = (int) round((float) $order->get_total());
        $paidRaw  = $d['final_amount'] ?? ($d['amount'] ?? null);
        $paid     = (null !== $paidRaw) ? (int) round((float) $paidRaw) : null;
        if (null !== $paid && $expected > 0 && $paid < $expected) {
            $order->add_order_note(sprintf(
                'BAYAR GG: pembayaran ditahan — nominal terverifikasi (%d) lebih kecil dari total order (%d). Invoice %s.',
                $paid,
                $expected,
                $invoice
            ));
            $this->log(sprintf('Order #%s TIDAK ditandai lunas: paid %d < total %d (invoice %s).', $order->get_order_number(), $paid, $expected, $invoice));
            return;
        }

        $reff = $d['paid_reff_num'] ?? ($d['reff_num'] ?? $invoice);
        $order->payment_complete($reff);
        $order->add_order_note(sprintf('Pembayaran BAYAR GG diterima (invoice %s, ref %s, via %s).', $invoice, $reff, $source));
        $this->log(sprintf('Order #%s ditandai lunas via %s (invoice %s).', $order->get_order_number(), $source, $invoice));
    }

    /**
     * Cari order dari meta invoice (kompatibel HPOS & legacy).
     *
     * @return WC_Order|null
     */
    private function get_order_by_invoice($invoice) {
        $orders = wc_get_orders([
            'limit'      => 1,
            'meta_key'   => '_bayargg_invoice_id', // phpcs:ignore WordPress.DB.SlowDBQuery
            'meta_value' => $invoice,              // phpcs:ignore WordPress.DB.SlowDBQuery
        ]);
        if (!empty($orders) && $orders[0] instanceof WC_Order) {
            return $orders[0];
        }
        return null;
    }

    private function log($message) {
        if (!$this->debug) {
            return;
        }
        wc_get_logger()->info($message, ['source' => 'bayargg']);
    }
}
