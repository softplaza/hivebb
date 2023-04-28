<?php
/**
 * Plugin Name: HiveBB Forum
 * Plugin URI: http://wordpress.org/plugins/hivebb/
 * Description: HiveBB Forum based on original PunBB. This is a fast and lightweight PHP-powered discussion board. Its primary goals are to be faster, smaller and less graphically intensive as compared to other discussion boards.
 * Author: SoftPlaza.NET
 * Version: 1.4.6.13
 * Author URI: https://softplaza.net/
 * 
 * HiveBB Forum is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * HiveBB Forum is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with HiveBB Forum. If not, see <https://www.gnu.org/licenses/>.
 */
namespace HiveBB;

defined( 'ABSPATH' ) OR die();

// Plugin Folder Path.
if (!defined( 'HIVEBB_PLUGIN_DIR'))
	define('HIVEBB_PLUGIN_DIR', plugin_dir_path( __FILE__ ));

// Plugin Folder URL.
if (!defined('HIVEBB_PLUGIN_URL'))
	define( 'HIVEBB_PLUGIN_URL', plugin_dir_url( __FILE__ ));

define('WP_FORUM_ADMIN_PAGE', home_url().'/wp-admin/admin.php?page=');
define('FORUM_ROOT', plugin_dir_path( __FILE__ ).'core/');
define('FORUM_URL', plugin_dir_url( __FILE__ ).'core/');
define('FORUM_BASE_URL', plugin_dir_url( __FILE__ ).'core');
define('FORUM', 1);
define('FORUM_DEBUG', 1);

// Enable show DB Queries mode by removing // from the following line
//define('FORUM_SHOW_QUERIES', 1);

// Enable forum IDNA support by removing // from the following line
//define('FORUM_ENABLE_IDNA', 1);

// Disable forum CSRF checking by removing // from the following line
//define('FORUM_DISABLE_CSRF_CONFIRM', 1);

// Disable forum hooks (extensions) by removing // from the following line
//define('FORUM_DISABLE_HOOKS', 1);

// Disable forum output buffering by removing // from the following line
//define('FORUM_DISABLE_BUFFERING', 1);

// Disable forum async JS loader by removing // from the following line
//define('FORUM_DISABLE_ASYNC_JS_LOADER', 1);

// Disable forum extensions version check by removing // from the following line
//define('FORUM_DISABLE_EXTENSIONS_VERSION_CHECK', 1);


// Autoload classes from classmap
function hivebb_load_classes()
{
	$class_map = array_merge(
		include HIVEBB_PLUGIN_DIR . 'inc/classmap.php',
	);
	spl_autoload_register(
		function ( $class ) use ( $class_map ) {
			if ( isset( $class_map[ $class ] )) {
				require_once $class_map[ $class ];
			}
		},
		true,
		true
	);
}
hivebb_load_classes();

register_activation_hook(__FILE__, array( 'HiveBB\\Setup', 'activation'));
register_deactivation_hook(__FILE__, array( 'HiveBB\\Setup', 'deactivation'));
//register_uninstall_hook(__FILE__, array( 'HiveBB\\Setup', 'uninstall'));
add_action('plugins_loaded', array( 'HiveBB\\Setup', 'init' ) );

require HIVEBB_PLUGIN_DIR . 'inc/functions.php';

if (!isset($_SESSION))
  session_start();

if (isset($_SESSION['pun_message_success']))
{
	echo '<div id="brd-messages" class="brd"><span class="message_info">'.$_SESSION['pun_message_success'].'</span></div>';
  	unset($_SESSION['pun_message_success']);
}

/*
add_filter( 'wp_get_nav_menu_items', function($items, $menu, $args)
{
    // IDs of the menu items to be excluded
    $exclude_ids = array( 2, 3, 6 );

    // Loop through menu items and remove the ones with matching IDs
    foreach ( $items as $key => $item ) {
        if ( in_array( $item->ID, $exclude_ids ) ) {
            unset( $items[$key] );
        }
    }

    return $items;
}, 10, 3 );
*/