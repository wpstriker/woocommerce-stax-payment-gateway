/* global woocommerce_stax_params */

jQuery(function ($) {
	'use strict';

	/**
	 * Object to handle Stax elements payment form.
	 */
	var woocommerce_stax_form = {
		/**
		 * Get WC AJAX endpoint URL.
		 *
		 * @param  {String} endpoint Endpoint.
		 * @return {String}
		 */
		getAjaxURL: function (endpoint) {
			return woocommerce_stax_params.ajaxurl
				.toString()
				.replace('%%endpoint%%', 'wc_stax_' + endpoint);
		},

		/**
		 * Initialize event handlers and UI state.
		 */
		init: function () {
			// Initialize tokenization script if on change payment method page and pay for order page.
			if ('yes' === woocommerce_stax_params.is_change_payment_page || 'yes' === woocommerce_stax_params.is_pay_for_order_page) {
				$(document.body).trigger('wc-credit-card-form-init');
			}

			// checkout page
			if ($('form.woocommerce-checkout').length) {
				this.form = $('form.woocommerce-checkout');
			}

			$('form.woocommerce-checkout')
				.on(
					'checkout_place_order_stax',
					this.onSubmit
				);

			// pay order page
			if ($('form#order_review').length) {
				this.form = $('form#order_review');
			}

			$('form#order_review, form#add_payment_method')
				.on(
					'submit',
					this.onSubmit
				);

			// add payment method page
			if ($('form#add_payment_method').length) {
				this.form = $('form#add_payment_method');
			}

			$('form.woocommerce-checkout')
				.on(
					'change',
					this.reset
				);

			$(document)
				.on(
					'staxError',
					this.onError
				)
				.on(
					'checkout_error',
					this.reset
				);

			woocommerce_stax_form.createElements();
		},

		createElements: function () {

		},

		/**
		 * Check to see if Stax in general is being used for checkout.
		 *
		 * @return {boolean}
		 */
		isStaxChosen: function () {
			return $('#payment_method_stax').is(':checked');
		},

		/**
		 * Checks if a source ID is present as a hidden input.
		 * Only used when SEPA Direct Debit is chosen.
		 *
		 * @return {boolean}
		 */
		hasSource: function () {
			return 0 < $('input.stax-source').length;
		},

		/**
		 * Check whether a mobile device is being used.
		 *
		 * @return {boolean}
		 */
		isMobile: function () {
			if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
				return true;
			}

			return false;
		},

		/**
		 * Blocks payment forms with an overlay while being submitted.
		 */
		block: function () {
			if (!woocommerce_stax_form.isMobile()) {
				woocommerce_stax_form.form.block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});
			}
		},

		/**
		 * Removes overlays from payment forms.
		 */
		unblock: function () {
			woocommerce_stax_form.form && woocommerce_stax_form.form.unblock();
		},

		/**
		 * Returns the selected payment method HTML element.
		 *
		 * @return {HTMLElement}
		 */
		getSelectedPaymentElement: function () {
			return $('.payment_methods input[name="payment_method"]:checked');
		},

		/**
		 * Initiates the creation of a Source object.
		 *
		 * Currently this is only used for credit cards and SEPA Direct Debit,
		 * all other payment methods work with redirects to create sources.
		 */
		createSource: function (event) {
			var stax_card_expiry = $('#stax-card-expiry').val();
			var card_expiry = stax_card_expiry.split('/');
			var card_month = $.trim(card_expiry[0]) || '';
			var card_year = $.trim(card_expiry[1]) || '';
			card_year = card_year.length == 2 ? '20' + card_year : card_year;

			var extraDetails = {
				firstname: $('#billing_first_name').val(),
				lastname: $('#billing_last_name').val(),
				method: "card",
				month: card_month,
				year: card_year,
				phone: $('#billing_phone').val(),
				address_1: $('#billing_address_1').val(),
				address_2: $('#billing_address_2').val(),
				address_city: $('#billing_city').val(),
				address_state: $('#billing_state').val(),
				address_zip: $('#billing_postcode').val(),
				address_country: $('#billing_country').val(),
				validate: false
			};

			// Handle card payments.
			window.staxJs
				.tokenize(extraDetails)
				.then((response) => {
					//console.log("payment method object:", response);
					//console.log("customer object:", response.customer);
					woocommerce_stax_form.sourceResponse(response);
				})
				.catch((err) => {
					//console.log(err);
					var message = typeof err === 'object' ? err.message : JSON.stringify(err);
					//console.log(message);
					woocommerce_stax_form.onError(event, message);
					//console.log("unsuccessful tokenization:", err);
				});
		},

		/**
		 * Handles responses, based on source object.
		 *
		 * @param {Object} response The `stax.createSource` response.
		 */
		sourceResponse: function (response) {
			if (response.error) {
				return $(document.body).trigger('staxError', response);
			}

			woocommerce_stax_form.reset();

			woocommerce_stax_form.form.append(
				$('<input type="hidden" />')
					.addClass('stax-source')
					.attr('name', 'stax_source')
					.val(response.id)
			);

			woocommerce_stax_form.form.append(
				$('<input type="hidden" />')
					.addClass('stax-customer-id')
					.attr('name', 'stax_customer_id')
					.val(response.customer_id)
			);

			if ($('form#add_payment_method').length) {
				$(woocommerce_stax_form.form).off('submit', woocommerce_stax_form.form.onSubmit);
			}

			woocommerce_stax_form.form.submit();
		},

		/**
		 * Performs payment-related actions when a checkout/payment form is being submitted.
		 *
		 * @return {boolean} An indicator whether the submission should proceed.
		 *                   WooCommerce's checkout.js stops only on `false`, so this needs to be explicit.
		 */
		onSubmit: function () {
			if (!woocommerce_stax_form.isStaxChosen()) {
				return true;
			}

			// If a source is already in place, submit the form as usual.
			if (woocommerce_stax_form.hasSource()) {
				return true;
			}

			woocommerce_stax_form.block();
			woocommerce_stax_form.createSource();

			return false;
		},

		/**
		 * If a new credit card is entered, reset sources.
		 */
		onCCFormChange: function () {
			woocommerce_stax_form.reset();
		},

		/**
		 * Removes all stax errors and hidden fields with IDs from the form.
		 */
		reset: function () {
			$('.wc-stax-error, .stax-source, .stax-customer-id').remove();
		},

		/**
		 * Displays stax-related errors.
		 *
		 * @param {Event}  e      The jQuery event.
		 * @param {Object} result The result of stax call.
		 */
		onError: function (e, message) {
			var selectedMethodElement = woocommerce_stax_form.getSelectedPaymentElement().closest('li');
			var errorContainer;

			// When no saved cards are available, display the error next to CC fields.
			errorContainer = selectedMethodElement.find('.stax-source-errors');

			woocommerce_stax_form.reset();
			$('.woocommerce-NoticeGroup-checkout').remove();
			//console.log(result.error.message); // Leave for troubleshooting.
			$(errorContainer).html('<ul class="woocommerce_error woocommerce-error wc-stax-error"><li /></ul>');
			$(errorContainer).find('li').text(message); // Prevent XSS

			if ($('.wc-stax-error').length) {
				$('html, body').animate({
					scrollTop: ($('.wc-stax-error').offset().top - 200)
				}, 200);
			}
			woocommerce_stax_form.unblock();
			$.unblockUI(); // If arriving via Payment Request Button.
		},

		/**
		 * Displays an error message in the beginning of the form and scrolls to it.
		 *
		 * @param {Object} error_message An error message jQuery object.
		 */
		submitError: function (error_message) {
			$('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
			woocommerce_stax_form.form.prepend('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">' + error_message + '</div>');
			woocommerce_stax_form.form.removeClass('processing').unblock();
			woocommerce_stax_form.form.find('.input-text, select, input:checkbox').blur();

			var selector = '';

			if ($('#add_payment_method').length) {
				selector = $('#add_payment_method');
			}

			if ($('#order_review').length) {
				selector = $('#order_review');
			}

			if ($('form.checkout').length) {
				selector = $('form.checkout');
			}

			if (selector.length) {
				$('html, body').animate({
					scrollTop: (selector.offset().top - 100)
				}, 500);
			}

			$(document.body).trigger('checkout_error');
			woocommerce_stax_form.unblock();
		},
	};

	woocommerce_stax_form.init();
});
