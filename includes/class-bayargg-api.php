<?php
/**
 * Klien HTTP ringan untuk BAYAR GG REST API.
 *
 * @package BayarGG_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class BayarGG_API {

    /** @var string */
    private $api_key;

    /** @var string */
    private $base_url;

    public function __construct($api_key, $base_url = 'https://www.bayar.gg/api') {
        $this->api_key  = (string) $api_key;
        $this->base_url = rtrim((string) $base_url, '/');
    }

    /**
     * Buat payment link baru. POST /create-payment.php
     *
     * @param array $payload amount, description, customer_*, payment_method, payment_url, callback_url, redirect_url
     * @return array Decoded response (lihat https://www.bayar.gg/api-docs)
     */
    public function create_payment(array $payload) {
        return $this->request('POST', '/create-payment.php', $payload);
    }

    /**
     * Cek status invoice. GET /check-payment.php?invoice=...
     *
     * @param string $invoice
     * @return array
     */
    public function check_payment($invoice) {
        return $this->request('GET', '/check-payment.php', ['invoice' => $invoice]);
    }

    /**
     * Eksekusi request memakai WP HTTP API.
     *
     * @return array Selalu array; pada kegagalan: ['success'=>false,'error'=>'...'].
     */
    private function request($method, $path, array $data = []) {
        $url  = $this->base_url . $path;
        $args = [
            'method'  => $method,
            'timeout' => 30,
            'headers' => [
                'Accept'    => 'application/json',
                'X-API-Key' => $this->api_key,
            ],
        ];

        if ('GET' === $method) {
            if (!empty($data)) {
                $url = add_query_arg(array_map('rawurlencode', $data), $url);
            }
        } else {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($body)) {
            return ['success' => false, 'error' => 'Respons tidak valid dari BAYAR GG (HTTP ' . $code . ').'];
        }

        return $body;
    }
}
