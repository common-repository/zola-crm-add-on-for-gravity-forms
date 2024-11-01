<?php 
/**
 * Plugin Name: Zola CRM Add-on for Gravity Forms
 * Description: Add-on for Gravity Forms to submit leads data to Zola CRM. Needs Gravity Forms to function.
	This plugin keeps track of where users where refered from and the pages the user visits within the site. Upon submission it is sent to your CRM account at Zola Suite. 
	Link to Zola Suite's terms of Service: https://zolasuite.com/terms-of-service/
 * Version: 1.3.4
 * Author: Zola CRM 
 * Author URI: https://zolasuite.com
 * License: GPLv2 or later
*/

/**
 * Copyright (c) 2020 zolasuite.com
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */



/*
 * Constants and defaults 
 */
define( 'GF_SIMPLE_ADDON_VERSION', '2.0' );
define( 'ZS_TOKEN_VALIDATION_ENDPOINT', 'https://secure.zolasuite.com/api2/CRM/ValidateToken' );
define( 'ZS_SUBMISSION_ENDPOINT', 'https://secure.zolasuite.com/api2/CRM/CreateExternalLead' );


/*
 * Create the menu item that attaches itself to Gravity Forms and calls back settings page
 * Uses GF method: https://docs.gravityforms.com/gform_addon_navigation/
 */
class GF_zola_suite_addon_bootstrap {
 
    public static function load() {
 
		// Check if GF framework exists 
        if ( !method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }
 
        require_once( 'class-zola-suite-addon.php' );
 
        GFAddOn::register( 'GFzs_leads_addon' );
    }#End 
 
}#End GF_zola_suite_addon_bootstrap
add_action( 'gform_loaded', array( 'GF_zola_suite_addon_bootstrap', 'load' ), 5 );
 

/*
 * Load scripts on the frontend
 */
function zs_load_store_referrer(){
	
	// Enqueue the store_referrer.js file 
	wp_enqueue_script( 
		'zs_store_referrer', 
		plugin_dir_url( __FILE__ ) . 'store_referrer.js', 
		array('jquery'), '1.2', true 
	);
	
}#End zs_load_store_referrer
add_action('wp_enqueue_scripts', 'zs_load_store_referrer');