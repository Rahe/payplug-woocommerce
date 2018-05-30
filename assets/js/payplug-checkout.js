/* global payplug_checkout_params */

(function ($) {

	/**
	 * For now we need to redefine the `_closeIframe` from the Payplug object
	 * to trigger an event when it run. This allow us to redirect the user to
	 * the cancelURL manually.
	 */
	if (typeof Payplug != 'undefined') {
		Payplug._closeIframe = function (callback) {
			var node = document.getElementById("payplug-spinner");
			if (node) {
				node.style.display = "none";
				node.parentNode.removeChild(node);
			}
			node = document.getElementById("wrapper-payplug-iframe");
			if (node) {
				this._fadeOut(node, function () {
					if (callback) {
						callback();
					}
				});
			}
			// Hard Remove iframe
			node.parentNode.removeChild(node);
			node = document.getElementById("iframe-payplug-close");
			if (node && node.parentNode) {
				node.parentNode.removeChild(node);
			}

			$(document).trigger('payplugIframeClosed');
		}
	}

	var payplug_checkout = {
		init: function () {
			if ($('form.woocommerce-checkout').length) {
				this.$form = $('form.woocommerce-checkout');
				this.$form.on(
					'submit',
					this.onSubmit
				)
			}

			if ($('form#order_review').length) {
				this.$form = $('form#order_review');
				this.$form.on(
					'submit',
					this.onSubmit
				)
			}

			$(document).on('payplugIframeClosed', this.handleClosedIframe);
		},
		onSubmit: function (e) {
			if (!payplug_checkout.isPayplugChosen()) {
				return;
			}

			// Use standard checkout process if a payment token has been
			// choose by a user.
			if (payplug_checkout.isPaymentTokenSelected()) {
				return;
			}

			//Prevent submit and stop all other listeners from being triggered.
			e.preventDefault();
			e.stopImmediatePropagation();

			payplug_checkout.$form.block({ message: null, overlayCSS: { background: '#fff', opacity: 0.6 } });

			$.post(
				payplug_checkout_params.ajax_url,
				payplug_checkout.$form.serialize()
			).done(payplug_checkout.openModal);
		},
		openModal: function (response) {
			payplug_checkout.$form.unblock();

			if ('success' !== response.result) {
				response.message && alert(response.message);
				return;
			}

			payplug_checkout.cancelUrl = response.cancel || false;
			Payplug.showPayment(response.redirect);
		},
		handleClosedIframe: function () {
			if (payplug_checkout.cancelUrl) {
				window.location.href = payplug_checkout.cancelUrl;
			}
		},
		isPayplugChosen: function () {
			return $('#payment_method_payplug').is(':checked');
		},
		isPaymentTokenSelected: function () {
			return 'new' !== $('input[name=wc-payplug-payment-token]:checked').val();
		}
	};

	payplug_checkout.init();
})(jQuery);