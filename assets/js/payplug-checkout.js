(function ($) {

    var payplug_checkout = {
        init: function () {
            this.form = $('form.woocommerce-checkout');

            this.form.on(
                'submit',
                this.onSubmit
            )
        },
        onSubmit: function (e) {
            if (!payplug_checkout.isPayplugChosen()) {
                return;
            }

            // Prevent submit and stop all other listeners
            // from being triggered.
            e.preventDefault();
            e.stopImmediatePropagation();

            $.post(
                payplug_checkout_params.ajax_url,
                payplug_checkout.form.serialize()
            ).done(payplug_checkout.openModal);
        },
        openModal: function (response) {
            if ('success' !== response.result) {
                response.message && alert(response.message);
                return;
            }

            Payplug.showPayment(response.redirect);
        },
        isPayplugChosen: function () {
            return $('#payment_method_payplug').is(':checked');
        }
    };

    payplug_checkout.init();
})(jQuery);