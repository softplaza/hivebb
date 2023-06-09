<?php
/**
 * Loads the minimum amount of data (eg: functions, database connection, config data, etc) necessary to integrate the site.
 *
 * @copyright (C) 2023 HiveBB, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package HiveBB
 */

namespace HiveBB;

use \HiveBB\ForumCore;

if (!defined('FORUM_ROOT'))
	exit('The constant FORUM_ROOT must be defined and point to a valid HiveBB installation root directory.');

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
	define('FORUM_REQUEST_AJAX', 1);
}

require FORUM_ROOT.'include/constants.php';

// If the cache directory is not specified, we use the default setting
if (!defined('FORUM_CACHE_DIR'))
	define('FORUM_CACHE_DIR', FORUM_ROOT.'cache/');

$ForumCore = new ForumCore;

// Record the start time (will be used to calculate the generation time for the page)
ForumCore::$forum_start = empty($_SERVER['REQUEST_TIME_FLOAT']) ? microtime(true) : (float) $_SERVER['REQUEST_TIME_FLOAT'];

// Load the functions script
require FORUM_ROOT.'include/functions.php';
// Load the Loader class
require FORUM_ROOT.'include/loader.php';

// Load UTF-8 functions
require FORUM_ROOT.'include/utf8/utf8.php';
require FORUM_ROOT.'include/utf8/ucwords.php';
require FORUM_ROOT.'include/utf8/trim.php';

// Reverse the effect of register_globals
forum_unregister_globals();

// Ignore any user abort requests
ignore_user_abort(true);

if (!defined('FORUM'))
	error('The file \'config.php\' doesn\'t exist or is corrupt.<br />Please run <a href="'.FORUM_ROOT.'admin/install.php">install.php</a> to install HiveBB first.');

// Make sure PHP reports all errors except E_NOTICE. HiveBB supports E_ALL, but a lot of scripts it may interact with, do not.
if (defined('FORUM_DEBUG'))
	error_reporting(E_ALL);
else
	error_reporting(E_ALL ^ E_NOTICE);

// Detect UTF-8 support in PCRE
if ((version_compare(PHP_VERSION, '5.1.0', '>=') || (version_compare(PHP_VERSION, '5.0.0-dev', '<=') && version_compare(PHP_VERSION, '4.4.0', '>='))) && @/**/preg_match('/\p{L}/u', 'a') !== FALSE)
{
	define('FORUM_SUPPORT_PCRE_UNICODE', 1);
}

// Force POSIX locale (to prevent functions such as strtolower() from messing up UTF-8 strings)
setlocale(LC_CTYPE, 'C');

// Load DB abstraction layer and connect
// Start a transaction
//$forum_db = new \SoftPlaza\HiveBB\DBLayer;
//$forum_db->start_transaction();

// Try to get cache or gen config
ForumCore::gen_config();

// If the request_uri is invalid try fix it
forum_fix_request_uri();

if (!isset($base_url))
{
	// Make an educated guess regarding base_url
	$base_url_guess = ((!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') ? 'https://' : 'http://').preg_replace('/:80$/', '', $_SERVER['HTTP_HOST']).str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
	if (substr($base_url_guess, -1) == '/')
		$base_url_guess = substr($base_url_guess, 0, -1);

	$base_url = $base_url_guess;
}

// Verify that we are running the proper database schema revision
if (defined('PUN') || !isset(ForumCore::$forum_config['o_database_revision']) || ForumCore::$forum_config['o_database_revision'] < FORUM_DB_REVISION || version_compare(ForumCore::$forum_config['o_cur_version'], FORUM_VERSION, '<'))
	error('Your HiveBB database is out-of-date and must be upgraded in order to continue.<br />Please run <a href="'.$base_url.'/admin/db_update.php">db_update.php</a> in order to complete the upgrade process.');

// A good place to add common functions for your extension
($hook = get_hook('es_essentials')) ? eval($hook) : null;

if (!defined('FORUM_MAX_POSTSIZE_BYTES'))
	define('FORUM_MAX_POSTSIZE_BYTES', 65535);

define('FORUM_ESSENTIALS_LOADED', 1);
