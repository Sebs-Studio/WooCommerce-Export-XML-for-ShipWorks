<?php
if(!class_exists('WC_ShipWorks_XML_Help_Settings')){
	class WC_ShipWorks_XML_Help_Settings{

		/**
		 * Constructor.
		 */
		public function __construct(){
			$this->tab_name = shipworks_tab_name;
			//$this->hidden_submit = $plugin_prefix.'submit';
			add_action('admin_init', array(&$this, 'load_hooks'));
		}

		/**
		 * Load the admin hooks.
		 */
		public function load_hooks(){
			if(wc_shipworks_plugin_version > '1.0.0'){
				add_filter('woocommerce_settings_tabs_array', array(&$this, 'add_settings_tab'));
				add_action('woocommerce_settings_tabs_'.$this->tab_name, array(&$this, 'shipworks_export_settings_page'));
				add_action('woocommerce_update_options_'.$this->tab_name, array($this, 'save_shipworks_export_settings_page'));
			}

			add_action('admin_init', array(&$this, 'load_help'), 20);
		}

		/**
		 * Check if we are on settings page.
		 */
		public function is_settings_page(){
			if(isset($_GET['page']) && isset($_GET['tab']) && $_GET['tab'] == $this->tab_name){
				return true;
			}
			else if(isset($_GET['page']) && $_GET['page'] == 'woocommerce_shipworks_export'){
				return true;
			}
			else{
				return false;
			}
		}

		/**
		 * Load the help system.
		 */
		public function load_help(){
			// Get the hookname and load the help tabs.
			if($this->is_settings_page()){
				$menu_slug = plugin_basename($_GET['page']);
				$hookname = get_plugin_page_hookname($menu_slug, '');

				add_action('load-'.$hookname, array(&$this, 'add_help_tabs'));
			}
		}

		/**
		 * Add the help tabs.
		 */
		public function add_help_tabs(){
			// Check current admin screen.
			$screen = get_current_screen();

			// Don't load help tab system prior WordPress 3.3
			if(!class_exists('WP_Screen') || ! $screen){
				return;
			}

			// Remove all existing tabs.
			$screen->remove_help_tabs();

			// Create arrays with help tab titles.
			// About the Plugin.
			$screen->add_help_tab(array(
				'id' => 'wc_shipworks',
				'title' => __('About the Plugin', 'wc_shipworks'),
				'content' => 
				'<h3>'.__('WooCommerce Export XML for Shipworks', 'wc_shipworks').'</h3>'.
				'<p>'.sprintf(__('Plugin Created by <a href="%1$s" target="_blank">Seb\'s Studio</a>.', 'wc_shipworks'), 'http://www.sebs-studio.com').'</p>'.
				'<p>'.__('Plugin Version', 'wc_shipworks').': <b>'.wc_shipworks_plugin_version.'</b></p>'.
				'<p>'.__('ShipWorks XML Schema Version', 'wc_shipworks').': <b>'.shipworks_xml_schema_version.'</b></p>'.
				'<p>'.__('Compatible with ShipWorks Version', 'wc_shipworks').': <b>'.shipworks_compatible_version.'</b></p>'
			));
			// How to Export.
			$screen->add_help_tab(array(
				'id' => 'wc_shipworks_how',
				'title' => __('How to Export', 'wc_shipworks'),
				'content' => 
				'<h3>'.__('How to export your orders').'</h3>'.
				'<p>'.sprintf(__('To export your orders, look under <a href="%1$s">WooCommerce > ShipWorks Export</a> and press "Export Orders". Yes, it is that easy :-).', 'wc_shipworks'), admin_url('admin.php?page=woocommerce_shipworks_export')).'</p>'.
				'<h4>How do I export orders between a certain date ?</h4>'.
				'<p>You can export orders between dates by clicking on the calendar and select a date. You can use the calendars drop menus to jump to a specfic month and year. Do this for both start and end dates to filter your orders before pressing on "Export Orders".</p>'.
				'<h4>Can I export orders that are in process ?</h4>'.
				'<p>Yes you can, all you need to do is select the export status "Processing" and press "Export Orders".</p>'.
				'<h4>It did not export anything, why ?</h4>'.
				'<p>It\'s possible that you have asked it to export orders that don\'t exist in the date range or on the order status that you selected. Try changing the date range or the order status and try again.</p>'
			));

			// Create help sidebar.
			$screen->set_help_sidebar(
				'<p><strong>'.__('Helpful Links', 'wc_shipworks').'</strong></p>'.
				'<p><a href="http://www.shipworks.com" target="_blank">'.__('ShipWorks', 'wc_shipworks').'</a></p>'.'
				<p><a href="http://www.sebs-studio.com" target="_blank">'.__('Seb\'s Studio', 'wc_shipworks').'</a></p>'.
				'<p><a href="http://www.sebs-studio.com/wp-plugins/woocommerce-export-xml-for-shipworks/" target="_blank">'.__('Plugin Details', 'wc_shipworks').'</a></p>'.
				'<p><a href="http://www.sebs-studio.com/support/" target="_blank">'.__('Support', 'wc_shipworks').'</a></p>'
			);
		}

		/**
		 * Add a tab to the settings page of WooCommerce.
		 */
		public function add_settings_tab($tabs){
			$tabs[$this->tab_name] = __('ShipWorks Export', 'wc_shipworks');

			return $tabs;
		}

		public function shipworks_export_settings_page(){
			if(!current_user_can('manage_options')){
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}
			echo '<div class="wrap">'.
			'<p>Settings for auto exports on orders will be placed here in the next version.</p>'.
			'</div>';
		}
	}
	// Instantiate plugin class and add it to the set of globals.
	$GLOBALS['wc_shipworks_export'] = new WC_ShipWorks_XML_Help_Settings();
}
?>