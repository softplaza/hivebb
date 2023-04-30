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
            'data:image/svg+xml;base64,' .base64_encode('<svg fill="#000000" width="800px" height="800px" viewBox="0 0 24 24" role="img" xmlns="http://www.w3.org/2000/svg"><path d="M6.076 1.637a.103.103 0 0 0-.09.05L.014 11.95a.102.102 0 0 0 0 .104l6.039 10.26c.04.068.14.068.18 0l5.972-10.262a.102.102 0 0 0-.002-.104L6.166 1.687a.103.103 0 0 0-.09-.05zm2.863 0a.103.103 0 0 0-.09.154l5.186 8.967a.105.105 0 0 0 .09.053h3.117c.08 0 .13-.088.09-.157l-5.186-8.966a.104.104 0 0 0-.09-.051H8.94zm5.891 0a.102.102 0 0 0-.088.154L20.656 12l-5.914 10.209a.102.102 0 0 0 .088.154h3.123a.1.1 0 0 0 .088-.05l5.945-10.262a.1.1 0 0 0 0-.102L18.041 1.688a.1.1 0 0 0-.088-.051H14.83zm-.79 11.7a.1.1 0 0 0-.089.052l-5.101 8.82c-.04.069.01.154.09.154h3.117a.104.104 0 0 0 .09-.05l5.1-8.82a.103.103 0 0 0-.09-.155h-3.118z"/></svg>'), //plugins_url('/core/favicon.ico', __DIR__),
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