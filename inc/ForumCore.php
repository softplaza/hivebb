<?php
/**
 * @copyright (C) 2022 SoftPlaza.NET
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package AdminMenu
 * @author SoftPlaza.NET
 */

namespace HiveBB;

class ForumCore
{
    // Use all variables in Array
    static $var = [];

    // Array
    static $forum_start;// ???

	static $forum_date_formats = [];
	static $forum_time_formats = [];
    static $forum_config = [];
    static $forum_hooks = [];
    static $apps_info = [];
    static $forum_page = [];
    static $lang = [];
    static $forum_url = [];
    static $errors = [];

    // page param
    static $cur_forum = [];
    static $cur_topic = [];
    static $cur_posting = [];

    // Variables
    static $id = 0;
    static $tid = 0;
    static $fid = 0;
    static $section;
    static $action;
    static $page;

    static $page_title = '';

    static $dir_path;
    static $dir_url;
    static $base_url; // forum view url
    private $apps_path = FORUM_ROOT.'apps';

    static $parsed_signature;
    static $is_subscribed;
    static $post_message;

    static $show_as = 'topics';
    static $advanced_search;
    static $num_hits;
    static $search_set;
    static $search_id;

    protected static $instance;

    public static function init()
    {
        is_null( self::$instance ) AND self::$instance = new self;
        return self::$instance;
    }

    public function __construct()
    {
        // Load forum urls
        if (file_exists(FORUM_ROOT.'include/url/Default/forum_urls.php'))
        {
            require FORUM_ROOT.'include/url/Default/forum_urls.php';

            self::$forum_url = $forum_url;
        }

        if ( file_exists( FORUM_ROOT.'lang/English/common.php') )
        {
            self::$lang = include FORUM_ROOT.'lang/English/common.php';
        }

        self::$dir_path = FORUM_ROOT;
        self::$dir_url = FORUM_URL;
        self::$base_url = home_url().'/forum';
    }

    public static function gen_config()
    {
        // Load cached config
        if (file_exists(FORUM_CACHE_DIR.'cache_config.php'))
            include FORUM_CACHE_DIR.'cache_config.php';

        if (!defined('FORUM_CONFIG_LOADED'))
        {
            if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
                require FORUM_ROOT.'include/cache.php';

            generate_config_cache();
            require FORUM_CACHE_DIR.'cache_config.php';
        }

        return self::$forum_config = $forum_config;
    }

    public static function add_lang($lang_pack = '')
    {
        // Load cached config
        if (file_exists(FORUM_ROOT.'lang/English/'.$lang_pack.'.php'))
        {
            $cur_lang = include FORUM_ROOT.'lang/English/'.$lang_pack.'.php';

            self::$lang = array_merge(self::$lang, $cur_lang);
        }
    }

}