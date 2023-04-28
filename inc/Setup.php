<?php
/**
 * @copyright (C) 2022 SoftPlaza.NET
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Setup
 * @author SoftPlaza.NET
 */
namespace HiveBB;

use \HiveBB\ForumCore;
use \HiveBB\DBLayer;

class Setup
{
    protected static $instance;

    public static function init()
    {
        is_null( self::$instance ) AND self::$instance = new self;
        return self::$instance;
    }

    public static function activation()
    {
        if ( ! current_user_can( 'activate_plugins' ) )
            return;

        // Disable error reporting for uninitialized variables
        error_reporting(E_ALL);

        // Turn off PHP time limit
        @set_time_limit(0);

        // Load the files
        require FORUM_ROOT.'include/constants.php';
        // We need some stuff from functions.php
        require FORUM_ROOT.'include/functions.php';

        //require FORUM_ROOT.'lang/English/install.php';
        ForumCore::add_lang('install');

        //require FORUM_ROOT.'lang/English/admin_settings.php';
        ForumCore::add_lang('admin_settings');

        $wp_user = wp_get_current_user();
        
        $forum_db = new DBLayer;

        if (!$forum_db->table_exists('users'))
        {
            //$db_type = 'mysqli';
            $default_lang = 'English';
        
            // Make sure board title and description aren't left blank
            $board_title = 'My HiveBB forum';
            $board_descrip = 'Unfortunately no one can be told what HiveBB is — you have to see it for yourself';
        
            // Start a transaction
            $forum_db->start_transaction();
        
            $schema = array(
                'FIELDS'		=> array(
                    'id'				=> $forum_db->dt_varchar(),
                    'title'				=> $forum_db->dt_varchar(),
                    'version'			=> $forum_db->dt_varchar(),
                    'description'		=> $forum_db->dt_text(),
                    'author'			=> $forum_db->dt_varchar(),
                    'disabled'			=> $forum_db->dt_int('TINYINT(1)'),
                ),
                'PRIMARY KEY'	=> array('id')
            );
            $forum_db->create_table('applications', $schema);

            // Create all tables
            $schema = array(
                'FIELDS'		=> array(
                    'id'			=> array(
                        'datatype'		=> 'SERIAL',
                        'allow_null'	=> false
                    ),
                    'username'		=> array(
                        'datatype'		=> 'VARCHAR(200)',
                        'allow_null'	=> true
                    ),
                    'ip'			=> array(
                        'datatype'		=> 'VARCHAR(255)',
                        'allow_null'	=> true
                    ),
                    'email'			=> array(
                        'datatype'		=> 'VARCHAR(80)',
                        'allow_null'	=> true
                    ),
                    'message'		=> array(
                        'datatype'		=> 'VARCHAR(255)',
                        'allow_null'	=> true
                    ),
                    'expire'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> true
                    ),
                    'ban_creator'	=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    )
                ),
                'PRIMARY KEY'	=> array('id')
            );
            $forum_db->create_table('bans', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'id'			=> array(
                        'datatype'		=> 'SERIAL',
                        'allow_null'	=> false
                    ),
                    'cat_name'		=> array(
                        'datatype'		=> 'VARCHAR(80)',
                        'allow_null'	=> false,
                        'default'		=> '\'New Category\''
                    ),
                    'disp_position'	=> array(
                        'datatype'		=> 'INT(10)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    )
                ),
                'PRIMARY KEY'	=> array('id')
            );
            $forum_db->create_table('categories', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'id'			=> array(
                        'datatype'		=> 'SERIAL',
                        'allow_null'	=> false
                    ),
                    'search_for'	=> array(
                        'datatype'		=> 'VARCHAR(60)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'replace_with'	=> array(
                        'datatype'		=> 'VARCHAR(60)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    )
                ),
                'PRIMARY KEY'	=> array('id')
            );
            $forum_db->create_table('censoring', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'conf_name'		=> array(
                        'datatype'		=> 'VARCHAR(255)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'conf_value'	=> array(
                        'datatype'		=> 'TEXT',
                        'allow_null'	=> true
                    )
                ),
                'PRIMARY KEY'	=> array('conf_name')
            );
            $forum_db->create_table('config', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'id'				=> array(
                        'datatype'		=> 'VARCHAR(150)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'title'				=> array(
                        'datatype'		=> 'VARCHAR(255)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'version'			=> array(
                        'datatype'		=> 'VARCHAR(25)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'description'		=> array(
                        'datatype'		=> 'TEXT',
                        'allow_null'	=> true
                    ),
                    'author'			=> array(
                        'datatype'		=> 'VARCHAR(50)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'uninstall'			=> array(
                        'datatype'		=> 'TEXT',
                        'allow_null'	=> true
                    ),
                    'uninstall_note'	=> array(
                        'datatype'		=> 'TEXT',
                        'allow_null'	=> true
                    ),
                    'disabled'			=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'dependencies'		=> array(
                        'datatype'		=> 'VARCHAR(255)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    )
                ),
                'PRIMARY KEY'	=> array('id')
            );
            $forum_db->create_table('extensions', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'id'			=> array(
                        'datatype'		=> 'VARCHAR(150)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'extension_id'	=> array(
                        'datatype'		=> 'VARCHAR(50)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'code'			=> array(
                        'datatype'		=> 'TEXT',
                        'allow_null'	=> true
                    ),
                    'installed'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'priority'		=> array(
                        'datatype'		=> 'TINYINT(1) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '5'
                    )
                ),
                'PRIMARY KEY'	=> array('id', 'extension_id')
            );
            $forum_db->create_table('extension_hooks', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'group_id'		=> array(
                        'datatype'		=> 'INT(10)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'forum_id'		=> array(
                        'datatype'		=> 'INT(10)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'read_forum'	=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'post_replies'	=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'post_topics'	=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    )
                ),
                'PRIMARY KEY'	=> array('group_id', 'forum_id')
            );
            $forum_db->create_table('forum_perms', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'id'			=> array(
                        'datatype'		=> 'SERIAL',
                        'allow_null'	=> false
                    ),
                    'forum_name'	=> array(
                        'datatype'		=> 'VARCHAR(80)',
                        'allow_null'	=> false,
                        'default'		=> '\'New forum\''
                    ),
                    'forum_desc'	=> array(
                        'datatype'		=> 'TEXT',
                        'allow_null'	=> true
                    ),
                    'redirect_url'	=> array(
                        'datatype'		=> 'VARCHAR(100)',
                        'allow_null'	=> true
                    ),
                    'moderators'	=> array(
                        'datatype'		=> 'TEXT',
                        'allow_null'	=> true
                    ),
                    'num_topics'	=> array(
                        'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'num_posts'		=> array(
                        'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'last_post'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> true
                    ),
                    'last_post_id'	=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> true
                    ),
                    'last_poster'	=> array(
                        'datatype'		=> 'VARCHAR(200)',
                        'allow_null'	=> true
                    ),
                    'sort_by'		=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'disp_position'	=> array(
                        'datatype'		=> 'INT(10)',
                        'allow_null'	=> false,
                        'default'		=>	'0'
                    ),
                    'cat_id'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=>	'0'
                    )
                ),
                'PRIMARY KEY'	=> array('id')
            );
            $forum_db->create_table('forums', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'g_id'						=> array(
                        'datatype'		=> 'SERIAL',
                        'allow_null'	=> false
                    ),
                    'g_title'					=> array(
                        'datatype'		=> 'VARCHAR(50)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'g_user_title'				=> array(
                        'datatype'		=> 'VARCHAR(50)',
                        'allow_null'	=> true
                    ),
                    'g_moderator'				=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'g_mod_edit_users'			=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'g_mod_rename_users'		=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'g_mod_change_passwords'	=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'g_mod_ban_users'			=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'g_read_board'				=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'g_view_users'				=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'g_post_replies'			=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'g_post_topics'				=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'g_edit_posts'				=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'g_delete_posts'			=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'g_delete_topics'			=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'g_set_title'				=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'g_search'					=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'g_search_users'			=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'g_send_email'				=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'g_post_flood'				=> array(
                        'datatype'		=> 'SMALLINT(6)',
                        'allow_null'	=> false,
                        'default'		=> '30'
                    ),
                    'g_search_flood'			=> array(
                        'datatype'		=> 'SMALLINT(6)',
                        'allow_null'	=> false,
                        'default'		=> '30'
                    ),
                    'g_email_flood'				=> array(
                        'datatype'		=> 'SMALLINT(6)',
                        'allow_null'	=> false,
                        'default'		=> '60'
                    )
                ),
                'PRIMARY KEY'	=> array('g_id')
            );
            $forum_db->create_table('groups', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'user_id'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'ident'			=> array(
                        'datatype'		=> 'VARCHAR(200)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'logged'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'idle'			=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'csrf_token'	=> array(
                        'datatype'		=> 'VARCHAR(40)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'prev_url'		=> array(
                        'datatype'		=> 'VARCHAR(255)',
                        'allow_null'	=> true
                    ),
                    'last_post'			=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> true
                    ),
                    'last_search'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> true
                    ),
                ),
                'UNIQUE KEYS'	=> array(
                    'user_id_ident_idx'	=> array('user_id', 'ident(40)')
                ),
                'INDEXES'		=> array(
                    'ident_idx'		=> array('ident(40)'),
                    'logged_idx'	=> array('logged')
                ),
                'ENGINE'		=> 'HEAP'
            );
            $forum_db->create_table('online', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'id'			=> array(
                        'datatype'		=> 'SERIAL',
                        'allow_null'	=> false
                    ),
                    'poster'		=> array(
                        'datatype'		=> 'VARCHAR(200)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'poster_id'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'poster_ip'		=> array(
                        'datatype'		=> 'VARCHAR(39)',
                        'allow_null'	=> true
                    ),
                    'poster_email'	=> array(
                        'datatype'		=> 'VARCHAR(80)',
                        'allow_null'	=> true
                    ),
                    'message'		=> array(
                        'datatype'		=> 'TEXT',
                        'allow_null'	=> true
                    ),
                    'hide_smilies'	=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'posted'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'edited'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> true
                    ),
                    'edited_by'		=> array(
                        'datatype'		=> 'VARCHAR(200)',
                        'allow_null'	=> true
                    ),
                    'topic_id'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    )
                ),
                'PRIMARY KEY'	=> array('id'),
                'INDEXES'		=> array(
                    'topic_id_idx'	=> array('topic_id'),
                    'multi_idx'		=> array('poster_id', 'topic_id'),
                    'posted_idx'	=> array('posted')
                )
            );
            $forum_db->create_table('posts', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'id'			=> array(
                        'datatype'		=> 'SERIAL',
                        'allow_null'	=> false
                    ),
                    'rank'			=> array(
                        'datatype'		=> 'VARCHAR(50)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'min_posts'		=> array(
                        'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    )
                ),
                'PRIMARY KEY'	=> array('id')
            );
            $forum_db->create_table('ranks', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'id'			=> array(
                        'datatype'		=> 'SERIAL',
                        'allow_null'	=> false
                    ),
                    'post_id'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'topic_id'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'forum_id'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'reported_by'	=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'created'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'message'		=> array(
                        'datatype'		=> 'TEXT',
                        'allow_null'	=> true
                    ),
                    'zapped'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> true
                    ),
                    'zapped_by'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> true
                    )
                ),
                'PRIMARY KEY'	=> array('id'),
                'INDEXES'		=> array(
                    'zapped_idx'	=> array('zapped')
                )
            );
            $forum_db->create_table('reports', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'id'			=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'ident'			=> array(
                        'datatype'		=> 'VARCHAR(200)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'search_data'	=> array(
                        'datatype'		=> 'TEXT',
                        'allow_null'	=> true
                    )
                ),
                'PRIMARY KEY'	=> array('id'),
                'INDEXES'		=> array(
                    'ident_idx'	=> array('ident(8)')
                )
            );
            $forum_db->create_table('search_cache', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'post_id'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'word_id'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'subject_match'	=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    )
                ),
                'INDEXES'		=> array(
                    'word_id_idx'	=> array('word_id'),
                    'post_id_idx'	=> array('post_id')
                )
            );
            $forum_db->create_table('search_matches', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'id'			=> array(
                        'datatype'		=> 'SERIAL',
                        'allow_null'	=> false
                    ),
                    'word'			=> array(
                        'datatype'		=> 'VARCHAR(20)',
                        'allow_null'	=> false,
                        'default'		=> '\'\'',
                        'collation'		=> 'bin'
                    )
                ),
                'PRIMARY KEY'	=> array('word'),
                'INDEXES'		=> array(
                    'id_idx'	=> array('id')
                )
            );
            $forum_db->create_table('search_words', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'user_id'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'topic_id'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    )
                ),
                'PRIMARY KEY'	=> array('user_id', 'topic_id')
            );
            $forum_db->create_table('subscriptions', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'user_id'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'forum_id'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    )
                ),
                'PRIMARY KEY'	=> array('user_id', 'forum_id')
            );
            $forum_db->create_table('forum_subscriptions', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'id'			=> array(
                        'datatype'		=> 'SERIAL',
                        'allow_null'	=> false
                    ),
                    'poster'		=> array(
                        'datatype'		=> 'VARCHAR(200)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'subject'		=> array(
                        'datatype'		=> 'VARCHAR(255)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'posted'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'first_post_id'	=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'last_post'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'last_post_id'	=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'last_poster'	=> array(
                        'datatype'		=> 'VARCHAR(200)',
                        'allow_null'	=> true
                    ),
                    'num_views'		=> array(
                        'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'num_replies'	=> array(
                        'datatype'		=> 'MEDIUMINT(8) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'closed'		=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'sticky'		=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'moved_to'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> true
                    ),
                    'forum_id'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    )
                ),
                'PRIMARY KEY'	=> array('id'),
                'INDEXES'		=> array(
                    'forum_id_idx'		=> array('forum_id'),
                    'moved_to_idx'		=> array('moved_to'),
                    'last_post_idx'		=> array('last_post'),
                    'first_post_id_idx'	=> array('first_post_id')
                )
            );
            $forum_db->create_table('topics', $schema);
        
            $schema = array(
                'FIELDS'		=> array(
                    'id'				=> array(
                        'datatype'		=> 'SERIAL',
                        'allow_null'	=> false
                    ),
                    'wp_user_id'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'group_id'			=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '3'
                    ),
                    'username'			=> array(
                        'datatype'		=> 'VARCHAR(200)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'password'			=> array(
                        'datatype'		=> 'VARCHAR(40)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'salt'				=> array(
                        'datatype'		=> 'VARCHAR(12)',
                        'allow_null'	=> true
                    ),
                    'email'				=> array(
                        'datatype'		=> 'VARCHAR(80)',
                        'allow_null'	=> false,
                        'default'		=> '\'\''
                    ),
                    'title'				=> array(
                        'datatype'		=> 'VARCHAR(50)',
                        'allow_null'	=> true
                    ),
                    'realname'			=> array(
                        'datatype'		=> 'VARCHAR(40)',
                        'allow_null'	=> true
                    ),
                    'url'				=> array(
                        'datatype'		=> 'VARCHAR(100)',
                        'allow_null'	=> true
                    ),
                    'facebook'			=> array(
                        'datatype'		=> 'VARCHAR(100)',
                        'allow_null'	=> true
                    ),
                    'twitter'			=> array(
                        'datatype'		=> 'VARCHAR(100)',
                        'allow_null'	=> true
                    ),
                    'linkedin'			=> array(
                        'datatype'		=> 'VARCHAR(100)',
                        'allow_null'	=> true
                    ),
                    'skype'			=> array(
                        'datatype'		=> 'VARCHAR(100)',
                        'allow_null'	=> true
                    ),
                    'jabber'			=> array(
                        'datatype'		=> 'VARCHAR(80)',
                        'allow_null'	=> true
                    ),
                    'icq'				=> array(
                        'datatype'		=> 'VARCHAR(12)',
                        'allow_null'	=> true
                    ),
                    'msn'				=> array(
                        'datatype'		=> 'VARCHAR(80)',
                        'allow_null'	=> true
                    ),
                    'aim'				=> array(
                        'datatype'		=> 'VARCHAR(30)',
                        'allow_null'	=> true
                    ),
                    'yahoo'				=> array(
                        'datatype'		=> 'VARCHAR(30)',
                        'allow_null'	=> true
                    ),
                    'location'			=> array(
                        'datatype'		=> 'VARCHAR(30)',
                        'allow_null'	=> true
                    ),
                    'signature'			=> array(
                        'datatype'		=> 'TEXT',
                        'allow_null'	=> true
                    ),
                    'disp_topics'		=> array(
                        'datatype'		=> 'TINYINT(3) UNSIGNED',
                        'allow_null'	=> true
                    ),
                    'disp_posts'		=> array(
                        'datatype'		=> 'TINYINT(3) UNSIGNED',
                        'allow_null'	=> true
                    ),
                    'email_setting'		=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'notify_with_post'	=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'auto_notify'		=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'show_smilies'		=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'show_img'			=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'show_img_sig'		=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'show_avatars'		=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'show_sig'			=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '1'
                    ),
                    'access_keys'		=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'timezone'			=> array(
                        'datatype'		=> 'FLOAT',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'dst'				=> array(
                        'datatype'		=> 'TINYINT(1)',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'time_format'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'date_format'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'language'			=> array(
                        'datatype'		=> 'VARCHAR(25)',
                        'allow_null'	=> false,
                        'default'		=> '\'English\''
                    ),
                    'style'				=> array(
                        'datatype'		=> 'VARCHAR(25)',
                        'allow_null'	=> false,
                        'default'		=> '\'Oxygen\''
                    ),
                    'num_posts'			=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'last_post'			=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> true
                    ),
                    'last_search'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> true
                    ),
                    'last_email_sent'	=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> true
                    ),
                    'registered'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'registration_ip'	=> array(
                        'datatype'		=> 'VARCHAR(39)',
                        'allow_null'	=> false,
                        'default'		=> '\'0.0.0.0\''
                    ),
                    'last_visit'		=> array(
                        'datatype'		=> 'INT(10) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> '0'
                    ),
                    'admin_note'		=> array(
                        'datatype'		=> 'VARCHAR(30)',
                        'allow_null'	=> true
                    ),
                    'activate_string'	=> array(
                        'datatype'		=> 'VARCHAR(80)',
                        'allow_null'	=> true
                    ),
                    'activate_key'		=> array(
                        'datatype'		=> 'VARCHAR(8)',
                        'allow_null'	=> true
                    ),
                    'avatar'			=> array(
                        'datatype'		=> 'TINYINT(3) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> 0,
                    ),
                    'avatar_width'		=> array(
                        'datatype'		=> 'TINYINT(3) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> 0,
                    ),
                    'avatar_height'		=> array(
                        'datatype'		=> 'TINYINT(3) UNSIGNED',
                        'allow_null'	=> false,
                        'default'		=> 0,
                    ),
                ),
                'PRIMARY KEY'	=> array('id'),
                'INDEXES'		=> array(
                    'registered_idx'	=> array('registered'),
                    'username_idx'		=> array('username(8)')
                )
            );
            $forum_db->create_table('users', $schema);

            $now = time();

            // Insert the four preset groups
            $query = array(
                'INSERT'	=> 'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
                'INTO'		=> 'groups',
                'VALUES'	=> '\'Administrators\', \'Administrator\', 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0'
            );
            $forum_db->query_build($query) or $forum_db->error();
        
            $query = array(
                'INSERT'	=> 'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
                'INTO'		=> 'groups',
                'VALUES'	=> '\'Guest\', NULL, 0, 0, 0, 0, 0, 1, 1, 0, 0, 0, 0, 0, 0, 1, 1, 0, 60, 30, 0'
            );
            $forum_db->query_build($query) or $forum_db->error();
        
            $query = array(
                'INSERT'	=> 'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
                'INTO'		=> 'groups',
                'VALUES'	=> '\'Members\', NULL, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 1, 60, 30, 60'
            );
            $forum_db->query_build($query) or $forum_db->error();
        
            $query = array(
                'INSERT'	=> 'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
                'INTO'		=> 'groups',
                'VALUES'	=> '\'Moderators\', \'Moderator\', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0'
            );
            $forum_db->query_build($query) or $forum_db->error();
        
            // Insert guest and first admin user
            $query = array(
                'INSERT'	=> 'group_id, username, password, email',
                'INTO'		=> 'users',
                'VALUES'	=> '2, \'Guest\', \'Guest\', \'Guest\''
            );
            $forum_db->query_build($query) or $forum_db->error();
        
            $query = array(
                'INSERT'	=> 'wp_user_id, group_id, username, email, language, num_posts, last_post, registered, registration_ip, last_visit',
                'INTO'		=> 'users',
                'VALUES'	=> ''.$wp_user->ID.', 1, \''.$forum_db->escape($wp_user->user_login).'\', \''.$forum_db->escape($wp_user->user_email).'\', \''.$forum_db->escape($default_lang).'\', 1, '.$now.', '.$now.', \'127.0.0.1\', '.$now.''
            );
            $forum_db->query_build($query) or $forum_db->error();
            $new_uid = $forum_db->insert_id();
        
            // Insert config data
            $config = array(
                'o_cur_version'				=> "'".FORUM_VERSION."'",
                'o_database_revision'		=> "'".FORUM_DB_REVISION."'",
                'o_board_title'				=> "'".$forum_db->escape($board_title)."'",
                'o_board_desc'				=> "'".$forum_db->escape($board_descrip)."'",
                'o_default_timezone'		=> "'0'",
                'o_time_format'				=> "'H:i:s'",
                'o_date_format'				=> "'Y-m-d'",
                'o_check_for_updates'		=> "'1'",
                'o_check_for_versions'		=> "'1'",
                'o_timeout_visit'			=> "'5400'",
                'o_timeout_online'			=> "'300'",
                'o_redirect_delay'			=> "'0'",
                'o_show_version'			=> "'0'",
                'o_show_user_info'			=> "'1'",
                'o_show_post_count'			=> "'1'",
                'o_signatures'				=> "'1'",
                'o_smilies'					=> "'1'",
                'o_smilies_sig'				=> "'1'",
                'o_make_links'				=> "'1'",
                'o_default_lang'			=> "'".$forum_db->escape($default_lang)."'",
                'o_default_style'			=> "'Oxygen'",
                'o_default_user_group'		=> "'3'",
                'o_topic_review'			=> "'15'",
                'o_disp_topics_default'		=> "'30'",
                'o_disp_posts_default'		=> "'25'",
                'o_indent_num_spaces'		=> "'4'",
                'o_quote_depth'				=> "'3'",
                'o_quickpost'				=> "'1'",
                'o_users_online'			=> "'1'",
                'o_censoring'				=> "'0'",
                'o_ranks'					=> "'1'",
                'o_show_dot'				=> "'0'",
                'o_topic_views'				=> "'1'",
                'o_quickjump'				=> "'1'",
                'o_gzip'					=> "'0'",
                'o_additional_navlinks'		=> "''",
                'o_report_method'			=> "'0'",
                'o_regs_report'				=> "'0'",
                'o_default_email_setting'	=> "'1'",
                'o_mailing_list'			=> "'".$forum_db->escape($wp_user->user_email)."'",
                'o_avatars'					=> "'1'",
                'o_avatars_dir'				=> "'img/avatars'",
                'o_avatars_width'			=> "'60'",
                'o_avatars_height'			=> "'60'",
                'o_avatars_size'			=> "'15360'",
                'o_search_all_forums'		=> "'1'",
                'o_sef'						=> "'Default'",
                'o_admin_email'				=> "'".$forum_db->escape($wp_user->user_email)."'",
                'o_webmaster_email'			=> "'".$forum_db->escape($wp_user->user_email)."'",
                'o_subscriptions'			=> "'1'",
                'o_smtp_host'				=> "NULL",
                'o_smtp_user'				=> "NULL",
                'o_smtp_pass'				=> "NULL",
                'o_smtp_ssl'				=> "'0'",
                'o_regs_allow'				=> "'1'",
                'o_regs_verify'				=> "'0'",
                'o_announcement'			=> "'0'",
                'o_announcement_heading'	=> "'".ForumCore::$lang['Default announce heading']."'",
                'o_announcement_message'	=> "'".ForumCore::$lang['Default announce message']."'",
                'o_rules'					=> "'0'",
                'o_rules_message'			=> "'".ForumCore::$lang['Default rules']."'",
                'o_maintenance'				=> "'0'",
                'o_maintenance_message'		=> "'".ForumCore::$lang['Maintenance message default']."'",
                'o_default_dst'				=> "'0'",
                'p_message_bbcode'			=> "'1'",
                'p_message_img_tag'			=> "'1'",
                'p_message_all_caps'		=> "'1'",
                'p_subject_all_caps'		=> "'1'",
                'p_sig_all_caps'			=> "'1'",
                'p_sig_bbcode'				=> "'1'",
                'p_sig_img_tag'				=> "'0'",
                'p_sig_length'				=> "'400'",
                'p_sig_lines'				=> "'4'",
                'p_allow_banned_email'		=> "'0'",
                'p_allow_dupe_email'		=> "'0'",
                'p_force_guest_email'		=> "'1'",
                'o_show_moderators'			=> "'0'",
                'o_mask_passwords'			=> "'1'"
            );
            foreach ($config as $conf_name => $conf_value)
            {
                $query = array(
                    'INSERT'	=> 'conf_name, conf_value',
                    'INTO'		=> 'config',
                    'VALUES'	=> '\''.$conf_name.'\', '.$conf_value.''
                );
                $forum_db->query_build($query) or $forum_db->error();
            }

            // Insert some other default data
            $query = array(
                'INSERT'	=> 'cat_name, disp_position',
                'INTO'		=> 'categories',
                'VALUES'	=> '\''.ForumCore::$lang['Default category name'].'\', 1'
            );
            $forum_db->query_build($query) or $forum_db->error();
        
            $query = array(
                'INSERT'	=> 'forum_name, forum_desc, num_topics, num_posts, last_post, last_post_id, last_poster, disp_position, cat_id',
                'INTO'		=> 'forums',
                'VALUES'	=> '\''.ForumCore::$lang['Default forum name'].'\', \''.ForumCore::$lang['Default forum descrip'].'\', 1, 1, '.$now.', 1, \''.$forum_db->escape($wp_user->user_login).'\', 1, '.$forum_db->insert_id().''
            );
            $forum_db->query_build($query) or $forum_db->error();
        
            $query = array(
                'INSERT'	=> 'poster, subject, posted, first_post_id, last_post, last_post_id, last_poster, forum_id',
                'INTO'		=> 'topics',
                'VALUES'	=> '\''.$forum_db->escape($wp_user->user_login).'\', \''.ForumCore::$lang['Default topic subject'].'\', '.$now.', 1, '.$now.', 1, \''.$forum_db->escape($wp_user->user_login).'\', '.$forum_db->insert_id().''
            );
            $forum_db->query_build($query) or $forum_db->error();
        
            $query = array(
                'INSERT'	=> 'poster, poster_id, poster_ip, message, posted, topic_id',
                'INTO'		=> 'posts',
                'VALUES'	=> '\''.$forum_db->escape($wp_user->user_login).'\', '.$wp_user->ID.', \'127.0.0.1\', \''.ForumCore::$lang['Default post contents'].'\', '.$now.', '.$forum_db->insert_id().''
            );
            $forum_db->query_build($query) or $forum_db->error();
        
            // Add new post to search table
            //require FORUM_ROOT.'include/search_idx.php';
            //update_search_index('post', $forum_db->insert_id(), ForumCore::$lang['Default post contents'], ForumCore::$lang['Default topic subject']);
        
            // Insert the default ranks
            $query = array(
                'INSERT'	=> $forum_db->quotes.'rank'.$forum_db->quotes.', min_posts',
                'INTO'		=> 'ranks',
                'VALUES'	=> '\''.ForumCore::$lang['Default rank 1'].'\', 0'
            );
            $forum_db->query_build($query) or $forum_db->error();
        
            $query = array(
                'INSERT'	=> $forum_db->quotes.'rank'.$forum_db->quotes.', min_posts',
                'INTO'		=> 'ranks',
                'VALUES'	=> '\''.ForumCore::$lang['Default rank 2'].'\', 10'
            );
            $forum_db->query_build($query) or $forum_db->error();
        
            $forum_db->end_transaction();
        }


        $wp_db = new DBLayer;
        global $table_prefix;
        $wp_db->prefix = $table_prefix;

        // Create HiveBB pages
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
            // Create post object
            $my_post = [
                'post_author'	    => $wp_user->ID,
                'post_date'	        => date('Y-m-d\TH:i:s'),
                'post_date_gmt'	    => date('Y-m-d\TH:i:s'),
                'post_content'	    => '[pun_header][/pun_header][pun_content][/pun_content][pun_footer][/pun_footer]',
                'post_title'	    => $page_name,
                'post_status'	    => 'publish',
                'comment_status'	=> 'closed',
                'ping_status'	    => 'closed',
                'post_name'	        => $page_id,
                'post_type'	        => 'page',
            ];
            //$wp_db->insert('posts', $query);// or $wp_db->error();

            // Insert the post into the database
            wp_insert_post( $my_post );
        }

    }

    public static function deactivation()
    {
        if ( ! current_user_can( 'activate_plugins' ) )
            return;

        $wp_db = new DBLayer;

        global $table_prefix;
        $wp_db->prefix = $table_prefix;

        $forum_pages = [
            'forum',
            'viewforum',
            'viewtopic',
            'userlist',
            'profile',
            'search',
            'post',
            'edit',
            'delete',
            'report',
            'moderate',
            'misc',
            'help',
            'extern',
        ];

        $wp_pages = get_posts([
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'numberposts'    => 50,
        ]);
        foreach($wp_pages as $page_info)
        {
            if (in_array($page_info->post_name, $forum_pages))
                wp_delete_post($page_info->ID);
        }
    }

    public static function uninstall()
    {
        // Exit if accessed directly.
        if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            exit;
        }
/*
        //require_once WP_PLUGIN_DIR . '/hivebb/inc/DBLayer.php';

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

*/
    }

    public function __construct(){}
}
