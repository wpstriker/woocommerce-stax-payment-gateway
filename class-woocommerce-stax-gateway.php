<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WooCommerce_Stax_Gateway extends WC_Payment_Gateway {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id           = 'stax';
		$this->has_fields   = true;
		$this->method_title = 'Stax Payment';

		$this->init_form_fields();
		$this->init_settings();
		$this->supports = array( 'products' );

		$this->title       = $this->get_option( 'woocommerce_stax_title' );
		$this->description = $this->get_option( 'woocommerce_stax_description' );

		if ( is_admin() ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ), 200 );
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'                      => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Stax Payment Gateway', 'woocommerce' ),
				'default' => 'yes',
			),
			'woocommerce_stax_title'       => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Stax Payment', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'woocommerce_stax_description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Stax Payment Gateway', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'woocommerce_stax_token'       => array(
				'title'       => __( 'Web Payments Token', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter web payments token received from Stax payment gateway.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'woocommerce_stax_api'         => array(
				'title'       => __( 'Api-Key', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Enter api-key received from Stax payment gateway.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	public function is_available() {
		if ( empty( $this->get_option( 'woocommerce_stax_token' ) ) || empty( $this->get_option( 'woocommerce_stax_api' ) ) ) {
			return false;
		}

		return true;
	}

	public function load_scripts() {
		wp_register_style( 'wc_stax_styles', WPS_WOOCOMMERCE_STAX_URL . '/assets/css/wc_stax.css' );
		wp_enqueue_style( 'wc_stax_styles' );

		wp_register_script( 'stax', 'https://staxjs.staxpayments.com/stax.js?nocache=2', null, null, false );
		wp_register_script( 'wc_stax', WPS_WOOCOMMERCE_STAX_URL . '/assets/js/wc_stax.js', array( 'stax', 'wc-credit-card-form' ), filemtime( WPS_WOOCOMMERCE_STAX_DIR . '/assets/js/wc_stax.js' ), true );
		$stax_params = array(
			'ajaxurl'                => WC_AJAX::get_endpoint( '%%endpoint%%' ),
			'is_checkout'            => ( is_checkout() && empty( $_GET['pay_for_order'] ) ) ? 'yes' : 'no', // wpcs: csrf ok.
			'token'                  => $this->get_option( 'woocommerce_stax_token' ),
			'is_change_payment_page' => isset( $_GET['change_payment_method'] ) ? 'yes' : 'no', // wpcs: csrf ok.
			'is_add_payment_page'    => is_wc_endpoint_url( 'add-payment-method' ) ? 'yes' : 'no',
			'is_pay_for_order_page'  => is_wc_endpoint_url( 'order-pay' ) ? 'yes' : 'no',
		);
		wp_localize_script( 'wc_stax', 'woocommerce_stax_params', apply_filters( 'woocommerce_stax_params', $stax_params ) );
		wp_enqueue_script( 'wc_stax' );
	}

	public function payment_fields() {
		$fields         = array();
		$default_fields = array(
			'card-number-field' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-card-number">' . esc_html__( 'Card number', 'woocommerce' ) . '&nbsp;<span class="required">*</span></label>
				<span id="' . esc_attr( $this->id ) . '-card-number" class="input-text wc-credit-card-form-card-number2" inputmode="numeric" autocomplete="cc-number" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" ' . $this->field_name( 'card-number' ) . ' style="display: inline-block; width: 100%; height: 50px; box-shadow: inset 2px 0 0 #0f834d;" ></span>
			</p>',
			'card-expiry-field' => '<p class="form-row form-row-first">
				<label for="' . esc_attr( $this->id ) . '-card-expiry">' . esc_html__( 'Expiry (MM/YY)', 'woocommerce' ) . '&nbsp;<span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-expiry" class="input-text wc-credit-card-form-card-expiry" inputmode="numeric" autocomplete="cc-exp" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" placeholder="' . esc_attr__( 'MM / YY', 'woocommerce' ) . '" ' . $this->field_name( 'card-expiry' ) . ' style="height: 50px;" />
			</p>',
			'card-cvc-field'    => '<p class="form-row form-row-last">
			<label for="' . esc_attr( $this->id ) . '-card-cvc">' . esc_html__( 'Card code', 'woocommerce' ) . '&nbsp;<span class="required">*</span></label>
			<span id="' . esc_attr( $this->id ) . '-card-cvc" class="input-text wc-credit-card-form-card-cvc2" inputmode="numeric" autocomplete="off" autocorrect="no" autocapitalize="no" spellcheck="no" type="tel" maxlength="4" placeholder="' . esc_attr__( 'CVC', 'woocommerce' ) . '" ' . $this->field_name( 'card-cvc' ) . ' style="display: inline-block;width: 100%;height: 50px;box-shadow: inset 2px 0 0 #0f834d;" ></span>
		</p>',
		);

		$fields = wp_parse_args( $fields, apply_filters( 'woocommerce_credit_card_form_fields', $default_fields, $this->id ) );
		?>
		<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-cc-form" class='wc-credit-card-form wc-payment-form'>
			<?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>
			<?php
			foreach ( $fields as $field ) {
				echo $field; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
			}
			?>
			<div class="clear"></div>
			<!-- Used to display form errors -->
			<div class="stax-source-errors" role="alert"></div>
			<?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
			<div class="clear"></div>
		</fieldset>
		<script type="text/javascript">
		jQuery(document).ready(function (e) {
			// Init StaxJs SDK
			window.staxJs = new StaxJs('<?php echo $this->get_option( 'woocommerce_stax_token' );?>', {
				number: {
					id: 'stax-card-number',     // the html id of the div you want to contain the credit card number field
					placeholder: '0000 0000 0000 0000',    // the placeholder the field should contain
					style: 'height: 30px; width: 100%; font-size: 15px;',    // the style to apply to the field
					format: 'prettyFormat'    // the formatting of the CC number (prettyFormat || plainFormat || maskedFormat)
				},
				cvv: {
					id: 'stax-card-cvc',    // the html id of the div you want to contain the cvv field
					placeholder: 'CVV',    // the placeholder the field should contain
					style: 'height: 30px; width: 100%; font-size: 15px;',    // the style to apply to the field
				}
			});

			// tell staxJs to load in the card fields
			window.staxJs.showCardForm().then(handler => {
				//console.log('form loaded');
			}).catch(err => {
				//console.log('error init form ' + err);
			});

			/*window.staxJs.on('card_form_complete', (message) => {
				// activate pay button
				console.log(message);
			});

			window.staxJs.on('card_form_uncomplete', (message) => {
				// deactivate pay button
				console.log(message);
			});*/
		});		
		</script>
		<?php
	}

	public function field_name( $name ) {
		return $this->supports( 'tokenization' ) ? '' : ' name="' . esc_attr( $this->id . '-' . $name ) . '" ';
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		$response_raw = wp_remote_post(
			'https://apiprod.fattlabs.com/charge',
			array(
				'method'    => 'POST',
				'sslverify' => false,
				'body'      => wp_json_encode(
					array(
						'payment_method_id' => $_POST['stax_source'],
						'meta'              => array(
							'order_id'         => $order->get_id(),
							'user_id'          => $order->get_user_id(),
							'stax_customer_id' => $_POST['stax_customer_id'],
							'email'            => $order->get_billing_email(),
						),
						'total'             => $order->get_total(),
						'pre_auth'          => false,
					)
				),
				'headers'   => array(
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer ' . $this->get_option( 'woocommerce_stax_api' ),
				),
			)
		);

		$response = json_decode( wp_remote_retrieve_body( $response_raw ), true );

		if ( ! siar( $response, 'success' ) ) {
			// Error
			$order->add_order_note( __( 'Payment Failure: ' . siar( $response, 'message' ). ', Ref ID = ' . siar( $response, 'id' ), 'woocommerce' ) );
			wc_add_notice( 'Payment Failure: ' . siar( $response, 'message' ), $notice_type = 'error' );
			return false;
		}

		$timestamp = date( 'Y-m-d H:i:s A e', current_time( 'timestamp' ) );

		$order->add_order_note( __( 'Charge success at ' . $timestamp . ', TXN ID = ' . siar( $response, 'id' ), 'woocommerce' ) );

		$order->payment_complete( siar( $response, 'id' ) );

		add_post_meta( $order->get_id(), '_stax_response', json_encode( $response ) );

		// Remove cart.
		WC()->cart->empty_cart();

		// Return thankyou redirect.
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}
}
