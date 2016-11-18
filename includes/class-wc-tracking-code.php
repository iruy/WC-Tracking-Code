<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Tracking_code {

	/**
	 * The single instance of WC_Tracking_code.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Text domain for localisation.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $text_domain;

	/**
	 * Text to show in prompt.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $prompt_text;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct ( $file = '', $version = '1.0.0' ) {
		$this->_version = $version;
		$this->_token = 'wc_tracking_code';

		// Load plugin environment variables
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );


		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load frontend JS & CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Start Custom code
		$this->text_domain = 'wc-tracking-code';
		$this->prompt_text = get_option('wctc_prompt_text', __('Insert tracking code here:', $this->text_domain));
		add_filter('woocommerce_admin_order_actions', array( $this, 'add_order_action' ), 10, 2);
		add_filter('manage_edit-shop_order_columns', array( $this, 'custom_shop_order_column' ), 11);
		add_action('manage_shop_order_posts_custom_column', array( $this, 'custom_admin_order_column' ), 10, 2);
		add_action('wp_ajax_wc_add_tracking', array( $this, 'woocommerce_add_order_tracking_code' ));
		add_action('wp_ajax_nopriv_wc_add_tracking', array( $this, 'woocommerce_add_order_tracking_code' ));
		add_action('add_meta_boxes', array( $this, 'wctc_add_meta_boxes' ));
		add_action('save_post', array( $this, 'save_wctc_order_other_fields' ), 10, 1);
		add_action('admin_init', array($this, 'localize_admin_script'), 10);
		// End custom code


		// Load API for generic admin functions
		if ( is_admin() ) {
			$this->admin = new WC_Tracking_code_Admin_API();
		}

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );
	} // End __construct ()

	/**
	 * Wrapper function to register a new post type
	 * @param  string $post_type   Post type name
	 * @param  string $plural      Post type item plural name
	 * @param  string $single      Post type item single name
	 * @param  string $description Description of post type
	 * @return object              Post type class object
	 */
	public function register_post_type ( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

		if ( ! $post_type || ! $plural || ! $single ) return;

		$post_type = new WC_Tracking_code_Post_Type( $post_type, $plural, $single, $description, $options );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy
	 * @param  string $taxonomy   Taxonomy name
	 * @param  string $plural     Taxonomy single name
	 * @param  string $single     Taxonomy plural name
	 * @param  array  $post_types Post types to which this taxonomy applies
	 * @return object             Taxonomy class object
	 */
	public function register_taxonomy ( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) return;

		$taxonomy = new WC_Tracking_code_Taxonomy( $taxonomy, $plural, $single, $post_types, $taxonomy_args );

		return $taxonomy;
	}

	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-frontend' );
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts () {
		wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-frontend' );
	} // End enqueue_scripts ()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-admin' );
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		load_plugin_textdomain( $this->text_domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'wc-tracking-code';

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	/**
	 * Main WC_Tracking_code Instance
	 *
	 * Ensures only one instance of WC_Tracking_code is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see WC_Tracking_code()
	 * @return Main WC_Tracking_code instance
	 */
	public static function instance ( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()



	// Start Custom code
	public function add_order_action($actions, $the_order) {
		GLOBAL $post;
		$actions['wctc-tracking'] = array(
			'url' => admin_url('?post=' . $post->ID),
			'name' => __('Add code', $this->text_domain),
			'action' => "wctc-tracking"
		);
		return $actions;
	}


	public function custom_shop_order_column($columns) {
		$index = array_search("customer_message", array_keys($columns));
		$columns = array_slice($columns, 0, $index, true) + array("wctc-tracking" => __('Tracking', $this->text_domain)) + array_slice($columns, $index, count($columns) - 1, true);
		return $columns;
	}

	public function custom_admin_order_column($column) {
		global $the_order;
		switch($column) {
			case 'wctc-tracking' :
				$tracking = get_post_meta($the_order->id, 'wctc-tracking', true);
				echo trim($tracking) == "" ? "-" : $tracking;
				break;
		}
	}

	public function woocommerce_add_order_tracking_code() {
		if(empty($_POST['order_id']) || empty($_POST['wctc_tracking'])) {
			wp_send_json_error();
		}
		if(!update_post_meta($_POST['order_id'], 'wctc-tracking', $_POST['wctc_tracking'])) {
			wp_send_json_error();
		} else {
			wp_send_json_success();
		}
	}

	public function wctc_add_meta_boxes() {
		global $woocommerce, $order, $post;

		add_meta_box('wctc-tracking', __('Tracking code', $this->text_domain), array($this, 'draw_order_page_meta_box'), 'shop_order', 'side', 'core');
	}

	//
	//adding Meta field in the meta container admin shop_order pages
	//
	public function draw_order_page_meta_box() {
		global $woocommerce, $order, $post;

		$meta_field_data = get_post_meta($post->ID, 'wctc-tracking', true);

		echo '<input type="hidden" name="wc_other_meta_field_nonce" value="' . wp_create_nonce() . '">' . '<p style="border-bottom:solid 1px #eee;padding-bottom:13px;">' . '<input type="text" style="width:250px;";" name="wctc_tracking" placeholder="' . $meta_field_data . '" value="' . $meta_field_data . '"></p>';

	}

	//Save the data of the Meta field
	public function save_wctc_order_other_fields($post_id) {

		// We need to verify this with the proper authorization (security stuff).

		// Check if our nonce is set.
		if(!isset($_POST['wc_other_meta_field_nonce'])) {
			return $post_id;
		}
		$nonce = $_REQUEST['wc_other_meta_field_nonce'];

		//Verify that the nonce is valid.
		if(!wp_verify_nonce($nonce)) {
			return $post_id;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return $post_id;
		}

		// Check the user's permissions.
		if('page' == $_POST['post_type']) {

			if(!current_user_can('edit_page', $post_id)) {
				return $post_id;
			}
		} else {

			if(!current_user_can('edit_post', $post_id)) {
				return $post_id;
			}
		}
		// --- Its safe for us to save the data ! --- //

		// Sanitize user input and update the meta field in the database.
		update_post_meta($post_id, 'wctc-tracking', $_POST['wctc_tracking']);
	}

	public function localize_admin_script(){
		wp_register_script( $this->_token . '-admin-js', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_localize_script( $this->_token . '-admin-js', 'admin_ajax_script', array(

			'ajax_url'      => admin_url( 'admin-ajax.php' ),
			'prompt_text'	=> $this->prompt_text

		) );

		wp_enqueue_script($this->_token . '-admin-js');
	}
}
