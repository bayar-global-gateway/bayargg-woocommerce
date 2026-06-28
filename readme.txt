=== BAYAR GG for WooCommerce ===
Contributors: bayargg
Tags: woocommerce, payment gateway, qris, e-wallet, indonesia, gopay, ovo, dana, shopeepay
Requires at least: 5.6
Tested up to: 6.7
Requires PHP: 7.4
WC requires at least: 5.0
WC tested up to: 9.5
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Terima pembayaran QRIS & e-wallet (QRIS BAYAR GG, BRI/GoPay/Livin Merchant QRIS, OVO) di WooCommerce lewat BAYAR GG.

== Description ==

BAYAR GG for WooCommerce menghubungkan toko WooCommerce Anda dengan gateway pembayaran BAYAR GG. Pelanggan membayar lewat QRIS (satu QR bisa dipindai semua e-wallet dan mobile banking), lalu status order otomatis menjadi lunas melalui webhook.

Fitur:

* Satu metode "QRIS / E-Wallet" di halaman checkout WooCommerce.
* Mendukung metode: QRIS Admin, QRIS BAYAR GG, BRI Merchant QRIS, Livin Merchant QRIS, GoPay Merchant QRIS, OVO.
* Konfirmasi pembayaran otomatis via webhook + verifikasi ulang ke API (anti-spoof).
* Jaring pengaman: status diverifikasi lagi saat pelanggan kembali ke halaman "terima kasih".
* Kompatibel WooCommerce HPOS (High-Performance Order Storage).
* Mode debug + log bawaan WooCommerce.

Butuh akun BAYAR GG dan API Key. Daftar / login di https://www.bayar.gg

== Installation ==

1. Unduh plugin (file .zip) dari halaman GitHub: Code -> Download ZIP.
2. Di WordPress: Plugins -> Add New -> Upload Plugin -> pilih file .zip -> Install Now -> Activate.
3. Buka WooCommerce -> Settings -> Payments -> BAYAR GG -> Manage/Setup.
4. Centang "Aktifkan", tempel API Key dari Dashboard BAYAR GG, pilih Metode Pembayaran, lalu Save changes.
5. Selesai. Lakukan test order untuk memastikan.

Panduan lengkap & bergambar ada di README.md repository.

== Frequently Asked Questions ==

= Apakah perlu setting webhook manual di BAYAR GG? =
Tidak. Plugin mengirim callback_url otomatis setiap transaksi, dan memverifikasi ulang status ke API sebelum menandai order lunas.

= Order tidak otomatis lunas? =
Aktifkan Mode Debug lalu cek WooCommerce -> Status -> Logs (source: bayargg). Pastikan API Key benar dan metode pembayaran aktif di akun BAYAR GG.

= Metode apa yang sebaiknya dipilih? =
"QRIS Admin" paling universal (maks Rp 500.000/transaksi). Untuk nominal besar atau settlement ke rekening sendiri, pakai QRIS BAYAR GG / BRI / Livin / GoPay sesuai yang sudah aktif di akun Anda.

== Changelog ==

= 1.0.1 =
* Pakai logo resmi BAYAR GG sebagai ikon gateway di checkout (ukuran rapi).
* Header bermerek di halaman pengaturan WooCommerce.
* Penyederhanaan teks: "semua e-wallet" (tidak lagi menyebut GoPay/OVO/DANA/ShopeePay satu per satu).
* Banner & badge README diperbarui.

= 1.0.0 =
* Rilis pertama: gateway QRIS/e-wallet, webhook + verifikasi, dukungan HPOS.
