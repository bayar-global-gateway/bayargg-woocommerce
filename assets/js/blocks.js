/**
 * BAYAR GG — WooCommerce Blocks (block checkout) payment method registration.
 *
 * Ditulis tanpa build step (memakai global wc/wp dari WooCommerce Blocks).
 */
(function () {
	if (!window.wc || !window.wc.wcBlocksRegistry || !window.wp || !window.wp.element) {
		return;
	}

	var registerPaymentMethod = window.wc.wcBlocksRegistry.registerPaymentMethod;
	var settings = (window.wc.wcSettings && window.wc.wcSettings.getSetting)
		? window.wc.wcSettings.getSetting('bayargg_data', {})
		: {};
	var createElement = window.wp.element.createElement;
	var decodeEntities = (window.wp.htmlEntities && window.wp.htmlEntities.decodeEntities)
		? window.wp.htmlEntities.decodeEntities
		: function (s) { return s; };

	var title = decodeEntities(settings.title || 'QRIS / E-Wallet (BAYAR GG)');
	var description = decodeEntities(settings.description || 'Bayar pakai QRIS — bisa dipindai semua e-wallet & mobile banking.');
	var iconUrl = settings.icon || '';

	function Label() {
		var children = [
			createElement('span', { key: 'label' }, title),
		];
		if (iconUrl) {
			children.push(
				createElement('img', {
					key: 'icon',
					src: iconUrl,
					alt: title,
					style: { maxHeight: '24px', width: 'auto', marginLeft: '8px', verticalAlign: 'middle' },
				})
			);
		}
		return createElement(
			'span',
			{ style: { display: 'flex', alignItems: 'center', justifyContent: 'space-between', width: '100%' } },
			children
		);
	}

	function Content() {
		return createElement('div', null, description);
	}

	registerPaymentMethod({
		name: 'bayargg',
		label: createElement(Label, null),
		content: createElement(Content, null),
		edit: createElement(Content, null),
		canMakePayment: function () { return true; },
		ariaLabel: title,
		supports: {
			features: (settings.supports && settings.supports.length) ? settings.supports : ['products'],
		},
	});
})();
