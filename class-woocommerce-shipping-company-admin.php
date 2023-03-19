<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       Segmalog.com
 * @since      1.0.0
 *
 * @package    Woocommerce_Shipping_Company
 * @subpackage Woocommerce_Shipping_Company/admin
 */
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woocommerce_Shipping_Company
 * @subpackage Woocommerce_Shipping_Company/admin
 * @author     Segmalog <contact@segmalog.com>
 */
require_once( WOOCOMMERCE_SHIPPING_COMPANY_PATH . '/vendor/autoload.php' );

class Woocommerce_Shipping_Company_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		add_action( 'init', [ $this, 'entity_setup_post_type' ] );
		add_action( 'add_meta_boxes', [ $this, 'global_wsc_companies_meta_box' ] );

		add_action( 'save_post', [ $this, 'wsc_companies_meta_box_save' ] );


		add_action( 'admin_menu', array( $this, 'add_plugins_menu' ) );

		add_action( 'woocommerce_order_status_changed', [ $this, 'action_woocommerce_order_status_changed' ], 10, 4 );

		//  add_filter('bulk_actions-edit-shop_order', [$this, 'custom_status_bulk_actions'], 20, 1);
		// add_filter('handle_bulk_actions-edit-shop_order', [$this, 'custom_status_handle_bulk_action'], 10, 3);

		add_filter( 'admin_footer', [ $this, 'custom_content_footer' ] );

		add_action( "rest_api_init", [ $this, "custom_api_rest" ] );

		add_action( 'manage_shop_order_posts_custom_column', [ $this, 'wc_add_custom_column_order' ], 20, 2 );

		add_filter( 'manage_edit-shop_order_columns', [ $this, 'wc_custom_column_order_header' ], 20 );

		//  add_filter( 'manage_edit-shop_order_sortable_columns', 'my_sortable_shop_order_column' );

		add_action( 'pre_get_posts', [ $this, 'custom_query_post' ] );

		add_filter( 'parse_query', [ $this, 'wisdom_sort_plugins_by_slug' ] );
		add_action( 'restrict_manage_posts', [ $this, 'wpse45436_admin_posts_filter_restrict_manage_posts' ], 20 );

	}

	function wpse45436_admin_posts_filter_restrict_manage_posts() {
		global $pagenow, $wp_query, $post_type;


		$post_typee = isset( $_GET['post_type'] ) ? $_GET['post_type'] : $post_type;

		//only add filter to post type you want
		if ( is_admin() && 'edit.php' === $pagenow && 'shop_order' === $post_typee ) {
			?>
            <input name="filter_date_range_order" id="filter_date_range_order" class="filter_date_range_order "
                   value="<?php echo ( isset( $_GET['filter_date_range_order'] ) &&
			                           $_GET['filter_date_range_order'] != '' ) ? $_GET['filter_date_range_order'] : '' ?>"
                   type="text" placeholder="Select Date..">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css"/>

            <script src="https://cdn.jsdelivr.net/npm/litepicker/dist/litepicker.js"></script>

            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    const picker = new Litepicker({
                        element: document.getElementById('filter_date_range_order'),
                        singleMode: false,
                        delimiter: ' to '
                    });
                });
            </script>


			<?php


			if ( isset( $_GET['filter_date_range_order'] ) ) {
				echo ' <span style="color: #124964">' . $wp_query->post_count . ' éléments</span>';
			}


		}

	}

	function wisdom_sort_plugins_by_slug( $query ) {
		global $pagenow;

		// Get the post type
		$post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';
		if ( is_admin() && $pagenow == 'edit.php' && isset( $query->query['post_type'] ) && $query->query['post_type'] == 'shop_order' ) {


			if ( isset( $_GET['filter_date_range_order'] ) && $_GET['filter_date_range_order'] != '' ) {

				$array_date_range = explode( 'to', $_GET['filter_date_range_order'] );

				$end = isset( $array_date_range[1] ) ? trim( $array_date_range[1] ) : trim( $array_date_range[0] );

				$start_date = new DateTime( trim( $array_date_range[0] ) );
				$end_date   = new DateTime( $end );


//                $query->query_vars['meta_query'] = array(
//                    array(
//                        'key' => '_order_date_pickup',
//                        'value' => array($array_date_range[0], $end),
//                        'compare' => 'BETWEEN',
//                        'type' => 'DATE'
//                    )
//                );
				$query->query_vars['date_query'] = array(
					array(
						'after'     => array(
							'year'  => $start_date->format( 'Y' ),
							'month' => $start_date->format( 'm' ),
							'day'   => $start_date->format( 'd' ),
						),
						'before'    => array(
							'year'  => $end_date->format( 'Y' ),
							'month' => $end_date->format( 'm' ),
							'day'   => $end_date->format( 'd' ),
						),
						'inclusive' => true,
					),
				);
			}


		}


	}

	function custom_query_post( $query ) {

		global $post_type, $pagenow;

		$this_post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : $post_type;
		$this_status    = isset( $_GET['post_status'] ) ? $_GET['post_status'] : '';

		if ( is_admin() && $pagenow == 'edit.php' && isset( $query->query['post_type'] ) && $query->query['post_type'] == 'shop_order' ) {


			if ( $this_status == 'wc-manifest' ) {
				$query->set( 'meta_key', '_order_date_manifest' );
				$query->set( 'orderby', 'meta_value' );
			}
			if ( $this_status == 'wc-pick-up' ) {
				$query->set( 'meta_key', '_order_date_pickup' );
				$query->set( 'orderby', 'meta_value' );
			}
		}
	}


	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles( $hook ) {

		global $post_type;

		if ( $hook == 'wsc-companies_page_manage-company-payment' || $hook == 'wsc-companies_page_manage-company-history-payment' ) {


			wp_enqueue_style( 'jquery-ui-css', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
			wp_enqueue_style( 'daterangepicker', WOOCOMMERCE_SHIPPING_COMPANY_URL .
			                                     'assets/css/daterangepicker.min.css' );


			wp_enqueue_style( 'zebra_dialog', WOOCOMMERCE_SHIPPING_COMPANY_URL .
			                                  'assets/css/zebra_dialog.min.css' );


			wp_enqueue_style( 'jsuites', 'https://cdn.jsdelivr.net/npm/jsuites@2.8.0/dist/jsuites.min.css' );
			wp_enqueue_style( 'jexcel', 'https://cdnjs.cloudflare.com/ajax/libs/jexcel/4.3.0/jexcel.min.css' );

		}


		if ( ! wp_style_is( 'materialdesignicons' ) ) {
			wp_enqueue_style( 'materialdesignicons', 'https://cdn.jsdelivr.net/npm/@mdi/font@5.8.55/css/materialdesignicons.min.css' );
		}
		if ( ! wp_style_is( 'vuetify' ) ) {
			wp_enqueue_style( 'vuetify', WOOCOMMERCE_SHIPPING_COMPANY_URL .
			                             'assets/css/vuetify.min.css', array(), '', 'all' );
		}

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/company-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts( $hook ) {

		global $wp_scripts, $wp_styles, $post_type;


		if ( $hook == 'wsc-companies_page_manage-company-payment' || $hook == 'wsc-companies_page_manage-company-history-payment' ) {


			wp_enqueue_script( 'moment-script', 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js' );
			wp_enqueue_script( 'daterangepicker-script', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-date-range-picker/0.20.0/jquery.daterangepicker.min.js', array( 'jquery' ) );


			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-datepicker' );


			wp_enqueue_script( 'jsuites', 'https://cdn.jsdelivr.net/npm/jsuites@2.8.0/dist/jsuites.min.js' );
			wp_enqueue_script( 'jexcel', 'https://cdnjs.cloudflare.com/ajax/libs/jexcel/4.3.0/jexcel.min.js' );


			wp_enqueue_script( 'sweetalert', '//cdn.jsdelivr.net/npm/sweetalert2@11' );
			wp_enqueue_script( 'jquery.loading', WOOCOMMERCE_SHIPPING_COMPANY_URL . 'assets/js/jquery.loading.min.js' );

			wp_enqueue_script( 'zebra_dialog.', WOOCOMMERCE_SHIPPING_COMPANY_URL . 'assets/js/zebra_dialog.min.js' );

			wp_enqueue_script( 'payment-script', plugin_dir_url( __FILE__ ) . 'js/company-payment.js',
				array( 'jquery' ), $this->version, false );


			wp_add_inline_script( 'payment-script', 'const paymmentdata = ' . json_encode( array(
					'nonce'        => wp_create_nonce( 'wp_rest' ),
					'listeCompany' => get_ship_company(),
				) ) );
		}


		if ( ! wp_script_is( 'vuejs' ) ) {
			wp_enqueue_script( 'vuejs', WOOCOMMERCE_SHIPPING_COMPANY_URL .
			                            'assets/js/vue.js', [], $this->version, false );
		}
		if ( ! wp_script_is( 'vuetify' ) ) {
			wp_enqueue_script( 'vuetify', WOOCOMMERCE_SHIPPING_COMPANY_URL .
			                              'assets/js/vuetify.js', [], $this->version, false );
		}


		wp_enqueue_script( 'company-script', plugin_dir_url( __FILE__ ) . 'js/company-script.js?v=' . strval( microtime( true ) ),
			array( 'jquery' ), $this->version, false );


		wp_add_inline_script( 'company-script', 'const companydata = ' . json_encode( array(
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'listeCompany' => get_ship_company(),
				'post_type'    => isset( $_GET['post_type'] ) ? $_GET['post_type'] : '',
				'post_status'  => isset( $_GET['post_status'] ) ? $_GET['post_status'] : '',
			) ) );


		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/company-admin.js?v=' . strval( microtime( true ) ),
			array( 'jquery' ), $this->version, false );

		wp_add_inline_script( $this->plugin_name, 'const jsdata = ' . json_encode( array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'site_url'    => site_url(),
				'post_status' => isset( $_GET['post_status'] ) ? $_GET['post_status'] : '',
			) ) );
	}


	function custom_api_rest() {
		register_rest_route( 'globalapi/v2', '/process_company_shipping', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'process_company_shipping' ],
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		] );
	}

	function custom_content_footer() {
		include 'partials/company-popup-block.php';

		include 'partials/company-track-order.php';
		include 'partials/company-print-orders.php';
		include 'partials/company-bulk-orders.php';
	}


	function wc_custom_column_order_header( $columns ) {

		$new_columns = array();
		if ( is_admin() ) {
			foreach ( $columns as $column_name => $column_info ) {

				$new_columns[ $column_name ] = $column_info;

				if ( 'order_number' === $column_name ) {
					$new_columns['order_ship_number'] = __( 'Ship Number', 'fashio' );
					$new_columns['custom_note_order'] = __( 'Note', 'fashio' );
				}

				if ( 'order_date' === $column_name ) {
					$new_columns['order_date_pickup'] = __( 'Pickup Date', 'fashio' );
				}
			}

			return $new_columns;
		}

		return $columns;
	}

	function wc_add_custom_column_order( $column, $post_id ) {
		$order = wc_get_order( (int) $post_id );

		if ( 'order_number' === $column ) {

			echo '</br><strong class="custom_billing_phone">' . $order->get_billing_phone() . '</strong>';
		}

		if ( 'order_status' === $column ) {

			$_order_date_manifest = get_post_meta( $post_id, '_order_date_manifest', true );

			if ( $_order_date_manifest != '' ) {
				echo '</br><small>Date Manifest: ' . $_order_date_manifest . '</small>';
			}

		}
		if ( 'order_date_pickup' === $column ) {
			$_order_date_pickup = get_post_meta( $post_id, '_order_date_pickup', true );

			if ( $_order_date_pickup != '' ) {
				$date_pickup = new DateTime( $_order_date_pickup );
				setlocale( LC_TIME, "fr_FR" );
				echo utf8_encode( strftime( "%d %b %G", strtotime( $_order_date_pickup ) ) );
			}
		}


		if ( 'order_ship_number' === $column ) {

			$ship_number        = get_post_meta( $post_id, '_shipment_number_id', true );
			$tentative_number   = get_post_meta( $post_id, '_order_tentative_number', true );
			$data_track         = get_post_meta( $post_id, '_order_date_track', true );
			$ship_company_label = get_post_meta( $post_id, '_shipment_company_label', true );

			if ( $ship_number && ! empty( $ship_number ) ) {

				echo '<p>' . $ship_company_label . '</p>';
				echo '<a data-id="' . $post_id . '"  data-order-track-number="' . $ship_number .
				     '" href="#" class="button btn_order_ship_number_tracking">Voir</a> ';
				//echo '<a data-order-id="' . $post->ID . '" href="#" class="button btn_order_ship_number_edit">Modifier</a> ';
				echo $ship_number;
				if ( $data_track && $data_track != '' ) {
					echo '<br>';
					echo $data_track;
				}
				if ( get_post_meta( $post_id, '_billing_echange', true ) != '' ) {
					echo 'Echange';
				}

				if ( $tentative_number != '' && $tentative_number != 0 ) {
					echo '<br><span class="badge-tentative">Tentative: <span class="tentative">' . $tentative_number .
					     '</span></span>';
				}
			} else {
				//echo '<a data-order-id="' . $post_id . '" href="#" class="button btn_order_ship_number">Ajouter</a> ';
			}
		}
		if ( 'custom_note_order' === $column ) {

			$_custom_note_order = get_post_meta( $post_id, '_custom_note_order', true );
			echo '<strong class="custom_billing_phone">' . $_custom_note_order . '</strong>';
		}
	}

	function action_woocommerce_order_status_changed( $order_id, $old_status, $new_status ) {


		$old_status = str_replace( ' ', '', $old_status );
		$new_status = str_replace( ' ', '', $new_status );

		$order = wc_get_order( $order_id );

		if ( get_post_meta( $order_id, '_order_date_pickup', true ) == '' ) {
			if ( $new_status == 'pick-up' ) {
				update_post_meta( $order_id, '_order_date_pickup', date( "Y-m-d" ) );
			}
		}


		if ( $new_status == 'manifest' ) {
			update_post_meta( $order_id, '_order_date_manifest', date( "Y-m-d H:i:s" ) );
		}


		if ( $new_status == 'retour' ) {
			update_post_meta( $order_id, '_order_date_returned_ok', date( "Y-m-d" ) );
		}

		if ( $new_status == 'pick-up' ) {

			$upload_dir = wp_upload_dir();
			$path       = $upload_dir['basedir'] . '/wsc_files_orders/orders/' . $order_id;

			delete_folder_pdf( $path . '/*' );
			rmdir( $path );
		}
	}

	function add_plugins_menu() {
		add_submenu_page(
			'edit.php?post_type=wsc-companies',
			__( 'Paiement', $this->plugin_name ),
			__( 'Paiement', $this->plugin_name ),
			'manage_options',
			'manage-company-payment',
			array( $this, 'wsc_process_payment' )
		);

		add_submenu_page(
			'edit.php?post_type=wsc-companies',
			__( 'Historique Paiement', $this->plugin_name ),
			__( 'Historique Paiement', $this->plugin_name ),
			'manage_options',
			'manage-company-history-payment',
			array( $this, 'wsc_process_history_payment' )
		);

//         add_submenu_page(
//                'edit.php?post_type=wsc-companies',
//                __('Statistics', $this->plugin_name),
//                __('Statistics', $this->plugin_name),
//                'manage_options',
//                'manage-company-statistics',
//                array($this, 'wsc_process_statistics')
//        );

		add_submenu_page(
			'edit.php?post_type=wsc-companies',
			__( 'Statistics', $this->plugin_name ),
			__( 'Statistics', $this->plugin_name ),
			'manage_options',
			'manage-company-statistics-pro',
			array( $this, 'wsc_process_statistics_pro' )
		);
	}

	function wsc_process_payment() {
		include 'partials/payment/main.php';
	}

	function wsc_process_history_payment() {
		include 'partials/payment/history.php';
	}

	function wsc_process_statistics() {
		include 'partials/statistics/main.php';
	}

	function wsc_process_statistics_pro() {
		include 'partials/statistics/new_main.php';
	}

	function write_log ( $log )  {
		if ( is_array( $log ) || is_object( $log ) ) {
			error_log( print_r( $log, true ) );
		} else {
			error_log( $log );
		}
	}

	function process_company_shipping( $request ) {

		$params = $request->get_params();

		$func = $params['function'];

		if ( $func == 'create-position' ) {

			$order_id = $params['orderData']['id'];

			$shipping_company = $params['formData']['shipping_company'];

			if ( $shipping_company['company_slug'] == 'aramex' ) {//Aramex
				$data = $this->create_aramex_shipping_position( (int) $order_id, $params, 'default' );
			} else {
				$data = $this->create_company_shipping_position( (int) $order_id, $params, 'default' );
			}
		}

		if ( $func == 'bulk-position' ) {
			$shipping_company = $params['company']['company_label'];
			if ( $shipping_company == 'Aramex' ) {//Aramex
				$data = $this->create_bulk_shipping_position_aramex( $params );
			} else {
				$data = $this->create_bulk_shipping_position( $params );
			}

		}
		if ( $func == 'track' ) {
			$order_id = $params['orderData']['id'];
			$data     = $this->order_status_track_api( $order_id );
		}

		if ( $func == 'repport' ) {

			$data = $this->print_manifest_company_shipping( $params['orders'], $params['company'] );
		}

		if ( $func == 'label_bl' ) {

			$data = $this->print_label_company_shipping( $params['orders'], $params['company'] );
		}


		return $data;
	}

	function create_bulk_shipping_position( $params ) {

		$order_id   = $params['orderData']['order_id'];
		$company_id = $params['company']['company_id'];

		$order = wc_get_order( (int) $order_id );

		$ship_id = get_post_meta( (int) $order_id, '_shipment_number_id', true );

		if ( $order && $order->get_status() == 'en-cours' && $ship_id == '' ) {

			$wsc_company_end_point = get_post_meta( (int) $company_id, 'wsc_company_end_point', true );

			$params['formData']['shipping_company'] = $params['company'];

			$params['userData'] = [
				'first_name' => $order->get_billing_first_name(),
				'address'    => $order->get_billing_address_1(),
				'phone'      => $order->get_billing_phone(),
				'user_email' => $order->get_billing_email(),
				'city'       => $order->get_billing_city(),
			];

			$params['formData']['echange_contenu']  = $params['orderData']['echange'];
			$params['formData']['shipping_nbr_pcs'] = $params['orderData']['nbr_pcs'];
			$resp                                   = $this->create_company_shipping_position( $order_id, $params, 'default' );

			return $resp;

		} else {
			return [
				'resp' => 'error',
				'msg'  => 'Déja traité!'
			];
		}

		return $resp;
	}

	function create_bulk_shipping_position_aramex( $params ) {

		$order_id   = $params['orderData']['order_id'];
		$company_id = $params['company']['company_id'];

		$order = wc_get_order( (int) $order_id );

		$ship_id = get_post_meta( (int) $order_id, '_shipment_number_id', true );

		if ( $order && $order->get_status() == 'en-cours' && $ship_id == '' ) {

			$params['formData']['shipping_company'] = $params['company'];

			$params['userData'] = [
				'first_name' => $order->get_billing_first_name(),
				'address'    => $order->get_billing_address_1(),
				'phone'      => $order->get_billing_phone(),
				'user_email' => $order->get_billing_email(),
				'city'       => $order->get_billing_city(),
			];

			$params['formData']['echange_contenu']  = $params['orderData']['echange'];
			$params['formData']['shipping_nbr_pcs'] = $params['orderData']['nbr_pcs'];
			$resp                                   = $this->create_aramex_shipping_position( $order_id, $params, 'default' );

			return $resp;

		} else {
			return [
				'resp' => 'error',
				'msg'  => 'Déja traité!'
			];
		}

		return $resp;
	}

	function order_status_track_api( $order_id ) {
		$html        = '';
		$company     = get_post_meta( (int) $order_id, '_shipment_company_name', true );
		$ship_number = get_post_meta( (int) $order_id, '_shipment_number_id', true );


		$data_company = get_page_by_path( $company, OBJECT, 'wsc-companies' );

        if($company == 'aramex')
        {
            $ship_numbers_array = [$ship_number];
	        $aramex_data = $this->get_track_by_ship_number_aramex( $ship_numbers_array );
	        $aramex_data['company'] = 'Aramex';
	        $aramex_data['evenements'] = $aramex_data['track_data'];
	        return $aramex_data;
        }
        else {
	        $data_track = json_decode( company_get_action( 'tracking_position', [ $ship_number ], false,
		        get_post_meta( $data_company->ID, 'wsc_company_end_point', true ) ) );
	        if ( $data_track->livraison == null ) {
		        return 'error';
	        }
	        return $data_track;
        }
	}

	function get_track_by_ship_number_aramex( $ship_number = array() ) {
		$path         = WP_CURRENT_THIS_PLUGIN_URL . 'admin/aramex-api';
		$company_data = get_page_by_path( 'aramex', OBJECT, 'wc-company' );
		$data         = [];

		$soapClient = new SoapClient( $path . '/Tracking.wsdl' );

		$account_number = '171083';
		$account_pin    = '321321';

		$company_username = 'fashio.tn@gmail.com';
		$company_password = 'Qsdcxw@123';


		$params = array(
			'ClientInfo'  => array(
				'AccountCountryCode' => 'TN',
				'AccountEntity'      => 'TUN',
				'AccountNumber'      => $account_number,
				'AccountPin'         => $account_pin,
				'UserName'           => $company_username,
				'Password'           => $company_password,
				'Version'            => 'v1.0'
			),
			'Transaction' => array(),
			'Shipments'   => $ship_number
		);

		$auth_call = '';
		// calling the method and printing results
		try {

			$auth_call = $soapClient->TrackShipments( $params );
			// d($auth_call);
			if ( ! $auth_call->HasErrors ) {

				if ( is_array( $ship_number ) && count( $ship_number ) == 1 ) {
					foreach ( $auth_call->TrackingResults as $OneTracking ) {
						$data = array(
							'track_number' => $OneTracking->Key,
							'track_data'   => $OneTracking->Value->TrackingResult
						);
					}
				} else {

					foreach ( $auth_call->TrackingResults as $OneTracking ) {

						foreach ( $OneTracking as $resultTracking ) {
							$data[] = array(
								'track_number' => $resultTracking->Key,
								'track_data'   => $resultTracking->Value->TrackingResult
							);
						}
					}
				}

			}
		} catch ( SoapFault $fault ) {
			// echo "TRY FAILED";
			die( 'Error : ' . $fault->faultstring );
		}


		if ( $data && $data['track_data'] instanceof stdClass ) {
			$data['track_data'] = array(
				0 => $data['track_data']
			);
		}

		return $data;


	}


	function print_label_company_shipping( $orders, $company ) {

		$x          = date( 'Ymdhi' );
		$url_labels = [];

		$upload_dir = wp_upload_dir();
		$path       = $upload_dir['basedir'] . '/wsc_files_orders/merged';
		if ( ! file_exists( $path ) ) {
			mkdir( $path, 0777, true );
		}

		delete_folder_pdf( $path . '/*' );

		$problemOrder = [];

		foreach ( $orders as $key => $order_id ) {
//            if ($this->order_status_track_api($order_id) == 'error') {
//                $problemOrder[] = $order_id;
//                unset($orders[$key]);
//            } else {
			$order_label = $upload_dir['basedir'] . '/wsc_files_orders/orders/' . $order_id . '/order_label.pdf';
			$order_bl    = $upload_dir['basedir'] . '/wsc_files_orders/orders/' . $order_id . '/order_bl.pdf';


			if ( file_exists( $order_label ) ) {
				$url_labels[] = $order_label;
			} else {
				$resp         = $this->generate_company_label( wc_get_order( (int) $order_id ), $company );
				$url_labels[] = $order_label;
			}
			if ( file_exists( $order_bl ) ) {
				$url_labels[] = $order_bl;
			}
		}
//        }


		if ( ! empty( $url_labels ) ) {
			$pdf = new \Jurosh\PDFMerge\PDFMerger;
			foreach ( $url_labels as $pdfDocument ) {
				$pdf->addPDF( $pdfDocument, 'all' );
			}
			$pdf->merge( 'file', $path . '/merged_label_livraison_' . $x . '.pdf' );
		}

		return [
			'orders'  => $orders,
			'problem' => $problemOrder,
			'url'     => WP_CONTENT_URL . '/uploads/wsc_files_orders/merged/merged_label_livraison_' . $x . '.pdf'
		];
	}

	function generate_company_label( $order, $company ) {

		$error = [];

		//create folder for order pdfs if not

		$upload_dir = wp_upload_dir();
		$path       = $upload_dir['basedir'] . '/wsc_files_orders/orders/' . $order->get_id();
		if ( ! file_exists( $path ) ) {
			mkdir( $path, 0777, true );
		}


		$shipment_id = get_post_meta( $order->get_id(), '_shipment_number_id', true );
		if ( $shipment_id && $shipment_id != '' ) {

			$params = [
				'TOKEN'      => get_post_meta( $company['company_id'], 'wsc_company_token', true ),
				'POSBARCODE' => $shipment_id
			];
			$label  = company_get_action( 'get_label', $params, true,
				get_post_meta( $company['company_id'], 'wsc_company_end_point', true ) );

			if ( strpos( $label, 'INVALID' ) !== false ) {
				$error[] = [
					'resp' => 'error',
					'msg'  => $label
				];
			} else {

				$this->print_bl_company_shipping( $order, $company, $path );
				$path = $path . '/order_label.pdf';
				file_put_contents( $path, $label );
			}
//             
		}

		if ( ! empty( $error ) ) {
			return $error;
		}


		return 'success';
	}

	function print_manifest_company_shipping( $orders, $company ) {


		$problemOrder = [];
//        foreach ($orders as $key => $order_idd) {
//            if ($this->order_status_track_api($order_idd) == 'error') {
//                $problemOrder[] = $order_idd;
//                unset($orders[$key]);
//            }
//        }

		if ( ! empty( $orders ) ) {
			$x = date( 'Ymdhi' );

			$upload_dir = wp_upload_dir();
			$path       = $upload_dir['basedir'] . '/wsc_files_orders/manifest_repport';
			$path_tmp   = $upload_dir['basedir'] . '/wsc_files_orders/mpdf_tmp';
			if ( ! file_exists( $path ) ) {
				mkdir( $path, 0777, true );
			}
			if ( ! file_exists( $path_tmp ) ) {
				mkdir( $path_tmp, 0777, true );
			}
			$mpdf = new \Mpdf\Mpdf( [
				'mode'                => 'utf-8',
				'tempDir'             => $path_tmp,
				'setAutoTopMargin'    => 'stretch',
				'setAutoBottomMargin' => 'stretch'
			] );

			$mpdf->autoLangToFont   = true;
			$mpdf->autoScriptToLang = true;
			$mpdf->baseScript       = 1;
			$mpdf->autoArabic       = true;

			$mpdf->packTableData = true;

			$k = 0;


			$logo          = get_post_meta( (int) $company['company_id'], 'wsc_company_logo', true );
			$company_title = get_the_title( (int) $company['company_id'] );


			$html = '';
			ob_start();
			include 'partials/template/template_manifest_report.php';
			$html .= ob_get_clean();


			$mpdf->WriteHTML( $html );


			delete_folder_pdf( $path . '/*' );


			$mpdf->Output( $path . '/repport_' . $x . '.pdf', 'F' );
		}

		return
			[
				'orders'  => $orders,
				'problem' => $problemOrder,
				'url'     => WP_CONTENT_URL . '/uploads/wsc_files_orders/manifest_repport/repport_' . $x . '.pdf'
			];
	}

	function print_bl_company_shipping( $order, $company, $path ) {

		$x = time();

		//delete_folder_pdf(ABSPATH . 'manifest/company/livraison/*');
		$k = 0;


		if ( $order ) {

			$ship_number = get_post_meta( $order->get_id(), '_shipment_number_id', true );
			$company_id  = (int) $company['company_id'];


			$num        = get_post_meta( $order->get_id(), '_order_num_bl', true );
			$order_date = get_post_meta( $order->get_id(), '_order_date_bl', true );
			if ( $num != '' ) {
				$dd = $num;
			} else {
				$num = get_option( '_global_num_bl' );
				if ( $num != '' ) {
					$dd = $num;
				} else {
					$num = date( 'Y' ) . '00001';
				}

				update_post_meta( $order->get_id(), '_order_num_bl', $num );
				update_post_meta( $order->get_id(), '_order_date_bl', date( 'Y-m-d' ) );
				update_option( '_global_num_bl', $num + 1 );
			}

			$upload_dir = wp_upload_dir();
			$path_tmp   = $upload_dir['basedir'] . '/wsc_files_orders/mpdf_tmp';

			if ( ! file_exists( $path_tmp ) ) {
				mkdir( $path_tmp, 0777, true );
			}

			$mpdf = new \Mpdf\Mpdf( [
				'mode'                => 'utf-8',
				'tempDir'             => $path_tmp,
				'setAutoTopMargin'    => 'stretch',
				'setAutoBottomMargin' => 'stretch'
			] );

			$mpdf->autoLangToFont   = true;
			$mpdf->autoScriptToLang = true;
			$mpdf->baseScript       = 1;
			$mpdf->autoArabic       = true;

			$mpdf->packTableData = true;


			$html = '';
			ob_start();
			include 'partials/template/template_bl.php';
			$html .= ob_get_clean();


			$mpdf->WriteHTML( $html );


			$mpdf->Output( $path . '/order_bl.pdf', 'F' );
		}
	}

	function create_company_shipping_position( $order_id, $data, $default = '', $oldorderid = '' ) {

		$company_id = $data['formData']['shipping_company']['company_id'];


		//return $upload_dir['basedir'];
		if ( ! $company_id ) {
			return;
		}


		$company_data = array_combine( array_keys( get_post_meta( $company_id ) ), array_column( get_post_meta( $company_id ), '0' ) );

//return $company_data;

		$order                 = wc_get_order( (int) $order_id );
		$wsc_company_end_point = $company_data['wsc_company_end_point'];


		//  return replace_special_carac('sdfsdfdsf');
		if ( $wsc_company_end_point != '' ) {


			$params = [
				'TOKEN'                        => $company_data['wsc_company_token'],
				'ENL_CONTACT_NOM'              => $company_data['wsc_enl_contact_nom'],
				'ENL_CONTACT_PRENOM'           => '',
				'ENL_ADRESSE'                  => $company_data['wsc_enl_adress'],
				'ENL_PORTABLE'                 => $company_data['wsc_enl_portable'],
				'ENL_MAIL'                     => $company_data['wsc_enl_mail'],
				'ENL_CODE_POSTAL'              => $company_data['wsc_enl_code_postal'],
				'LIV_CONTACT_NOM'              => replace_special_carac( $data['userData']['first_name'] ),
				'LIV_CONTACT_PRENOM'           => '',
				'LIV_ADRESSE'                  => replace_special_carac( $data['userData']['address'] ),
				'LIV_PORTABLE'                 => $data['userData']['phone'],
				'LIV_MAIL'                     => replace_special_carac( $data['userData']['user_email'] ),
				'LIV_CODE_POSTAL'              => get_post_code_gov( $data['userData']['city'] ),
				'POIDS'                        => 1,
				'VALEUR'                       => (string) str_replace( '.', ',', $order->get_subtotal() ),
				'COD'                          => (string) str_replace( '.', ',', $order->get_total() ),
				'RTRNCONTENU'                  => ( isset( $data['formData']['echange_contenu'] ) && trim( $data['formData']['echange_contenu'] ) != '' ) ? trim( $data['formData']['echange_contenu'] ) : '',
				'POSNBPIECE'                   => $data['formData']['shipping_nbr_pcs'],
				'DATE_ENLEVEMENT'              => date( 'd/m/Y' ),
				'POSITION_TIME_LIV_DISPO_FROM' => '',
				'POSITION_TIME_LIV_DISPO_TO'   => '',
				'REFERENCE'                    => replace_special_carac( get_product_label( $order ) ),
				'DATE_LIVRAISON'               => date( "d/m/Y", strtotime( date( 'm/d/Y', strtotime( date( 'm/d/Y' ) . "+1 days" ) ) ) ),
				'MR_CODE'                      => 'ESP',
				'account_number'               => $company_data['wsc_account_number'],
				'account_pin'                  => $company_data['wsc_account_pin'],
				'aramex_mode'                  => $company_data['wsc_aramex_mode']
			];


			if ( trim( get_post_meta( $order->get_id(), '_shipment_number_id', true ) ) == '' ) {

				$shippement_id = company_get_action( 'pos_create', $params, true, $wsc_company_end_point );

				$shippement_id = str_replace( '"', '', $shippement_id );
				if ( $shippement_id != '' && is_numeric( $shippement_id ) && strlen( $shippement_id ) >= 12 ) {

					update_post_meta( $order->get_id(), '_shipment_number_id', $shippement_id );

					update_post_meta( $order->get_id(), '_shipment_company_name',
						$data['formData']['shipping_company']['company_slug'] );

					update_post_meta( $order->get_id(), '_shipment_company_label',
						$data['formData']['shipping_company']['company_label'] );

					$order->update_status( 'manifest', 'Nouvelle livraison ' .
					                                   $data['formData']['shipping_company']['company_label'] . ' #' . $shippement_id );


					update_post_meta( $order->get_id(), '_shipping_nbr_pcs',
						$data['formData']['shipping_nbr_pcs'] );

					update_post_meta( $order->get_id(), '_echange_contenu',
						$data['formData']['echange_contenu'] );


					///get bouredearau label
					$this->generate_company_label( $order, $data['formData']['shipping_company'] );

					return [

						'resp' => 'success',
						'msg'  => $shippement_id,
					];
				} else {
					return [
						'msg'  => $shippement_id,
						'resp' => 'error',

					];
				}
			} else {
				return 'Commande possède déja une numéro de livraison!';
			}

			return [
				'msg'  => $shippement_id,
				'resp' => $shippement_id
			];
		} else {

		}
	}

    /******************* aramex *****************/
	function create_aramex_shipping_position($order_id, $formdata)
	{
		$gouv = array('Ariana' => 'Ariana', 'Béja' => 'Beja', 'Ben Arous' => 'Ben Arous', 'Bizerte' => 'Bizerte', 'Gabès' => 'Gabes', 'Gafsa' => 'Gafsa', 'Jendouba' => 'Jandouba',
		              'Kairouan' => 'Kairouan', 'Kasserine' => 'Kasserine', 'Kébili' => 'Kebili', 'Kef' => 'Le Kef', 'Mahdia' => 'Mahdia', 'Mannouba' => 'Mannouba', 'Médenine' => 'Medenine', 'Monastir' => 'Monastir',
		              'Nabeul' => 'Nabeul', 'Sfax' => 'Sfax', 'Sidi Bouzid' => 'Sidi Bouzid', 'Siliana' => 'Siliana', 'Sousse' => 'Sousse', 'Tataouine' => 'Tataouine', 'Tozeur' => 'Tozeur', 'Tunis' => 'Tunis', 'Zaghouan' => 'Zaghouan');

		$order = wc_get_order($order_id);

		$account_number = '171083';
		$account_pin = '321321';

		$company_username = 'fashio.tn@gmail.com';
		$company_password = 'Qsdcxw@123';

		$account_entity = 'TUN';
		$account_country_code = 'TN';

		$shipper_city = 'Medenine';
		$reciver_city = $gouv[$order->get_billing_city()];
		$consige_phone = '216' . $order->get_billing_phone();

		$source = 52;
		$path = WP_CURRENT_THIS_PLUGIN_URL . 'admin/aramex-api';

		$soapClient = new SoapClient($path . '/shipping-services-api-wsdl.wsdl');

		$itemDetails = [];
		$items = $order->get_items();

		foreach ( $items as $itemvv ) {

			$p = wc_get_product( $itemvv->get_product_id() );

			array_push( $itemDetails, [
				'Quantity'         => $itemvv->get_quantity(),
				'Weight'           => [
					'Value' => '',
					'Unit'  => 'kg'
				],
				'GoodsDescription' => replace_special_carac( ( $p && $p->get_sku() != '' ) ? $p->get_sku() : $itemvv->get_name() ) . '(' . $itemvv->get_quantity() . ')',
				'CustomsValue'     => [
					'Value'        => $itemvv->get_total() <= 0 ? 0 : $itemvv->get_total(),
					'CurrencyCode' => 'TND'
				]
			] );
		}

		$descriptionOfGoods = mb_substr(get_product_label($order), 0, 65);

		$params = array();
		//shipper parameters
		$params['Shipper'] = array(
			'Reference1' => $order_id, //'ref11111',
			'Reference2' => '',
			'AccountNumber' => $account_number,
			'AccountPin' => $account_pin,
			//Party Address
			'PartyAddress' => array(
				'Line1' => 'Mellita Houmt Souk Djerba',
				'Line2' => '',
				'Line3' => '',
				'City' => $shipper_city, //'Dubai',
				'StateOrProvinceCode' => '', //'',
				'PostCode' => '4115',
				'CountryCode' => 'TN' //'AE'
			),
			//Contact Info
			'Contact' => array(
				'Department' => '',
				'PersonName' => 'Fashio.tn' ,
				'Title' => '',
				'CompanyName' => 'Fashio.tn',
				'PhoneNumber1' => '58407470',
				'PhoneNumber1Ext' => '',
				'PhoneNumber2' => '',
				'PhoneNumber2Ext' => '',
				'FaxNumber' => '',
				'CellPhone' => '58407470',
				'EmailAddress' => $company_username,
				'Type' => ''
			),
		);

		$params['Consignee'] = array(
			'Reference1' => $order_id,
			'Reference2' => '',
			'AccountNumber' => $account_number,
			'AccountPin' => $account_pin,
			//Party Address
			'PartyAddress' => array(
				'Line1' => replace_special_carac($order->get_billing_address_1()),
				'Line2' => '',
				'Line3' => '',
				'City' => $reciver_city,
				'StateOrProvinceCode' => '',
				'PostCode' => '',
				'CountryCode' => 'TN',
			),
			//Contact Info
			'Contact' => array(
				'Department' => '',
				'PersonName' => replace_special_carac($order->get_billing_first_name()),
				'Title' => '',
				'CompanyName' => replace_special_carac($order->get_billing_first_name()),
				'PhoneNumber1' => $consige_phone,
				'PhoneNumber1Ext' => '',
				'PhoneNumber2' => '',
				'PhoneNumber2Ext' => '',
				'FaxNumber' => '',
				'CellPhone' => $consige_phone,
				'EmailAddress' => $order->get_billing_email() != '' ? $order->get_billing_email() : 'client.aramex@gmail.com',
				'Type' => ''
			)
		);

		$services = '';

		if (isset($formdata['echange_contenu']) && trim($formdata['echange_contenu']) != '') {
			if ($order->get_total() > 0) {
				$services .= 'CODS';
			}
			$services .= ',RTRN';
		} else {
			$services = 'CODS';
		}

		///// add COD end
		// Other Main Shipment Parameters
		$params['ForeignHAWB'] = '';
		$params['Reference1'] = $order_id; //'Shpt0001';
		$params['Reference2'] = '';
		$params['Reference3'] = '';
		$params['ForeignHAWB'] = '';
		$params['TransportType'] = 0;
		$params['ShippingDateTime'] = time();
		$params['DueDate'] = time() + (7 * 24 * 60 * 60);
		$params['PickupLocation'] = 'Reception';
		$params['PickupGUID'] = '';
		$params['Comments'] = '';
		$params['AccountingInstrcutions'] = '';
		$params['OperationsInstructions'] = '';
		$params['Details'] = array(
			'Dimensions' => array(
				'Length' => '0',
				'Width' => '0',
				'Height' => '0',
				'Unit' => 'cm'
			),
			'ActualWeight' => array(
				'Value' => 0.5,
				'Unit' => 'kg'
			),
			'ProductGroup' => 'DOM',
			//'EXP',
			'ProductType' => 'ONP',
			//,'PDX'
			'PaymentType' => 'P',
			'PaymentOptions' => '',
			'Services' => $services,
			'NumberOfPieces' => (trim($formdata['formData']['shipping_nbr_pcs']) == '') ? 1 : (int)$formdata['formData']['shipping_nbr_pcs'],
			'DescriptionOfGoods' => trim($descriptionOfGoods),
			'GoodsOriginCountry' => 'TN',
			//'JO',
			'Items' => [
				'ShipmentItem' => $itemDetails
			]
		);

		$params['Details']['CashOnDeliveryAmount'] = array(
			'Value' => str_replace('.000', '', $order->get_total() <= 0 ? 0 : $order->get_total()),
			'CurrencyCode' => 'TND'
		);

		$params['Details']['CustomsValueAmount'] = array(
			'Value' => 0.1,
			'CurrencyCode' => 'TND'
		);
		$params['Details']['InsuranceAmount'] = array(
			'Value' => '',
			'CurrencyCode' => 'TND'
		);
		$params['ShipmentDetails']['InsuranceAmount'] = '';
		$params['Details']['CashAdditionalAmount'] = array(
			'Value' => '',
			'CurrencyCode' => 'TND'
		);

		$major_par['Shipments'][] = $params;

		$major_par['ClientInfo'] = array
		(
			'AccountCountryCode' => $account_country_code,
			'AccountEntity' => $account_entity,
			'AccountNumber' => $account_number,
			'AccountPin' => $account_pin,
			'UserName' => $company_username,
			'Password' => $company_password,
			'Version' => 'v1.0',
			'Source' => $source,
			'address' => 'Mellita Houmt Souk Djerba',
			'city' => $shipper_city,
			'state' => '',
			'postalcode' => '4115',
			'country' => 'TN',
			'name' => 'Fashio.tn',
			'company' => 'Fashio.tn',
			'phone' => '58407470',
			'email' => 'fashio.tn@gmail.com',
			'report_id' => ''
		);
		$major_par['LabelInfo'] = array(
			'ReportID' => 9729,
			'ReportType' => 'URL'
		);

		try {

			$auth_call = $soapClient->CreateShipments($major_par);

			if ($auth_call->HasErrors) {
				return [
					'debug' => $major_par,
					'resp' => 'error',
					'auth_call' => $auth_call
				];
			} else {
				$ship_number = '';

				if ($auth_call->Shipments->ProcessedShipment->ID && $auth_call->Shipments->ProcessedShipment->ID != '') {
					$ship_number = $auth_call->Shipments->ProcessedShipment->ID;
					$order->update_status('manifest', __('Aramex shipment created ' . $ship_number, 'aramex'));
					update_post_meta($order->get_id(), '_shipment_number_id', $ship_number);
					update_post_meta($order->get_id(), '_shipment_company_name', 'aramex');
					update_post_meta($order->get_id(), '_shipment_company_label', 'Aramex');
				}

				///get bouredearau label
				$this->generate_company_label_aramex( $order, $formdata['formData']['shipping_company'] );

				return [

					'resp' => 'success',
					'msg'  => $ship_number,
					'auth_call' => $auth_call
				];
			}
		} catch (Exception $ex) {

			return [

				'resp' => 'error',
				'auth_call' => $ex->getMessage()
			];
		}

	}


	function generate_company_label_aramex( $order, $company ) {

		$error = [];

		//create folder for order pdfs if not

		$upload_dir = wp_upload_dir();
		$path       = $upload_dir['basedir'] . '/wsc_files_orders/orders/' . $order->get_id();
		if ( ! file_exists( $path ) ) {
			mkdir( $path, 0777, true );
		}


		$shipment_id = get_post_meta( $order->get_id(), '_shipment_number_id', true );
		if ( $shipment_id && $shipment_id != '' ) {

			$soapClientShippment = new SoapClient(WP_CURRENT_THIS_PLUGIN_URL . 'admin/aramex-api/shipping.wsdl');

			$report_id = 9729;

			$account_number = '171083';
			$account_pin = '321321';

			$company_username = 'fashio.tn@gmail.com';
			$company_password = 'Qsdcxw@123';

			$account_entity = 'TUN';
			$account_country_code = 'TN';


			$params = array(
				'ClientInfo' => array(
					'AccountCountryCode' => $account_country_code,
					'AccountEntity' => $account_entity,
					'AccountNumber' => $account_number,
					'AccountPin' => $account_pin,
					'UserName' => $company_username,
					'Password' => $company_password,
					'Version' => 'v1.0',
					'Source' => 31,
					'address' => 'Mellita Houmt Souk Djerba',
					'city' => 'Medenine',
					'state' => '',
					'postalcode' => '4115',
					'country' => 'TN',
					'name' => 'Fashio.tn',
					'company' => 'Fashio.tn',
					'phone' => '58407470',
					'email' => 'fashio.tn@gmail.com',
					'report_id' => $report_id,
				),
				'Transaction' => array(
					'Reference1' => ''
				),
				'LabelInfo' => array(
					'ReportID' => $report_id,
					'ReportType' => 'URL',
				),
                'ShipmentNumber' => $shipment_id
			);

            try
            {
	            $auth_call_shippment = $soapClientShippment->PrintLabel($params);
	            if (isset($auth_call_shippment->ShipmentLabel->LabelURL)) {

		            $this->print_bl_company_shipping( $order, $company, $path );
		            $path = $path . '/order_label.pdf';

		            file_put_contents( $path, fopen($auth_call_shippment->ShipmentLabel->LabelURL, 'r') );
	            }
            }
            catch (SoapFault $fault) {
	            $error[] = [
		            'resp' => 'error',
		            'msg'  => 'Error generating aramex label pdf'
	            ];
            }

		}

		if ( ! empty( $error ) ) {
			return $error;
		}


		return 'success';
	}
	/******************* aramex *****************/

	function wsc_companies_meta_box_save( $post_id ) {
		if ( isset( $_POST['post_type'] ) && $_POST['post_type'] == "wsc-companies" ) {

			foreach ( $_POST['wscdata'] as $meta => $value ) {
				update_post_meta( $post_id, $meta, $value );
			}
		}
	}

	function global_wsc_companies_meta_box() {
		add_meta_box(
			'new-wsc-companies',
			__( 'Company Information', $this->plugin_name ),
			[ $this, 'order_wsc_companies_meta_box_callback' ],
			'wsc-companies',
			'normal'
		);
	}

	function order_wsc_companies_meta_box_callback( $post ) {
		include 'partials/company-admin-display.php';
	}

	function entity_setup_post_type() {
		$args = array(
			'public'          => false,
			'label'           => __( 'Shipping Comp.', $this->plugin_name ),
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-building',
			'supports'        => array( 'title' ),
			'capability_type' => 'page',
			'rewrite'         => array( 'slug' => 'wsc-companies' ),
		);
		register_post_type( 'wsc-companies', $args );
	}

}
