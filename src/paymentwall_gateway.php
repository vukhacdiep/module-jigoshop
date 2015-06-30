<?php
add_action( 'plugins_loaded', 'paymentwall_jigoshop_gateway', 159 );
function paymentwall_jigoshop_gateway() {

	if ( !class_exists( 'jigoshop_payment_gateway' ) ) return; // if the Jigoshop payment gateway class is not available, do nothing

	add_filter( 'jigoshop_payment_gateways', 'add_paymentwall' );

	function add_paymentwall( $methods ) {
		$methods[] = 'jigoshop_paymentwall';
		return $methods;
	}

	class jigoshop_paymentwall extends jigoshop_payment_gateway {

			public function paymentwall_init () {
				require_once('api/lib/paymentwall.php');
                Paymentwall_Config::getInstance()->set(array(
                    'api_type' => Paymentwall_Config::API_GOODS,
                    'public_key' => $this->appkey, // available in your Paymentwall merchant area
                    'private_key' => $this->secretkey // available in your Paymentwall merchant area
                ));
			}
		
			public function __construct() {
		
				parent::__construct();
				
				$this->id						= 'paymentwall';
				$this->has_fields				= false;
				$this->enabled					= Jigoshop_Base::get_options()->get_option('jigoshop_paymentwall_enabled');
				$this->title					= Jigoshop_Base::get_options()->get_option('jigoshop_paymentwall_title');
				$this->appkey					= Jigoshop_Base::get_options()->get_option('jigoshop_paymentwall_app_key');
				$this->secretkey				= Jigoshop_Base::get_options()->get_option('jigoshop_paymentwall_secret_key');
				$this->widget					= Jigoshop_Base::get_options()->get_option('jigoshop_paymentwall_widget');
				$this->description				= Jigoshop_Base::get_options()->get_option('jigoshop_paymentwall_description');
				$this->thankyoutext				= Jigoshop_Base::get_options()->get_option('jigoshop_paymentwall_thankyoutext');
				$this->currency					= Jigoshop_Base::get_options()->get_option('jigoshop_paymentwall_currency');
				$this->successurl				= Jigoshop_Base::get_options()->get_option('jigoshop_paymentwall_successurl');
				$this->testmode					= Jigoshop_Base::get_options()->get_option('jigoshop_paymentwall_testmode');
				$this->CREDIT_TYPE_CHARGEBACK	= 2;

				add_action( 'init', array($this, 'check_ipn_response') );
				add_action('receipt_paymentwall', array($this, 'receipt_paymentwall'));
			}
			
			protected function get_default_options() {
				$defaults = array();
				$defaults[] = array( 'name' => __('Paymentwall', 'jigoshop'), 'type' => 'title', 'desc' => __('This plugin extends the Jigoshop payment gateways by adding a Paymentwall payment solution.', 'jigoshop') );
				$defaults[] = array(
						'name'		=> __('Enable Paymentwall solution','jigoshop'),
						'desc' 		=> '',
						'tip' 		=> '',
						'id' 		=> 'jigoshop_paymentwall_enabled',
						'std' 		=> 'yes',
						'type' 		=> 'checkbox',
						'choices'	=> array(
								'no'			=> __('No', 'jigoshop'),
								'yes'			=> __('Yes', 'jigoshop')
						)
				);

				$defaults[] = array(
						'name'		=> __('Method Title','jigoshop'),
						'desc' 		=> '',
						'tip' 		=> __('This controls the title which the user sees during checkout.','jigoshop'),
						'id' 		=> 'jigoshop_paymentwall_title',
						'std' 		=> __('Paymentwall','jigoshop'),
						'type' 		=> 'text'
				);

				$defaults[] = array(
						'name'		=> __('Description','jigoshop'),
						'desc' 		=> '',
						'tip' 		=> __('This controls the description which the user sees during checkout.','jigoshop'),
						'id' 		=> 'jigoshop_paymentwall_description',
						'std' 		=> __("Pay via Paymentwall.", 'jigoshop'),
						'type' 		=> 'longtext'
				);

				$defaults[] = array(
						'name'		=> __('Thank you page text','jigoshop'),
						'desc' 		=> '',
						'tip' 		=> __('Text on iframe text widget.','jigoshop'),
						'id' 		=> 'jigoshop_paymentwall_thankyoutext',
						'std' 		=> __("Thank you for purchase!", 'jigoshop'),
						'type' 		=> 'longtext'
				);

				$defaults[] = array(
						'name'		=> __('Application Key','jigoshop'),
						'desc' 		=> '',
						'tip' 		=> __('Please enter your the Application Key; this is needed in order to make payment!','jigoshop'),
						'id' 		=> 'jigoshop_paymentwall_app_key',
						'std' 		=> '',
						'type' 		=> 'text'
				);

				$defaults[] = array(
						'name'		=> __('Secret Key','jigoshop'),
						'desc' 		=> '',
						'tip' 		=> __('Please enter your Secret Key; this is needed in order to make payment!','jigoshop'),
						'id' 		=> 'jigoshop_paymentwall_secret_key',
						'std' 		=> '',
						'type' 		=> 'text'
				);
				
				$defaults[] = array(
						'name'		=> __('Widget code','jigoshop'),
						'desc' 		=> '',
						'tip' 		=> __('e.g. p1_1, p4_1','jigoshop'),
						'id' 		=> 'jigoshop_paymentwall_widget',
						'std' 		=> __('p1_1','jigoshop'),
						'type' 		=> 'text'
				);

				$defaults[] = array(
						'name'		=> __('Currency','jigoshop'),
						'desc' 		=> '',
						'tip' 		=> __('Currency code ISO 4217','jigoshop'),
						'id' 		=> 'jigoshop_paymentwall_currency',
						'std' 		=> __('USD','jigoshop'),
						'type' 		=> 'text'
				);

				$defaults[] = array(
						'name'		=> __('Success url','jigoshop'),
						'desc' 		=> '',
						'tip' 		=> __('URL after success payment','jigoshop'),
						'id' 		=> 'jigoshop_paymentwall_successurl',
						'std' 		=> __('','jigoshop'),
						'type' 		=> 'text'
				);

				$defaults[] = array(
						'name'		=> __('Test mode?','jigoshop'),
						'desc' 		=> '',
						'tip' 		=> '',
						'id' 		=> 'jigoshop_paymentwall_testmode',
						'std' 		=> 'no',
						'type' 		=> 'checkbox',
						'choices'	=> array(
								'no'			=> __('No', 'jigoshop'),
								'yes'			=> __('Yes', 'jigoshop')
						)
				);
				return $defaults;
			}
			
			function receipt_paymentwall($order_id) {
				$this->paymentwall_init();
				$order = new jigoshop_order($order_id);

				$productNames = array();
				foreach ($order->items as $item) {
					array_push($productNames, $item['name']);
				}

				echo '<p>'.__($this->thankyoutext, 'jigoshop').'</p>';

				$widget = new Paymentwall_Widget(
					($order->user_id == 0) ? $_SERVER['REMOTE_ADDR'] : $order->user_id,	// id of the end-user who's making the payment
					$this->widget,											// widget code, e.g. p1; can be picked inside of your merchant account
					array(													// product details for Flexible Widget Call. To let users select the product on Paymentwall's end, leave this array empty
						new Paymentwall_Product(
							$order->id,								// id of the product in your system
							$order->order_subtotal,					// price
							$this->currency,						// currency code
							implode(', ', $productNames),			// product name
							Paymentwall_Product::TYPE_FIXED			// this is a time-based product; for one-time products, use Paymentwall_Product::TYPE_FIXED and omit the following 3 array elements
						)
					),
					array(
							'email' => $order->billing_email,
                            'integration_module' => 'jigoshop',
							'success_url' => $this->successurl,
							'test_mode' => (int)$this->testmode
						)			// additional parameters
				);
	
				echo $widget->getHtmlCode();

				jigoshop_cart::empty_cart();
			}
			
			function process_payment($order_id) {
				$order = new jigoshop_order($order_id);
		
				return array(
					'result' 	=> 'success',
					'redirect'	=> add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(jigoshop_get_page_id('pay'))))
				);
		
			}
			
			function payment_fields() {
				if($this->description){
					echo wpautop(wptexturize($this->description));
				}
			}

			function check_ipn_response() {
                if (isset($_GET['paymentwallListener']) && $_GET['paymentwallListener'] == 'paymentwall_IPN') {
                    $this->paymentwall_init();
                    unset($_GET['paymentwallListener']);

					$pingback = new Paymentwall_Pingback($_GET, $_SERVER['REMOTE_ADDR']);
					
					
					if ($pingback->validate()) {
						$productId = $pingback->getProduct()->getId();

						$order = new jigoshop_order((int)$productId);

						if ($order->id) {
							if ($pingback->isDeliverable()) {
								$order->update_status('completed', __('Order completed!', 'jigoshop'));
							} else if ($pingback->isCancelable()) {
								$order->update_status('canceled', __('Order canceled by Paymentwall!', 'jigoshop'));
							}

							$order->add_order_note(__('IPN payment completed', 'jigoshop'));
							echo 'OK'; // Paymentwall expects response to be OK, otherwise the pingback will be resent
						} else {
							echo "Undefined order!";
						}
					} else {
						echo $pingback->getErrorSummary();
					}

					die();
				}
			}
		}
}
