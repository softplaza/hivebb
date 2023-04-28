<?php
/**
 * 
 */
use \HiveBB\DBLayer;

defined( 'ABSPATH' ) OR die();

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) die;

require_once WP_PLUGIN_DIR . '/hivebb/inc/DBLayer.php';
$forum_db = new DBLayer;

$forum_db->drop_table('applications');
$forum_db->drop_table('bans');
$forum_db->drop_table('categories');
$forum_db->drop_table('censoring');
$forum_db->drop_table('config');
$forum_db->drop_table('extensions');
$forum_db->drop_table('extension_hooks');
$forum_db->drop_table('forum_perms');
$forum_db->drop_table('forums');
$forum_db->drop_table('groups');
$forum_db->drop_table('online');
$forum_db->drop_table('posts');
$forum_db->drop_table('ranks');
$forum_db->drop_table('reports');
$forum_db->drop_table('search_cache');
$forum_db->drop_table('search_matches');
$forum_db->drop_table('search_words');
$forum_db->drop_table('subscriptions');
$forum_db->drop_table('forum_subscriptions');
$forum_db->drop_table('topics');
$forum_db->drop_table('users');

// Remove HiveBB pages
$wp_db = new DBLayer;
global $table_prefix;
$wp_db->prefix = $table_prefix;

$forum_pages = [
    'forum'         => 'Forum',
    'viewforum'     => 'Topics',
    'viewtopic'     => 'Posts',
    'userlist'      => 'Userlist',
    'profile'       => 'Profile',
    'search'        => 'Search',
    'post'        	=> 'Post',
    'edit'        	=> 'Edit',
    'delete'        => 'Delete',
    'report'        => 'Report',
    'moderate'      => 'Moderate',
    'misc'          => 'Misc',
    'help'          => 'Help',
    'extern'        => 'Extern',
];
foreach($forum_pages as $page_id => $page_name)
{
    $query = array(
        'DELETE'	=> 'posts',
        'WHERE'		=> 'post_name=\''.$page_id.'\''
    );
    $wp_db->query_build($query) or $wp_db->error();
}

//
global $wp_filesystem;

// Remove plugin files.
$wp_filesystem->rmdir( WP_PLUGIN_DIR . '/hivebb/', true );
