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

            //plugins_url('/core/favicon.ico', __DIR__),
            'data:image/svg+xml;base64,' .base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" color="grey"><!-- Font Awesome Pro 5.15.4 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) --><path d="M260.353,254.878,131.538,33.1a2.208,2.208,0,0,0-3.829.009L.3,254.887A2.234,2.234,0,0,0,.3,257.122L129.116,478.9a2.208,2.208,0,0,0,3.83-.009L260.358,257.113A2.239,2.239,0,0,0,260.353,254.878Zm39.078-25.713a2.19,2.19,0,0,0,1.9,1.111h66.509a2.226,2.226,0,0,0,1.9-3.341L259.115,33.111a2.187,2.187,0,0,0-1.9-1.111H190.707a2.226,2.226,0,0,0-1.9,3.341ZM511.7,254.886,384.9,33.112A2.2,2.2,0,0,0,382.99,32h-66.6a2.226,2.226,0,0,0-1.906,3.34L440.652,256,314.481,476.66a2.226,2.226,0,0,0,1.906,3.34h66.6a2.2,2.2,0,0,0,1.906-1.112L511.7,257.114A2.243,2.243,0,0,0,511.7,254.886ZM366.016,284.917H299.508a2.187,2.187,0,0,0-1.9,1.111l-108.8,190.631a2.226,2.226,0,0,0,1.9,3.341h66.509a2.187,2.187,0,0,0,1.9-1.111l108.8-190.631A2.226,2.226,0,0,0,366.016,284.917Z"/></svg>'),
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