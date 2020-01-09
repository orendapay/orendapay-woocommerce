<?php
/**
 * OrendaPay for WooCommerce.
 *
 * @package   WC_orendapay_class
 * @author    Vítor Hugo Silva Gomes <vitorhugo@vitorhug.com>
 * @license   GPL-3.0+
 * @copyright 2019 OrendaPay
 *
 * @wordpress-plugin
 * Plugin Name:       OrendaPay
 * Plugin URI:        https://www.orendapay.com.br
 * Description:       Plugin de Pagamento OrendaPay para Woocommerce
 * Version:           1.0.0
 * Author:            OrendaPay Soluções Financeiras
 * Author URI:        https://www.orendapay.com.br
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       woo-orendapay
 **/

add_filter( 'woocommerce_payment_gateways', 'orendapay_class' );

function orendapay_class( $gateways ) 
{
	$gateways[] = 'WC_orendapay_class'; 
	return $gateways;
}


/*
 * The class itself
 */
add_action( 'plugins_loaded', 'orendapay_class_init' );


/**
 * OrendaPay payment gateway class.
 *
 * @package WC_orendapay_class
 * @author    Vítor Hugo Silva Gomes <vitorhugo@vitorhug.com>
 * @license   GPL-2.0+
 * @copyright 2019 OrendaPay
 */
function orendapay_class_init() 
{
 
	class WC_orendapay_class extends WC_Payment_Gateway 
	{
 
		
		/**
		 * Constructor for the OrendaPay gateway.
		 */
 		public function __construct() 
		{
 
			$this->id = 'orendapay'; // payment gateway plugin ID
			$this->icon = 'https://www.orendapay.com.br/layout_files/images/logo.svg'; // icon
			$this->has_fields = false; 
			$this->method_title = __( 'OrendaPay - Boleto Bancário', 'woo-orendapay' );
			$this->method_description = __( 'Comece a receber dinheiro via boleto bancário usando a OrendaPay Soluções em Pagamento', 'woo-orendapay' );
			
			// Endpoint API.
			$this->api_url = 'https://www.orendapay.com.br/api/v1/cobranca';	
			
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
			
			$this->title = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );
			$this->enabled = $this->get_option( 'enabled' );
			$this->merchant_id = $this->get_option( 'merchant_id' );
			$this->auth_token = $this->get_option( 'auth_token' );
		 

			// hook saves the settings
			add_action( 'woocommerce_api_orendapay_webhook', array( $this, 'check_webhook_notification' ) );
			add_action( 'woocommerce_orendapay_webhook_notification', array( $this, 'successful_webhook_notification' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
			add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 2 );
			add_action( 'woocommerce_order_details_after_order_table', array( $this, 'view_order_custom_payment_instruction' ), 10, 2 );
			
			// Display admin notices and dependencies.
			$this->admin_notices();		 
 
 		}
 
		/**
		 * Initialize Gateway Settings Form Fields
		 */
 		public function init_form_fields()
		{
 
			$this->form_fields = array(
				'enabled' => array(
					'title'       => __('Habilitar/Desabilitar', 'woo-orendapay' ),
					'label'       => __('Ativar OrendaPay', 'woo-orendapay' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),
				'title' => array(
					'title'       => __('Título', 'woo-orendapay' ),
					'type'        => 'text',
					'description' => __('Nome da Forma de Pagamento que aparecerá na tela de checkout.', 'woo-orendapay' ),
					'default'     => __('Boleto Bancário', 'woo-orendapay' ),
					'desc_tip'    => false,
				),
				'description' => array(
					'title'       => __('Descrição', 'woo-orendapay' ),
					'type'        => 'textarea',
					'description' => __('Descrição da Forma de Pagamento que aparecerá na tela de checkout.', 'woo-orendapay' ),
					'default'     => __('Pague sua compra com Boleto Bancário', 'woo-orendapay' ),
				),
				'merchant_id' => array(
					'title'       => __('ID da Integração', 'woo-orendapay' ),
					'type'        => 'text',
					'description' => __('Obtenha essa informação do seu Painel OrendaPay no menu Integrações', 'woo-orendapay' ),
					'placeholder' => __('Informe o ID da integração OrendaPay', 'woo-orendapay' )
				),
				'auth_token' => array(
					'title'       => __('Token de Integração', 'woo-orendapay' ),
					'type'        => 'text',
					'description' => __('Obtenha essa informação do seu Painel OrendaPay no menu Integrações', 'woo-orendapay' ),
					'placeholder' => __('Informe o Token de Integração OrendaPay', 'woo-orendapay' )
				)
			);
 
	 	}
 
 
		/*
 		 * Fields validation Checkout
		 */
		public function validate_fields() 
		{
 
			if( empty( $_POST[ 'billing_first_name' ]) ) 
			{
				wc_add_notice(  'First name is required!', 'error' );
				return false;
			}
			
			if( empty( $_POST[ 'billing_cpf' ]) && empty( $_POST[ 'billing_cnpj' ]) ) 
			{
				wc_add_notice(  'billing_cpf or billing_cnpj is required!', 'error' );
				return false;
			}

			if( empty( $_POST[ 'billing_address_1' ]) ) 
			{
				wc_add_notice(  'Address is required!', 'error' );
				return false;
			}
			
			if( empty( $_POST[ 'billing_email' ]) ) 
			{
				wc_add_notice(  'Email is required!', 'error' );
				return false;
			}			

			if( empty( $_POST[ 'billing_city' ]) ) 
			{
				wc_add_notice(  'City is required!', 'error' );
				return false;
			}			

			if( empty( $_POST[ 'billing_postcode' ]) ) 
			{
				wc_add_notice(  'Postal code is required!', 'error' );
				return false;
			}

			return true;
 
		}
		
		
		
		/**
		 * Create the payment data.
		 * @since  1.0.0
		 * @param  WC_Order $order Order data.
		 * @return array           Payment data.
		 */
		protected function payment_data( $order ) 
		{
		
			$product_list = '';
			$order_item = $order->get_items();
			foreach( $order_item as $product ) 
			{
				$prodct_name[] = $product['name'];
			}
			
			$product_list = implode( ',\n', $prodct_name );
			
			$args = array(
				// Customer data.
				'customer_person_name'   => $order->billing_first_name . ' ' . $order->billing_last_name,
				// Order data.
				'amount'                 => number_format( $order->order_total, 2, ',', '' ),
				// Document data.
				'description'            => $product_list,
				'customer_email'         => $order->billing_email,
				'meta'                   => 'order-' . $order->id,
				'expire_at'              => date( 'd/m/Y', time() + ( 3 * 86400 ) ),
				'bank_billet_account_id' => $this->contract_id
			);
		
			// WooCommerce Extra Checkout Fields for Brazil person type fields.
			if ( isset( $order->billing_persontype ) && ! empty( $order->billing_persontype ) ) 
			{
				if ( 2 == $order->billing_persontype ) 
				{
					$args['customer_cnpj_cpf'] = $order->billing_cnpj;
					$cliente_cpf_cnpj = $order->billing_cnpj;
				}
				else 
				{
					$args['customer_cnpj_cpf'] = $order->billing_cpf;
					$cliente_cpf_cnpj = $order->billing_cpf;
				}
			}
			
			// Address.
			if ( isset( $order->billing_postcode ) && ! empty( $order->billing_postcode ) ) 
			{
				$args['customer_address'] = $order->billing_address_1;
				$args['customer_city_name']    = $order->billing_city;
				$args['customer_state']   = $order->billing_state;
				$args['customer_zipcode'] = $order->billing_postcode;
			
				// WooCommerce Extra Checkout Fields for Brazil neighborhood field.
				if ( isset( $order->billing_neighborhood ) && ! empty( $order->billing_neighborhood ) ) 
				{
					$args['customer_neighborhood'] = $order->billing_neighborhood;
				}
				
				// WooCommerce Extra Checkout Fields for Brazil number field.
				if ( isset( $order->billing_number ) && ! empty( $order->billing_number ) ) 
				{
					$args['customer_address_number'] = $order->billing_number;
				}
				
				// Address complement.
				if ( ! empty( $order->billing_address_2 ) ) {
					$args['customer_address_complement'] = $order->billing_address_2;
				}
			}
			
			// Phone
			if ( isset( $order->billing_phone ) && ! empty( $order->billing_phone ) ) 
			{
				$args['customer_phone_number'] = preg_replace("/\D/", "", $order->billing_phone);
			}
			
			// Sets a filter for custom arguments.
			$args = apply_filters( 'woocommerce_boletosimples_billet_data', $args, $order );
			$args = array('bank_billet' => $args );


			//Create json POST API OrendaPay
			$vencimento = date( 'd/m/Y', time() + ( 3 * 86400 ) );
			$valor = number_format( $order->order_total, 2, '.', '' );
			$cliente_nome = $order->billing_first_name . ' ' . $order->billing_last_name;
			$url_call_back = get_bloginfo('url')."/wc-api/orendapay_webhook";
			 
			$json = array
			(
			"seu_codigo"=>"$order->id",
			"descricao"=>"Pedido $order->id",
			"vencimento"=>"$vencimento",
			"valor"=>"$valor",
			"juros"=>"0.00",
			"multa"=>"0.00",
			"desconto_pontualidade"=>"0.00",
			"cliente_nome"=>"$cliente_nome",
			"cliente_cpf_cnpj"=>"$cliente_cpf_cnpj",
			"cliente_telefone"=>"$order->billing_phone",
			"cliente_email"=>"$order->billing_email",
			"cliente_endereco"=>"$order->billing_address_1",
			"cliente_cidade"=>"$order->billing_city",
			"cliente_uf"=>"$order->billing_state",
			"cliente_cep"=>"$order->billing_postcode",
			"cliente_grupo"=>"E-commerce",
			"NUMERO_PARCELAS"=>"1",
			"RECORRENCIA"=>"0",
			"ENVIAR_EMAIL"=>"0",		
			"ENVIAR_SMS"=>"0",		
			"ENVIO_IMEDIATO"=>"1",
			"TIPO"=>"boleto",
			"URL_CALLBACK"=>"$url_call_back"
			);		
			
			return $json;
		}		    
		
		 
		
		/**
		 * Generate the billet on OrendaPay
		 * @since  1.0.0
		 * @param  WC_Order $order Order data.
		 * @return bool           Fail or success.
		 */
		protected function generate_billet( $order ) 
		{
			
			$json = $this->payment_data( $order );
			
			$params = array( 
				'method'     => 'POST',
				'charset'    => 'UTF-8',
				'body'       => json_encode($json),
				'sslverify'  => false,
				'timeout'    => 60,
				'headers'    => array(
				    'x-ID' => $this->merchant_id,
				    'x-Token' => $this->auth_token,
					'Content-Type' => 'application/json'
				)
			);
			
			$response = wp_remote_post( $this->api_url , $params );
			
			//Json to Object
			$data = json_decode($response['body']);
			$dataPedido = $data->cobrancas[0];

			//Ok
			if ($response['response']['code'] == 201) 
			{
				// Save billet data in order meta.
				add_post_meta( $order->id, 'orendapay_cod_transacao', $dataPedido->boleto_codigo );
				add_post_meta( $order->id, 'orendapay_url_boleto', $dataPedido->url );
				add_post_meta( $order->id, 'orendapay_linha_digitavel', $dataPedido->linha_digitavel );
				
				return true;
			}
			else
			{
				return false;
			}
		}		
		
		

		/**
		 * Backwards compatibility with version prior to 2.1.
		 * @since  1.0.0
		 * @return object Returns the main instance of WooCommerce class.
		 */
		protected function woocommerce_instance() 
		{
			if ( function_exists( 'WC' ) ) 
			{
				return WC();
			}
			else 
			{
				global $woocommerce;
				return $woocommerce;
			}
		}		
		
		
 
		/**
		 * Process the payment and return the result.
		 * @since  1.0.0
		 * @param  int    $order_id Order ID.
		 * @return array            Redirect when has success and display error notices when fail.
		 */
		public function process_payment( $order_id ) 
		{ 
			$order = new WC_Order( $order_id );
			
			
			// Send order for API
			$billet = $this->generate_billet( $order );					
			
			if($billet) 
			{
					
				// Mark as on-hold (we're awaiting the payment)
				$order->update_status( 'on-hold', __( 'Aguardando Pagamento', 'woo-orendapay' ) );
						
				// Reduce stock levels
				$order->reduce_order_stock();
						
				// Remove cart
				$this->woocommerce_instance()->cart->empty_cart();
						
				// Sets the return url.
				if ( version_compare( $this->woocommerce_instance()->version, '2.1', '>=' ) ) 
				{
					$url = $order->get_checkout_order_received_url();
				}
				else 
				{
					$url = add_query_arg( 'key', $order->order_key, add_query_arg( 'order', $order_id, get_permalink( woocommerce_get_page_id( 'thanks' ) ) ) );
				}
				
				// Return thankyou redirect.
				return array(
					'result'   => 'success',
					'redirect' => $url 
				);
			}
			else 
			{
				// Added error message.
				$this->add_error( '<strong>' . $this->title . '</strong>: ' . __( 'Ocorreu um erro ao processar seu pagamento. Tente novamente. Ou entre em contato conosco para obter assistência.', 'woo-orendapay' ) );
				return array(
					'result' => 'fail'
				);
				
			}			
 
	 	}
		
		
		/**
		 * Displays notifications when the admin has something wrong with the configuration.
		 * @since  1.0.0
		 * @return void
		 */
		protected function admin_notices() 
		{
			if ( is_admin() ) 
			{
				// Checks if token is not empty.
				if ( empty( $this->merchant_id ) ) 
				{
					add_action( 'admin_notices', array( $this, 'merchant_id_missing_message' ) );
				}
				
				// Checks if token is not empty.
				if ( empty( $this->auth_token) ) 
				{
					add_action( 'admin_notices', array( $this, 'auth_token_missing_message' ) );
				}				
				
				// Checks that the currency is supported.
				if ( ! $this->using_supported_currency() ) 
				{
					add_action( 'admin_notices', array( $this, 'currency_not_supported_message' ) );
				}
				
				// Checks that the currency is supported.
				if ( ! $this->using_supported_currency() ) 
				{
					add_action( 'admin_notices', array( $this, 'currency_not_supported_message' ) );
				}

				if(!class_exists('WooCommerce'))
				{
					add_action( 'admin_notices', array( $this, 'plugin_woocommerce_not_instaled' ) );
				}
				
				if(!class_exists('Extra_Checkout_Fields_For_Brazil'))
				{
					add_action( 'admin_notices', array( $this, 'class_wc_brazil_checkout_fields_not_instaled' ) );
				}				
				
			}
		}		
		
		
		/**
		* Adds error message when not configured the token.
		* @since  1.0.0
		* @return string Error Mensage.
		*/
		public function plugin_woocommerce_not_instaled() 
		{
			echo '<div class="error"><p><strong>' . __( 'OrendaPay', 'woo-orendapay' ) . '</strong>: ' . sprintf( __( 'Instale e ative o plugin WooCommerce.', 'woo-orendapay' ), '<a href="plugins.php">' . __( 'Click here to configure!', 'woo-orendapay' ) . '</a>' ) . '</p></div>';
		}	


		/**
		 * Adds error message when not configured the token.
		 * @since  1.0.0
		 * @return string Error Mensage.
		 */
		public function class_wc_brazil_checkout_fields_not_instaled() 
		{
			echo '<div class="error"><p><strong>' . __( 'OrendaPay', 'woo-orendapay' ) . '</strong>: ' . sprintf( __( 'Instale e ative o plugin WooCommerce Extra Checkout Fields for Brazil, busque pelo Plugin Brazilian Market on WooCommerce', 'woo-orendapay' ), '<a href="plugins.php">' . __( 'Click here to configure!', 'woo-orendapay' ) . '</a>' ) . '</p></div>';
		}			
		
		
		/**
		 * Adds error message when not configured the token.
		 * @since  1.0.0
		 * @return string Error Mensage.
		 */
		public function merchant_id_missing_message() 
		{
			echo '<div class="error"><p><strong>' . __( 'OrendaPay', 'woo-orendapay' ) . '</strong>: ' . sprintf( __( 'Você ainda não informou seu Merchant ID de integração. %s', 'woo-orendapay' ), '<a href="' . $this->admin_url() . '">' . __( 'Click here to configure!', 'woo-orendapay' ) . '</a>' ) . '</p></div>';
		}		
		
		
		/**
		 * Adds error message when not configured the token.
		 * @since  1.0.0
		 * @return string Error Mensage.
		 */
		public function auth_token_missing_message() 
		{
			echo '<div class="error"><p><strong>' . __( 'OrendaPay', 'woo-orendapay' ) . '</strong>: ' . sprintf( __( 'Você ainda não informou seu Token de Integração. %s', 'woo-orendapay' ), '<a href="' . $this->admin_url() . '">' . __( 'Click here to configure!', 'woo-orendapay' ) . '</a>' ) . '</p></div>';
		}
 


		/**
		 * Adds error message when an unsupported currency is used.
		 * @since  1.0.0
		 * @return string
		 */
		public function currency_not_supported_message() 
		{
			echo '<div class="error"><p><strong>' . __( 'OrendaPay', 'woo-orendapay') . '</strong>: ' . sprintf( __( 'Currency <code>%s</code> is not supported. Works only with Brazilian Real.', 'woo-orendapay' ), get_woocommerce_currency() ) . '</p></div>';
		}	
		
		
		/**
		 * Returns a bool that indicates if currency is amongst the supported ones.
		 * @since  1.0.0
		 * @return bool
		 */
		protected function using_supported_currency() 
		{
			return ( get_woocommerce_currency() == 'BRL' );
		}		
		
		
		/**
		 * Gets the admin url.
		 * @since  1.0.0
		 * @return string
		 */
		protected function admin_url() 
		{
			if ( version_compare( $this->woocommerce_instance()->version, '2.1', '>=' ) ) 
			{
				return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=orendapay' );
			}
			
			return admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_orendapay_class' );
		}		
		
 
		
		/**
		 * Adds payment instructions on thankyou page.
		 * @since  1.0.0
		 * @param  int    $order_id Order ID.
		 * @return string           Payment instructions.
		 */
		public function thankyou_page( $order_id ) 
		{
			$boleto_codigo = get_post_meta( $order_id, 'orendapay_cod_transacao', true );
			$url = get_post_meta( $order_id, 'orendapay_url_boleto', true );
			$linha_digitavel = get_post_meta( $order_id, 'orendapay_linha_digitavel', true );

			$html = '<div class="woocommerce-message">';
		
			if ( isset( $url ) && ! empty( $url ) ) 
			{
				$html .= sprintf( '<a class="button" href="%s" target="_blank">%s</a>', $url, __( 'Imprimir Boleto Bancário', 'woo-orendapay' ) );
			}
	  
			$message = __( 'Clique no botão e pague o boleto no seu Internet Banking.', 'woo-orendapay' ) . '<br />';
			$message .= __( 'Se preferir, imprima e pague em qualquer agência bancária ou loteria.', 'woo-orendapay' ) . '<br />';
			$message .= __( 'Linha Digitável: '. $linha_digitavel, 'woo-orendapay' ) . '<br />';
			$html .= apply_filters( 'woocommerce_orendapay_thankyou_page_instructions', $message, $order_id );
			$html .= '</div>';
			
			echo $html;
		}
		
			
		/**
		 * Adds payment instructions on customer email.
		 * @since  1.0.0
		 * @param  WC_Order $order         Order data.
		 * @param  bool     $sent_to_admin Sent to admin.
		 * @return string                  Payment instructions.
		 */
		public function email_instructions( $order, $sent_to_admin ) 
		{
			$url = get_post_meta( $order->id, 'orendapay_url_boleto', true );
			$linha_digitavel = get_post_meta( $order->id, 'orendapay_linha_digitavel', true );			
			
			$html = '<h2>' . __( 'Payment', 'woo-orendapay' ) . '</h2>';
			 
			$html .= '<p class="order_details">';
			$message = __( 'Clique no link abaixo e pague o boleto no seu Internet Banking.', 'woo-orendapay' ) . '<br />';
			$message .= __( 'Se preferir, pague em qualquer agência bancária ou loteria.', 'woo-orendapay' ) . '<br />';
			$message .= __( 'Linha Digitável: '.$linha_digitavel, 'woo-orendapay' ) . '<br />';
			
			$html .= apply_filters( 'woocommerce_orendapay_email_instructions', $message, $order );
			
			if ( isset( $url ) && ! empty( $url ) ) 
			{
				$html .= sprintf( '<a class="button" href="%s" target="_blank">%s</a>', $url, __( 'Imprimir Boleto', 'woo-orendapay' ) ) . '<br />';
			}
			
			$html .= '</p>';
			
			echo $html;
		}		
		
		
		/**
		 * Adds payment instructions on View Order
		 * @since  1.0.0
		 * @param  int    $order_id Order ID.
		 * @return string           Payment instructions.
		 */
		public function view_order_custom_payment_instruction( $order ) 
		{
			$boleto_codigo = get_post_meta( $order->id, 'orendapay_cod_transacao', true );
			$url = get_post_meta( $order->id, 'orendapay_url_boleto', true );
			$linha_digitavel = get_post_meta( $order->id, 'orendapay_linha_digitavel', true );

			$html = '<div class="woocommerce-message">';
		
			if ( isset( $url ) && ! empty( $url ) ) 
			{
				$html .= sprintf( '<a class="button" href="%s" target="_blank">%s</a>', $url, __( 'Imprimir Boleto Bancário', 'woo-orendapay' ) );
			}
	  
			$message = __( 'Clique no botão e pague o boleto no seu Internet Banking.', 'woo-orendapay' ) . '<br />';
			$message .= __( 'Se preferir, imprima e pague em qualquer agência bancária ou loteria.', 'woo-orendapay' ) . '<br />';
			$message .= __( 'Linha Digitável: '. $linha_digitavel, 'woo-orendapay' ) . '<br />';
			$html .= apply_filters( 'woocommerce_orendapay_order_details_after_order_table', $message, $order->id );
			$html .= '</div>';
			
			echo $html;
		}
		
		
		/**
		 * Check API Response.
		 * @since  1.0.0
		 * @return void
		*/
		public function check_webhook_notification() 
		{
			$jsonBody = json_decode(file_get_contents('php://input'));
			
			//get values post
			$seu_codigo = trim($jsonBody->seu_codigo);	
			$situacao = trim($jsonBody->situacao);
			$numero = trim($jsonBody->numero);
			
			if(is_null($jsonBody) || $seu_codigo<=0)
				throw new Exception('Falha ao interpretar JSON do webhook: Evento do Webhook não encontrado!');
		  
			header( 'HTTP/1.1 200 OK' );
			
			//Ok, pay!
			if ($situacao=='pago') 
			{
				$order_id = $seu_codigo;
				do_action( 'woocommerce_orendapay_webhook_notification', $order_id );
			}
		}
		
		/**
		 * Successful pay notification.
		 * @since  1.0.0
		 * @param  int    $order_id Order ID.
		 * @return void   Updated the order status to approved.
		 */
		public function successful_webhook_notification($order_id) 
		{
			$order = new WC_Order($order_id);
			$order->add_order_note( __( 'OrendaPay: Pagamento Aprovado.', 'woo-orendapay' ) );
			$order->payment_complete();
		}		
		
		
 	}
}

?>
