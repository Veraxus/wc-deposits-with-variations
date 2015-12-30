<?php
/*
 * Plugin Name: WooCommerce Deposits
 * Plugin URI: http://www.woothemes.com/products/woocommerce-deposits/
 * Description: Mark items as deposit items which customers can then place deposits on, rather than paying in full.
 * Version: 1.1.4
 * Author: WooThemes
 * Author URI: http://woothemes.com/
 * Text Domain: woocommerce-deposits
 * Domain Path: /languages
 *
 * 		Copyright: © 2009-2015 WooThemes.
 *  	License: GNU General Public License v3.0
 *   	License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), 'de192a6cf12c4fd803248da5db700762', '977087' );

if ( is_woocommerce_active() ) {
	/**
	 * WC_Deposits class
	 */
	class WC_Deposits {

		/** @var object Class Instance */
		private static $instance;

		/**
		 * Get the class instance
		 */
		public static function get_instance() {
			return null === self::$instance ? ( self::$instance = new self ) : self::$instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {
			global $wpdb;

			define( 'WC_DEPOSITS_VERSION', '1.1.4' );
			define( 'WC_DEPOSITS_FILE', __FILE__ );
			define( 'WC_DEPOSITS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
			define( 'WC_DEPOSITS_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );

			register_activation_hook( __FILE__, array( $this, 'install' ) );

			if ( get_option( 'wc_deposits_version' ) !== WC_DEPOSITS_VERSION ) {
				add_action( 'shutdown', array( $this, 'delayed_install' ) );
			}

			$wpdb->wc_deposits_payment_plans          = $wpdb->prefix . 'wc_deposits_payment_plans';
			$wpdb->wc_deposits_payment_plans_schedule = $wpdb->prefix . 'wc_deposits_payment_plans_schedule';

			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
			add_action( 'plugins_loaded', array( $this, 'includes' ) );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		}

		/**
		 * Localisation
		 */
		public function load_plugin_textdomain() {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-deposits' );
			$dir    = trailingslashit( WP_LANG_DIR );
			load_textdomain( 'woocommerce-deposits', $dir . 'woocommerce-deposits/woocommerce-deposits-' . $locale . '.mo' );
			load_plugin_textdomain( 'woocommerce-deposits', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Includes
		 */
		public function includes() {
			if ( is_admin() ) {
				include_once( 'includes/class-wc-deposits-settings.php' );
				include_once( 'includes/class-wc-deposits-plans-admin.php' );
				include_once( 'includes/class-wc-deposits-product-admin.php' );
			}
			include_once( 'includes/class-wc-deposits-plans-manager.php' );
			include_once( 'includes/class-wc-deposits-cart-manager.php' );
			include_once( 'includes/class-wc-deposits-order-manager.php' );
			include_once( 'includes/class-wc-deposits-order-item-manager.php' );
			include_once( 'includes/class-wc-deposits-scheduled-order-manager.php' );
			include_once( 'includes/class-wc-deposits-product-manager.php' );
			include_once( 'includes/class-wc-deposits-plan.php' );
		}

		/**
		 * Show row meta on the plugin screen.
		 *
		 * @param	array $links Plugin Row Meta
		 * @param	string $file  Plugin Base file
		 * @return	array
		 */
		public function plugin_row_meta( $links, $file ) {
			if ( $file === plugin_basename( __FILE__ ) ) {
				$row_meta = array(
					'docs'    => '<a href="http://docs.woothemes.com/documentation/plugins/woocommerce/woocommerce-extensions/deposits/">' . __( 'Docs', 'woocommerce-deposits' ) . '</a>',
					'support' => '<a href="http://support.woothemes.com/">' . __( 'Premium Support', 'woocommerce-deposits' ) . '</a>',
				);
				return array_merge( $links, $row_meta );
			}
			return (array) $links;
		}

		/**
		 * Installer
		 */
		public function install() {
			add_action( 'shutdown', array( $this, 'delayed_install' ) );
		}

		/**
		 * Installer (delayed)
		 */
		public function delayed_install() {
			global $wpdb, $wp_roles;

			$wpdb->hide_errors();

			$collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				if ( ! empty( $wpdb->charset ) ) {
					$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
				}
				if ( ! empty( $wpdb->collate ) ) {
					$collate .= " COLLATE $wpdb->collate";
				}
			}

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			dbDelta( "
	CREATE TABLE {$wpdb->wc_deposits_payment_plans} (
	ID bigint(20) unsigned NOT NULL auto_increment,
	name varchar(255) NOT NULL,
	description longtext NOT NULL,
	PRIMARY KEY  (ID)
	) $collate;
	CREATE TABLE {$wpdb->wc_deposits_payment_plans_schedule} (
	schedule_id bigint(20) unsigned NOT NULL auto_increment,
	schedule_index bigint(20) unsigned NOT NULL default 0,
	plan_id bigint(20) unsigned NOT NULL,
	amount varchar(255) NOT NULL,
	interval_amount varchar(255) NOT NULL,
	interval_unit varchar(255) NOT NULL,
	PRIMARY KEY  (schedule_id),
	KEY plan_id (plan_id)
	) $collate;
			" );

			// Cron
			wp_clear_scheduled_hook( 'woocommerce_invoice_scheduled_orders' );
			wp_schedule_event( time(), 'hourly', 'woocommerce_invoice_scheduled_orders' );

			// Update version
			update_option( 'wc_deposits_version', WC_DEPOSITS_VERSION );
		}
	}
	WC_Deposits::get_instance();
}
