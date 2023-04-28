<?php
/**
 * @copyright (C) 2022 SoftPlaza.NET
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package AdminMenu
 * @author SoftPlaza.NET
 */

namespace HiveBB;

class AdminMenu
{
    protected static $instance;

    public static function init()
    {
        is_null( self::$instance ) AND self::$instance = new self;
        return self::$instance;
    }

    public function __construct()
    {
        $page = isset($_GET['page']) ? $_GET['page'] : '';
        if (substr($page, 0, 12) == 'hivebb_admin') {
            add_action('admin_enqueue_scripts', 'softplaza_hivebb_load_css');
            add_action('admin_footer', 'softplaza_hivebb_load_js', 5);
        }
        add_action('admin_menu', [$this, 'admin_menu']);
    }

    function admin_menu()
    {
        add_menu_page(
            'HiveBB Forum', // Page title
            'HiveBB', // Menu title
            'manage_options', 
            'hivebb_admin_index', // page ID
            [$this, 'admin_index'], // call back function
            plugins_url('/core/favicon.ico', __DIR__),
            5 // Position
        );

        add_submenu_page(
            'hivebb_admin_index', // parent
            'HiveBB Settings', // Page title
            'Settings', // Menu title
            'manage_options', 
            'hivebb_admin_settings',  // page ID
            [$this, 'admin_settings'], // call back function
        );

        add_submenu_page(
            'hivebb_admin_index', // parent
            'HiveBB Users', // Page title
            'Users', // Menu title
            'manage_options', 
            'hivebb_admin_users',  // page ID
            [$this, 'admin_users'], //call back function
        );
    
        add_submenu_page(
            'hivebb_admin_index', // parent
            'HiveBB Reports', // Page title
            'Management', // Menu title
            'manage_options', 
            'hivebb_admin_management',  // page ID
            [$this, 'admin_management'],  // call back function
        );
    }

    function admin_index(){
        require FORUM_ROOT . 'admin/index.php';
    }
    function admin_settings(){
        require FORUM_ROOT . 'admin/settings.php';
    }
    function admin_users(){
        require FORUM_ROOT . 'admin/users.php';
    }
    function admin_management(){
        require FORUM_ROOT . 'admin/management.php';
    }
}