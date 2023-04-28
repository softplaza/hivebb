<?php
/**
 * @copyright (C) 2022 SoftPlaza.NET
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package ForumUser
 * @author SoftPlaza.NET
 */

namespace HiveBB;

use \HiveBB\ForumCore;
use \HiveBB\DBLayer;

class ForumUser
{
    static $user = [];
    static $forum_user = [];
    static $forum_bans = [];

    protected static $instance;
    public static function init()
    {
        is_null( self::$instance ) AND self::$instance = new self;
        return self::$instance;
    }

    public function __construct()
    {
        $this->cookie_login();
        // $this->authenticate_user($wp_user->ID);

    }

    // Attempt to login with the user ID and password hash from the cookie
    function cookie_login()
    {
        $forum_db = new DBLayer;

        $now = time();

        $return = ($hook = get_hook('fn_cookie_login_start')) ? eval($hook) : null;
        if ($return !== null)
            return;

        // If this a cookie for a logged in user and it shouldn't have already expired
        if (is_user_logged_in())
        {
            $wp_user = wp_get_current_user();

            $this->authenticate_user($wp_user->ID);

            // Set a default language if the user selected language no longer exists
            if (!file_exists(FORUM_ROOT.'lang/'.self::$forum_user['language'].'/common.php'))
                self::$forum_user['language'] = ForumCore::$forum_config['o_default_lang'];

            // Set a default style if the user selected style no longer exists
            if (!file_exists(FORUM_ROOT.'style/'.self::$forum_user['style'].'/'.self::$forum_user['style'].'.php'))
                self::$forum_user['style'] = ForumCore::$forum_config['o_default_style'];

            if (!self::$forum_user['disp_topics'])
                self::$forum_user['disp_topics'] = ForumCore::$forum_config['o_disp_topics_default'];
            if (!self::$forum_user['disp_posts'])
                self::$forum_user['disp_posts'] = ForumCore::$forum_config['o_disp_posts_default'];

            // Check user has a valid date and time format
            if (!isset(ForumCore::$forum_time_formats[self::$forum_user['time_format']]))
                self::$forum_user['time_format'] = 0;
            if (!isset(ForumCore::$forum_date_formats[self::$forum_user['date_format']]))
                self::$forum_user['date_format'] = 0;

            // Define this if you want this visit to affect the online list and the users last visit data
            if (!defined('FORUM_QUIET_VISIT'))
            {
                // Update the online list
                if (!self::$forum_user['logged'])
                {
                    self::$forum_user['logged'] = $now;
                    self::$forum_user['csrf_token'] = random_key(40, false, true);
                    self::$forum_user['prev_url'] = get_current_url(255);

                    // REPLACE INTO avoids a user having two rows in the online table
                    $query = array(
                        'REPLACE'	=> 'user_id, ident, logged, csrf_token',
                        'INTO'		=> 'online',
                        'VALUES'	=> self::$forum_user['id'].', \''.$forum_db->escape(self::$forum_user['username']).'\', '.self::$forum_user['logged'].', \''.self::$forum_user['csrf_token'].'\'',
                        'UNIQUE'	=> 'user_id='.self::$forum_user['id']
                    );

                    if (self::$forum_user['prev_url'] !== null)
                    {
                        $query['REPLACE'] .= ', prev_url';
                        $query['VALUES'] .= ', \''.$forum_db->escape(self::$forum_user['prev_url']).'\'';
                    }

                    ($hook = get_hook('fn_cookie_login_qr_add_online_user')) ? eval($hook) : null;
                    $forum_db->query_build($query) or error(__FILE__, __LINE__);

                    // Reset tracked topics
                    set_tracked_topics(null);
                }
                else
                {
                    // Special case: We've timed out, but no other user has browsed the forums since we timed out
                    if (self::$forum_user['logged'] < ($now - ForumCore::$forum_config['o_timeout_visit']))
                    {
                        $query = array(
                            'UPDATE'	=> 'users',
                            'SET'		=> 'last_visit='.self::$forum_user['logged'],
                            'WHERE'		=> 'id='.self::$forum_user['id']
                        );

                        ($hook = get_hook('fn_cookie_login_qr_update_user_visit')) ? eval($hook) : null;
                        $forum_db->query_build($query) or error(__FILE__, __LINE__);

                        self::$forum_user['last_visit'] = self::$forum_user['logged'];
                    }

                    // Now update the logged time and save the current URL in the online list
                    $query = array(
                        'UPDATE'	=> 'online',
                        'SET'		=> 'logged='.$now,
                        'WHERE'		=> 'user_id='.self::$forum_user['id']
                    );

                    $current_url = get_current_url(255);
                    if ($current_url !== null && !defined('FORUM_REQUEST_AJAX'))
                        $query['SET'] .= ', prev_url=\''.$forum_db->escape($current_url).'\'';

                    if (self::$forum_user['idle'] == '1')
                        $query['SET'] .= ', idle=0';

                    ($hook = get_hook('fn_cookie_login_qr_update_online_user')) ? eval($hook) : null;
                    $forum_db->query_build($query) or error(__FILE__, __LINE__);
                }
            }

            self::$forum_user['is_guest'] = false;
            self::$forum_user['is_admmod'] = (self::$forum_user['g_id'] == FORUM_ADMIN || self::$forum_user['g_moderator'] == '1');
        }
        else
            $this->set_default_user();

        //self::$forum_user = $forum_user;

        ($hook = get_hook('fn_cookie_login_end')) ? eval($hook) : null;
    }

    // Like other headers, cookies must be sent before any output from your script.
    // Use headers_sent() to ckeck wether HTTP headers has been sent already.
    public static function forum_setcookie($name, $value, $expire)
    {
        //
    }

    // Authenticates by user id
    function authenticate_user($user_id)
    {
        $forum_db = new DBLayer;

        $return = ($hook = get_hook('fn_authenticate_user_start')) ? eval($hook) : null;
        if ($return !== null)
            return;

        // Check if there's a user matching $user and $password
        $query = array(
            'SELECT'	=> 'u.*, g.*, o.logged, o.idle, o.csrf_token, o.prev_url',
            'FROM'		=> 'users AS u',
            'JOINS'		=> array(
                array(
                    'INNER JOIN'	=> 'groups AS g',
                    'ON'			=> 'g.g_id=u.group_id'
                ),
                array(
                    'LEFT JOIN'		=> 'online AS o',
                    'ON'			=> 'o.user_id=u.id'
                )
            ),
            'WHERE'     => 'u.wp_user_id='.$user_id
        );
        ($hook = get_hook('fn_authenticate_user_qr_get_user')) ? eval($hook) : null;
        $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
        self::$forum_user = $forum_db->fetch_assoc($result);

        ($hook = get_hook('fn_authenticate_user_end')) ? eval($hook) : null;
    }

    // Fill $forum_user with default values (for guests)
    function set_default_user()
    {
        $forum_db = new DBLayer;

        $remote_addr = get_remote_address();

        $return = ($hook = get_hook('fn_set_default_user_start')) ? eval($hook) : null;
        if ($return !== null)
            return;

        // Fetch guest user
        $query = array(
            'SELECT'	=> 'u.*, g.*, o.logged, o.csrf_token, o.prev_url, o.last_post, o.last_search',
            'FROM'		=> 'users AS u',
            'JOINS'		=> array(
                array(
                    'INNER JOIN'	=> 'groups AS g',
                    'ON'			=> 'g.g_id=u.group_id'
                ),
                array(
                    'LEFT JOIN'		=> 'online AS o',
                    'ON'			=> 'o.ident=\''.$forum_db->escape($remote_addr).'\''
                )
            ),
            'WHERE'		=> 'u.id=1'
        );

        ($hook = get_hook('fn_set_default_user_qr_get_default_user')) ? eval($hook) : null;
        $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
        self::$forum_user = $forum_db->fetch_assoc($result);

        if (!self::$forum_user)
            exit('Unable to fetch guest information. The table \''.$forum_db->prefix.'users\' must contain an entry with id = 1 that represents anonymous users.');

        if (!defined('FORUM_QUIET_VISIT'))
        {
            // Update online list
            if (!self::$forum_user['logged'])
            {
                self::$forum_user['logged'] = time();
                self::$forum_user['csrf_token'] = random_key(40, false, true);
                self::$forum_user['prev_url'] = get_current_url(255);

                // REPLACE INTO avoids a user having two rows in the online table
                $query = array(
                    'REPLACE'	=> 'user_id, ident, logged, csrf_token',
                    'INTO'		=> 'online',
                    'VALUES'	=> '1, \''.$forum_db->escape($remote_addr).'\', '.self::$forum_user['logged'].', \''.self::$forum_user['csrf_token'].'\'',
                    'UNIQUE'	=> 'user_id=1 AND ident=\''.$forum_db->escape($remote_addr).'\''
                );

                if (self::$forum_user['prev_url'] !== null)
                {
                    $query['REPLACE'] .= ', prev_url';
                    $query['VALUES'] .= ', \''.$forum_db->escape(self::$forum_user['prev_url']).'\'';
                }

                ($hook = get_hook('fn_set_default_user_qr_add_online_guest_user')) ? eval($hook) : null;
                $forum_db->query_build($query) or error(__FILE__, __LINE__);
            }
            else
            {
                $query = array(
                    'UPDATE'	=> 'online',
                    'SET'		=> 'logged='.time(),
                    'WHERE'		=> 'ident=\''.$forum_db->escape($remote_addr).'\''
                );

                $current_url = get_current_url(255);
                if ($current_url !== null)
                    $query['SET'] .= ', prev_url=\''.$forum_db->escape($current_url).'\'';

                ($hook = get_hook('fn_set_default_user_qr_update_online_guest_user')) ? eval($hook) : null;
                $forum_db->query_build($query) or error(__FILE__, __LINE__);
            }
        }

        self::$forum_user['disp_topics'] = ForumCore::$forum_config['o_disp_topics_default'];
        self::$forum_user['disp_posts'] = ForumCore::$forum_config['o_disp_posts_default'];
        self::$forum_user['timezone'] = ForumCore::$forum_config['o_default_timezone'];
        self::$forum_user['dst'] = ForumCore::$forum_config['o_default_dst'];
        self::$forum_user['language'] = ForumCore::$forum_config['o_default_lang'];
        self::$forum_user['style'] = ForumCore::$forum_config['o_default_style'];
        self::$forum_user['is_guest'] = true;
        self::$forum_user['is_admmod'] = false;

        //self::$forum_user = $forum_user;

        ($hook = get_hook('fn_set_default_user_end')) ? eval($hook) : null;
    }

    // Update "Users online"
    public static function update_users_online()
    {
        $forum_db = new DBLayer;

        $now = time();

        $return = ($hook = get_hook('fn_update_users_online_start')) ? eval($hook) : null;
        if ($return !== null)
            return;


        // Fetch all online list entries that are older than "o_timeout_online"
        $query = array(
            'SELECT'	=> 'o.*',
            'FROM'		=> 'online AS o',
            'WHERE'		=> 'o.logged < '.($now - ForumCore::$forum_config['o_timeout_online'])
        );

        ($hook = get_hook('fn_update_users_online_qr_get_old_online_users')) ? eval($hook) : null;
        $result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

        $need_delete_expired_guest = false;
        $expired_users_id = $idle_users_id = array();
        while ($cur_user = $forum_db->fetch_assoc($result))
        {
            if ($cur_user['user_id'] != '1')
            {
                // If the entry is older than "o_timeout_visit", update last_visit for the user in question, then delete him/her from the online list
                if ($cur_user['logged'] < ($now - ForumCore::$forum_config['o_timeout_visit']))
                {
                    $query = array(
                        'UPDATE'	=> 'users',
                        'SET'		=> 'last_visit='.$cur_user['logged'],
                        'WHERE'		=> 'id='.$cur_user['user_id']
                    );

                    ($hook = get_hook('fn_update_users_online_qr_update_user_visit')) ? eval($hook) : null;
                    $forum_db->query_build($query) or error(__FILE__, __LINE__);

                    // Add to expired list
                    $expired_users_id[] = $cur_user['user_id'];
                }
                else
                {
                    // Add to idle list
                    if ($cur_user['idle'] == '0')
                    {
                        $idle_users_id[] = $cur_user['user_id'];
                    }
                }
            }
            else
            {
                // We have expired guest — delete it later
                $need_delete_expired_guest = true;
            }
        }

        // Remove all guest that are older than "o_timeout_online"
        if ($need_delete_expired_guest)
        {
            $query = array(
                'DELETE'	=> 'online',
                'WHERE'		=> 'user_id=1 AND logged < '.($now - ForumCore::$forum_config['o_timeout_online'])
            );
            ($hook = get_hook('fn_update_users_online_qr_delete_online_guest_user')) ? eval($hook) : null;
            $forum_db->query_build($query) or error(__FILE__, __LINE__);
        }


        // Delete expired users
        if (!empty($expired_users_id))
        {
            $query = array(
                'DELETE'	=> 'online',
                'WHERE'		=> 'user_id IN ('.implode(',', $expired_users_id).')'
            );

            ($hook = get_hook('fn_update_users_online_qr_delete_online_user')) ? eval($hook) : null;
            $forum_db->query_build($query) or error(__FILE__, __LINE__);
        }

        // Update idle users
        if (!empty($idle_users_id))
        {
            $query = array(
                'UPDATE'	=> 'online',
                'SET'		=> 'idle=1',
                'WHERE'		=> 'user_id IN ('.implode(',', $idle_users_id).')'
            );

            ($hook = get_hook('fn_update_users_online_qr_update_user_idle')) ? eval($hook) : null;
            $forum_db->query_build($query) or error(__FILE__, __LINE__);
        }

        ($hook = get_hook('fn_update_users_online_end')) ? eval($hook) : null;
    }

	// Get user info by key
	function get($key)
	{
		if (isset($this->forum_user[$key]))
			return $this->forum_user[$key];
	}	
	
	// Check if current user is ADMIN
	function is_admin()
	{
		return ($this->forum_user['g_id'] == FORUM_ADMIN) ? true : false;
	}

	// Check if current user is GUEST
	function is_guest()
	{
		return ($this->forum_user['g_id'] == FORUM_GUEST) ? true : false;
	}
	
	// Check if current user is ASSISTANT
	function is_admmod()
	{
		return $this->forum_user['is_admmod'];
	}

    // Check whether the connecting user is banned (and delete any expired bans while we're at it)
    public static function check_bans()
    {
        global $forum_bans;

        $forum_db = new DBLayer;

        // Load cached bans
        if (file_exists(FORUM_CACHE_DIR.'cache_bans.php'))
            include FORUM_CACHE_DIR.'cache_bans.php';

        if (!defined('FORUM_BANS_LOADED'))
        {
            if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
                require FORUM_ROOT.'include/cache.php';

            generate_bans_cache();
            require FORUM_CACHE_DIR.'cache_bans.php';
        }

        self::$forum_bans = $forum_bans;

        $return = ($hook = get_hook('fn_check_bans_start')) ? eval($hook) : null;
        if ($return !== null)
            return;

        // Admins aren't affected
        if (defined('FORUM_ADMIN') && self::$forum_user['g_id'] == FORUM_ADMIN || !$forum_bans)
            return;

        // Add a dot or a colon (depending on IPv4/IPv6) at the end of the IP address to prevent banned address
        // 192.168.0.5 from matching e.g. 192.168.0.50
        $user_ip = get_remote_address();
        $user_ip .= (strpos($user_ip, '.') !== false) ? '.' : ':';

        $bans_altered = false;
        $is_banned = false;

        foreach ($forum_bans as $cur_ban)
        {
            // Has this ban expired?
            if ($cur_ban['expire'] != '' && $cur_ban['expire'] <= time())
            {
                $query = array(
                    'DELETE'	=> 'bans',
                    'WHERE'		=> 'id='.$cur_ban['id']
                );

                ($hook = get_hook('fn_check_bans_qr_delete_expired_ban')) ? eval($hook) : null;
                $forum_db->query_build($query) or error(__FILE__, __LINE__);

                $bans_altered = true;
                continue;
            }

            if ($cur_ban['username'] != '' && utf8_strtolower(self::$forum_user['username']) == utf8_strtolower($cur_ban['username']))
                $is_banned = true;

            if ($cur_ban['email'] != '' && self::$forum_user['email'] == $cur_ban['email'])
                $is_banned = true;

            if ($cur_ban['ip'] != '')
            {
                $cur_ban_ips = explode(' ', $cur_ban['ip']);

                $num_ips = count($cur_ban_ips);
                for ($i = 0; $i < $num_ips; ++$i)
                {
                    // Add the proper ending to the ban
                    if (strpos($user_ip, '.') !== false)
                        $cur_ban_ips[$i] = $cur_ban_ips[$i].'.';
                    else
                        $cur_ban_ips[$i] = $cur_ban_ips[$i].':';

                    if (substr($user_ip, 0, strlen($cur_ban_ips[$i])) == $cur_ban_ips[$i])
                    {
                        $is_banned = true;
                        break;
                    }
                }
            }

            if ($is_banned)
            {
                $query = array(
                    'DELETE'	=> 'online',
                    'WHERE'		=> 'ident=\''.$forum_db->escape(self::$forum_user['username']).'\''
                );

                ($hook = get_hook('fn_check_bans_qr_delete_online_user')) ? eval($hook) : null;
                $forum_db->query_build($query) or error(__FILE__, __LINE__);

                message(ForumCore::$lang['Ban message'].(($cur_ban['expire'] != '') ? ' '.sprintf(ForumCore::$lang['Ban message 2'], format_time($cur_ban['expire'], 1, null, null, true)) : '').(($cur_ban['message'] != '') ? ' '.ForumCore::$lang['Ban message 3'].'</p><p><strong>'.forum_htmlencode($cur_ban['message']).'</strong></p>' : '</p>').'<p>'.sprintf(ForumCore::$lang['Ban message 4'], '<a href="mailto:'.forum_htmlencode(ForumCore::$forum_config['o_admin_email']).'">'.forum_htmlencode(ForumCore::$forum_config['o_admin_email']).'</a>'));
            }
        }

        // If we removed any expired bans during our run-through, we need to regenerate the bans cache
        if ($bans_altered)
        {
            if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
                require FORUM_ROOT.'include/cache.php';

            generate_bans_cache();
        }
    }

}
