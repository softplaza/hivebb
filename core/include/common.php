<?php
/**
 * Loads common data and performs various functions necessary for the site to work properly.
 *
 * @copyright (C) 2023 HiveBB, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package HiveBB
 */

use \HiveBB\ForumCore;
use \HiveBB\ForumUser;

if (!defined('FORUM_ROOT'))
	exit('The constant FORUM_ROOT must be defined and point to a valid HiveBB installation root directory.');

if (!defined('FORUM_ESSENTIALS_LOADED'))
	require FORUM_ROOT.'include/essentials.php';

// Strip out "bad" UTF-8 characters
forum_remove_bad_characters();

// If a cookie name is not specified in config.php, we use the default (forum_cookie)
if (empty($cookie_name))
	$cookie_name = 'forum_cookie';

// Enable output buffering
if (!defined('FORUM_DISABLE_BUFFERING'))
{
	// For some very odd reason, "Norton Internet Security" unsets this
	$_SERVER['HTTP_ACCEPT_ENCODING'] = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';

	// Should we use gzip output compression?
	if (ForumCore::$forum_config['o_gzip'] && extension_loaded('zlib') && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false))
		ob_start('ob_gzhandler');
	else
		ob_start();
}

// Define standard date/time formats
ForumCore::$forum_time_formats = array(ForumCore::$forum_config['o_time_format'], 'H:i:s', 'H:i', 'g:i:s a', 'g:i a');
ForumCore::$forum_date_formats = array(ForumCore::$forum_config['o_date_format'], 'Y-m-d', 'Y-d-m', 'd-m-Y', 'm-d-Y', 'M j Y', 'jS M Y');

// Login and fetch user info
$ForumUser = new ForumUser;

// A good place to modify the URL scheme
($hook = get_hook('co_modify_url_scheme')) ? eval($hook) : null;

// Check if we are to display a maintenance message
if (ForumCore::$forum_config['o_maintenance'] && ForumUser::$forum_user['g_id'] > FORUM_ADMIN && !defined('FORUM_TURN_OFF_MAINT'))
	maintenance_message();

// Load cached updates info
if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN)
{
	if (file_exists(FORUM_CACHE_DIR.'cache_updates.php'))
		include FORUM_CACHE_DIR.'cache_updates.php';

	// Regenerate cache only if automatic updates are enabled and if the cache is more than 12 hours old
	if (ForumCore::$forum_config['o_check_for_updates'] == '1' && (!defined('FORUM_UPDATES_LOADED') || $forum_updates['cached'] < (time() - 43200)))
	{
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_updates_cache();
		require FORUM_CACHE_DIR.'cache_updates.php';
	}
}

// Check if current user is banned
ForumUser::check_bans();

// Update online list
ForumUser::update_users_online();

// Check to see if we logged in without a cookie being set
if (ForumUser::$forum_user['is_guest'] && isset($_GET['login']))
	message(ForumCore::$lang['No cookie']);

// If we're an administrator or moderator, make sure the CSRF token in $_POST is valid (token in post.php is dealt with in post.php)
if (
	!empty($_POST) && (isset($_POST['confirm_cancel']) || (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== generate_form_token(get_current_url()))) && !defined('FORUM_SKIP_CSRF_CONFIRM'))
	csrf_confirm_form();

($hook = get_hook('co_common')) ? eval($hook) : null;
