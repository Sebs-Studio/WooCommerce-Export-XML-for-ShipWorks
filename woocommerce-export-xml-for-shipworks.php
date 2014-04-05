<?php
/*
 * Plugin Name: WooCommerce Export XML for Shipworks
 * Plugin URI: http://www.sebs-studio.com/wp-plugins/woocommerce-export-xml-for-shipworks/
 * Description: Export your completed orders into an XML file designed for ShipWorks to import and create shipping labels with ease. Select from a date range or export all orders.
 * Version: 1.2.2
 * Author: Sebs Studio
 * Author URI: http://www.sebs-studio.com
 * Requires at least: 3.1
 * Tested up to: 3.5.1
 *
 * Text Domain: wc_shipworks
 * Domain Path: /lang/
 * 
 * Copyright: © 2013 Sebs Studio.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Plugin Name.
define('wc_shipworks_plugin_name', 'WooCommerce Export XML for Shipworks');

// Plugin version.
define('wc_shipworks_plugin_version', '1.0.0');

// XML Schema Version
define('shipworks_xml_schema_version', '2');

// Compatible with ShipWorks Version
define('shipworks_compatible_version', '3.3.5.3866');

// Tab Name
define('shipworks_tab_name', 'woocommerce-shipworks-export');

// Required minimum version of WordPress.
if(!function_exists('woo_shipworks_min_required')){
	function woo_shipworks_min_required(){
		global $wp_version;
		$plugin = plugin_basename(__FILE__);
		$plugin_data = get_plugin_data(__FILE__, false);

		if(version_compare($wp_version, "3.3", "<")){
			if(is_plugin_active($plugin)){
				deactivate_plugins($plugin);
				wp_die("'".$plugin_data['Name']."' requires WordPress 3.3 or higher, and has been deactivated! Please upgrade WordPress and try again.<br /><br />Back to <a href='".admin_url()."'>WordPress Admin</a>.");
			}
		}
	}
	add_action('admin_init', 'woo_shipworks_min_required');
}

/* Load Sebs Studio Updater */
if(!function_exists('sebs_studio_queue_update')){
	require_once('includes/sebs-functions.php');
}
/* If Sebs Studio Updater is loaded, integrate for plugin updates. */
if(function_exists('sebs_studio_queue_update')){
	sebs_studio_queue_update(plugin_basename(__FILE__), '909c063d204d3fa13436389da7dcb3ce', '2237');
}

// Checks if the WooCommerce plugins is installed and active.
if(in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
	if(!class_exists('WC_ShipWorks_XML')){

		/* Localisation */
		$locale = apply_filters('plugin_locale', get_locale(), 'woocommerce-shipworks');
		load_textdomain('wc_shipworks', WP_PLUGIN_DIR."/".plugin_basename(dirname(__FILE__)).'/lang/woocommerce-export-xml-for-shipworks-'.$locale.'.mo');
		load_plugin_textdomain('wc_shipworks', false, dirname(plugin_basename(__FILE__)).'/lang/');

		/* WC_ShipWorks_XML Class */
		class WC_ShipWorks_XML{

			/** 
			 * class construct
			 * plugin activation, hooks & filters, etc..
			 *
			 * @since 1.0.0
			 * @return void
			 */
			function __construct(){
				/* Plugin Data */
				$plugin_prefix = 'wc_shipworks_';
				$plugin_basefile = plugin_basename(__FILE__);
				$plugin_url = plugin_dir_url($plugin_basefile);
				$plugin_path = trailingslashit(dirname(__FILE__));
				/* Filters */
				add_filter('plugin_action_links', array(&$this, 'add_settings_link'), 9, 2);
				add_filter('plugin_row_meta', array(&$this, 'add_support_links'), 10, 2);
				/* Actions */
				add_action('init', array(&$this, 'include_shipwork_classes'), 20);
				add_action('admin_bar_menu', array(&$this, 'shipworks_export_admin_bar_link'), 25);
				add_action('admin_init', array(&$this, 'shipworks_export_init'));
				add_action('admin_print_styles', array(&$this, 'shipworks_export_style'));
			}

			/*
			 * Adds 'Export' and 'Settings' link on plugin page.
			 */
			function add_settings_link($links, $file){
				if($file == plugin_basename(__FILE__)){
					$links[] = '<a href="'.admin_url('admin.php?page=woocommerce_shipworks_export').'">'.__('Export', 'wc_shipworks').'</a>';
					if(wc_shipworks_plugin_version > '1.0.0'){
					$links[] = '<a href="'.admin_url('admin.php?page=woocommerce_settings&tab='.shipworks_tab_name).'" title="'.__('Go to the settings page', 'wc_shipworks').'">'.__('Settings', 'wc_shipworks').'</a>';
					}
				}
				return $links;
			}

			/**
			 * Add support links on the plugin page.
			 */
			function add_support_links($links, $file){
				if(!current_user_can('install_plugins')){
					return $links;
				}
				if($file == plugin_basename(__FILE__)){
					$links[] = '<a href="http://www.sebs-studio.com/support/" target="_blank">'.__('Support', 'wc_shipworks').'</a>';
					$links[] = '<a href="http://www.sebs-studio.com/wp-plugins/woocommerce-extensions/" target="_blank">'.__('More WooCommerce Extensions', 'wc_shipworks').'</a>';
				}
				return $links;
			}

			/* 
			 * Add links to the admin bar for quick access.
			 */
			function shipworks_export_admin_bar_link(){
				global $wp_admin_bar;

				// Only show if user is super admin or admin and the admin bar is set to show.
				if(is_super_admin() || is_admin() || is_admin_bar_showing()) :

					$wp_admin_bar->add_menu( array(
						'id' => 'shipworks_export', 
						'title' => __('Export Orders', 'wc_shipworks'), 
						'href' => admin_url('admin.php?page=woocommerce_shipworks_export'), 
						'meta' => false
					));

					if(wc_shipworks_plugin_version > '1.0.0') : 
						// Add sub menu link to the Settings Page.
						$wp_admin_bar->add_menu( array(
							'parent' => 'shipworks_export',
							'id' => 'shipworks_export_settings',
							'title' => __('Settings', 'wc_shipworks'),
							'href' => admin_url('admin.php?page=woocommerce_settings&tab='.shipworks_tab_name), 
						));
					endif; // if shipworks plugin version is higher than initial release.
				endif;
			}

			/*
			 * Add jQuery, jQuery UI and jQuery UI Date Picker.
			 */
			function shipworks_export_init(){
				if(isset($_GET['page']) && $_GET['page'] == 'woocommerce_shipworks_export'){
					wp_enqueue_script('jquery');
					wp_enqueue_script('jquery-ui-core');
					wp_register_script('woocommerce-jquery-ui-datepicker', plugins_url('/assets/js/ui-datepicker.js', __FILE__));
					wp_enqueue_script('woocommerce-jquery-ui-datepicker');
					wp_register_script('shipworks_export_script', plugins_url('/assets/js/shipworks-export.js', __FILE__), array('jquery', 'jquery-ui-core'));
					wp_enqueue_script('shipworks_export_script');
				}
			}

			/* 
			 * Adds datepicker theme stylesheet and 
			 * identified icon on export page.
			 */
			function shipworks_export_style(){
				if(isset($_GET['page']) && $_GET['page'] == 'woocommerce_shipworks_export'){
					wp_enqueue_style('jquery.ui.theme', plugins_url('/assets/css/smoothness/jquery-ui-1.8.20.custom.css', __FILE__));
					wp_enqueue_style('shipworks-export', plugins_url('/assets/css/shipworks-export.css', __FILE__));
				}
			}

			/**
			 * Include the plugin classes required.
			 */
			function include_shipwork_classes(){
				include(plugin_basename('class/class-help-and-settings.php'));
				include(plugin_basename('class/class-export.php'));
			}
		}
	}
	// Instantiate plugin class and add it to the set of globals.
	$GLOBALS['wc_shipworks_export'] = new WC_ShipWorks_XML();
}
?>