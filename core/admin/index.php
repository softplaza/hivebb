<?php
/**
 * Administration panel index page.
 *
 * Gives an overview of some statistics to administrators and moderators.
 *
 * @copyright (C) 2023 HiveBB, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package HiveBB
 */

use \HiveBB\ForumCore;
use \HiveBB\ForumUser;
use \HiveBB\DBLayer;

defined( 'ABSPATH' ) OR die();

require FORUM_ROOT.'include/common.php';
//require FORUM_ROOT.'include/common_admin.php';

($hook = get_hook('ain_start')) ? eval($hook) : null;

if (!is_admin())
	message(ForumCore::$lang['No permission']);

$section = isset($_GET['section']) ? $_GET['section'] : null;

$forum_db = new DBLayer;

if ($section == 'categories')
{
	require FORUM_ROOT . 'admin/categories.php';
}
else if ($section == 'forums')
{
	require FORUM_ROOT . 'admin/forums.php';
}
else
{
	// Load the admin.php language files
	ForumCore::add_lang('admin_index');
	ForumCore::add_lang('admin_common');

	// Show phpinfo() output
	if (isset($_GET['action']) && $_GET['action'] == 'phpinfo' && ForumUser::$forum_user['g_id'] == FORUM_ADMIN)
	{
		($hook = get_hook('ain_phpinfo_selected')) ? eval($hook) : null;

		// Is phpinfo() a disabled function?
		if (strpos(strtolower((string)@ini_get('disable_functions')), 'phpinfo') !== false)
			message(ForumCore::$lang['phpinfo disabled']);

		phpinfo();
		exit;
	}


	// Generate check for updates text block
	if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN)
	{
		if (ForumCore::$forum_config['o_check_for_updates'] == '1')
			$punbb_updates = ForumCore::$lang['Check for updates enabled'];
		else
		{
			// Get a list of installed hotfix extensions
			$query = array(
				'SELECT'	=> 'e.id',
				'FROM'		=> 'extensions AS e',
				'WHERE'		=> 'e.id LIKE \'hotfix_%\''
			);

			($hook = get_hook('ain_update_check_qr_get_hotfixes')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			$hotfixes = array();
			while ($row = $forum_db->fetch_assoc($result))
			{
				$hotfixes[] = urlencode($row['id']);
			}

			$punbb_updates = '<a href="http://punbb.informer.com/update/?version='.urlencode(ForumCore::$forum_config['o_cur_version']).'&amp;hotfixes='.implode(',', $hotfixes).'">'.ForumCore::$lang['Check for updates manual'].'</a>';
		}
	}


	// Get the server load averages (if possible)
	if (function_exists('sys_getloadavg') && is_array($load_averages = sys_getloadavg()))
	{
		array_walk($load_averages, function (&$v) {
			$v = forum_number_format(round($v, 2), 2);
		});
		$server_load = $load_averages[0].' '.$load_averages[1].' '.$load_averages[2];
	}
	else if (@/**/is_readable('/proc/loadavg'))
	{
		// We use @ just in case
		$fh = @/**/fopen('/proc/loadavg', 'r');
		$load_averages = @fread($fh, 64);
		@/**/fclose($fh);

		$load_averages = empty($load_averages) ? array() : explode(' ', $load_averages);
		$server_load = isset($load_averages[2]) ? forum_number_format(round($load_averages[0], 2), 2).' '.forum_number_format(round($load_averages[1], 2), 2).' '.forum_number_format(round($load_averages[2], 2), 2) : 'Not available';
	}
	else if (!in_array(PHP_OS, array('WINNT', 'WIN32')) && preg_match('/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/i', @exec('uptime'), $load_averages))
		$server_load = forum_number_format(round($load_averages[1], 2), 2).' '.forum_number_format(round($load_averages[2], 2), 2).' '.forum_number_format(round($load_averages[3], 2), 2);
	else
		$server_load = ForumCore::$lang['Not available'];


	// Get number of current visitors
	$query = array(
		'SELECT'	=> 'COUNT(o.user_id)',
		'FROM'		=> 'online AS o',
		'WHERE'		=> 'o.idle=0'
	);

	($hook = get_hook('ain_qr_get_users_online')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$num_online = $forum_db->result($result);

	// Calculate total db size/row count
	$result = $forum_db->query('SHOW TABLE STATUS FROM `'.$forum_db->db_name.'` LIKE \''.$forum_db->prefix.'%\'') or error(__FILE__, __LINE__);

	$total_records = $total_size = 0;
	while ($status = $forum_db->fetch_assoc($result))
	{
		$total_records += $status['Rows'];
		$total_size += $status['Data_length'] + $status['Index_length'];
	}

	$total_size = $total_size / 1024;

	if ($total_size > 1024)
		$total_size = forum_number_format($total_size / 1024, 2).' MB';
	else
		$total_size = forum_number_format($total_size, 2).' KB';

	// Check for the existance of various PHP opcode caches/optimizers
	if (ini_get('opcache.enable') && function_exists('opcache_invalidate'))
		$php_accelerator = '<a href="https://www.php.net/opcache/">Zend OPcache</a>';
	else if (ini_get('wincache.fcenabled'))
		$php_accelerator = '<a href="https://www.php.net/wincache/">Windows Cache for PHP</a>';
	else if (function_exists('mmcache'))
		$php_accelerator = '<a href="https://sourceforge.net/projects/turck-mmcache/">Turck MMCache</a>';
	else if (isset($_PHPA))
		$php_accelerator = '<a href="https://www.ioncube.com/">ionCube PHP Accelerator</a>';
	else if (ini_get('apc.enabled'))
		$php_accelerator ='<a href="https://web.archive.org/web/20160324235630/http://www.php.net/apc/">Alternative PHP Cache (APC)</a>';
	else if (ini_get('zend_optimizer.optimization_level'))
		$php_accelerator = '<a href="http://www.zend.com/products/zend_optimizer/">Zend Optimizer</a>';
	else if (ini_get('eaccelerator.enable'))
		$php_accelerator = '<a href="http://eaccelerator.net/">eAccelerator</a>';
	else if (ini_get('xcache.cacher'))
		$php_accelerator = '<a href="https://web.archive.org/web/20120224193029/http://xcache.lighttpd.net/">XCache</a>';
	else
		$php_accelerator = ForumCore::$lang['Not applicable'];

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index']))
	);
	if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN)
		ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Start'], forum_link(ForumCore::$forum_url['admin_index']));
	ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Information'], forum_link(ForumCore::$forum_url['admin_index']));

	($hook = get_hook('ain_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'start');
	define('FORUM_PAGE', 'admin-information');
	require FORUM_ROOT.'header.php';

	ForumCore::$forum_page['item_count'] = 0;

	($hook = get_hook('ain_main_output_start')) ? eval($hook) : null;


?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo ForumCore::$lang['Information head'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
<?php if (!empty($alert_items)): ?>
		<div id="admin-alerts" class="ct-set warn-set">
			<div class="ct-box warn-box">
				<h3 class="ct-legend hn warn"><span><?php echo ForumCore::$lang['Alerts'] ?></span></h3>
				<?php echo implode(' ', $alert_items)."\n" ?>
			</div>
		</div>
<?php endif; ?>
		<div class="ct-group">
			<img src="<?php echo FORUM_URL ?>/img/hive-bee.svg" style="width: 70px;float: left;margin-left: 30px;margin-top: 20px;">
<?php ($hook = get_hook('ain_pre_version')) ? eval($hook) : null; ?>
			<div class="ct-set group-item<?php echo ++ForumCore::$forum_page['item_count'] ?>">
				<div class="ct-box">
					<h3 class="ct-legend hn"><span><?php echo ForumCore::$lang['HiveBB version'] ?></span></h3>
					<ul class="data-list">
						<li><span>HiveBB <?php echo ForumCore::$forum_config['o_cur_version'] ?></span></li>
						<li><span>© 2023 HiveBB Forum</span></li>
						<li><span>Plugin Support: <a href="https://softplaza.net/">SoftPlaza.NET</a></span></li>
						<li><span>Download from: <a href="https://github.com/softplaza/hivebb">GitHub</a></span></li>
					</ul>
				</div>
			</div>
<?php ($hook = get_hook('ain_pre_community')) ? eval($hook) : null; ?>

<?php ($hook = get_hook('ain_pre_server_load')) ? eval($hook) : null; ?>
			<div class="ct-set group-item<?php echo ++ForumCore::$forum_page['item_count'] ?>">
				<div class="ct-box">
					<h3 class="ct-legend hn"><span><?php echo ForumCore::$lang['Server load'] ?></span></h3>
					<span><?php echo $server_load ?> (<?php echo $num_online.' '.ForumCore::$lang['users online']?>)</span>
				</div>
			</div>
<?php ($hook = get_hook('ain_pre_environment')) ? eval($hook) : null; if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN): ?>
			<div class="ct-set group-item<?php echo ++ForumCore::$forum_page['item_count'] ?>">
				<div class="ct-box">
					<h3 class="ct-legend hn"><span><?php echo ForumCore::$lang['Environment'] ?></span></h3>
					<ul class="data-list">
						<li><span><?php echo ForumCore::$lang['Operating system'] ?>: <?php echo PHP_OS ?></span></li>
						<li><span>PHP: <?php echo PHP_VERSION ?> - <a href="<?php echo pun_admin_link(ForumCore::$forum_url['admin_index']) ?>&action=phpinfo"><?php echo ForumCore::$lang['Show info'] ?></a></span></li>
						<li><span><?php echo ForumCore::$lang['Accelerator'] ?>: <?php echo $php_accelerator ?></span></li>
					</ul>
				</div>
			</div>
<?php ($hook = get_hook('ain_pre_database')) ? eval($hook) : null; ?>
			<div class="ct-set group-item<?php echo ++ForumCore::$forum_page['item_count'] ?>">
				<div class="ct-box">
					<h3 class="ct-legend hn"><span><?php echo ForumCore::$lang['Database'] ?></span></h3>
					<ul class="data-list">
						<li><span><?php echo implode(' ', $forum_db->get_version()) ?></span></li>
<?php if (isset($total_records) && isset($total_size)): ?>
						<li><span><?php echo ForumCore::$lang['Rows'] ?>: <?php echo forum_number_format($total_records) ?></span></li>
						<li><span><?php echo ForumCore::$lang['Size'] ?>: <?php echo $total_size ?></span></li>
<?php endif; ?>
					</ul>
				</div>
			</div>
<?php endif; ($hook = get_hook('ain_items_end')) ? eval($hook) : null; ?>
		</div>
	</div>
<?php

/*
	?>
		<div class="card mb-1">

			<div class="card-header">
				<h2 class="card-title mb-0"><?php echo ForumCore::$lang['Information head'] ?></h2>
			</div>

			<?php if (!empty($alert_items)): ?>
			<div class="card-body">
				<h3 class=""><span><?php echo ForumCore::$lang['Alerts'] ?></span></h3>
				<?php echo implode(' ', $alert_items)."\n" ?>
			</div>
			<?php endif; ?>

	<?php ($hook = get_hook('ain_pre_version')) ? eval($hook) : null; ?>

			<div class="card-body">
				<h3 class="ct-legend hn"><span><?php echo ForumCore::$lang['HiveBB version'] ?></span></h3>
				<ul class="data-list">
					<li><span>Based on HiveBB <?php echo ForumCore::$forum_config['o_cur_version'] ?></span></li>
					<li><span><?php echo ForumCore::$lang['Copyright message'] ?></span></li>
	<?php if (isset($HiveBB_updates)): ?>
					<li><span></span></li>
	<?php endif; ?>
				</ul>
			</div>

	<?php ($hook = get_hook('ain_pre_community')) ? eval($hook) : null; ?>

	<?php ($hook = get_hook('ain_pre_server_load')) ? eval($hook) : null; ?>

			<div class="card-body">
				<h3 class="ct-legend hn"><span><?php echo ForumCore::$lang['Server load'] ?></span></h3>
				<p><span><?php echo $server_load ?> (<?php echo $num_online.' '.ForumCore::$lang['users online']?>)</span></p>
			</div>

	<?php ($hook = get_hook('ain_pre_environment')) ? eval($hook) : null; 
	if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN): ?>

			<div class="card-body">
				<h3 class="ct-legend hn"><span><?php echo ForumCore::$lang['Environment'] ?></span></h3>
				<ul class="data-list">
					<li><span><?php echo ForumCore::$lang['Operating system'] ?>: <?php echo PHP_OS ?></span></li>
					<li><span>PHP: <?php echo PHP_VERSION ?> - <a href="<?php echo pun_admin_link(ForumCore::$forum_url['admin_index']) ?>&action=phpinfo"><?php echo ForumCore::$lang['Show info'] ?></a></span></li>
					<li><span><?php echo ForumCore::$lang['Accelerator'] ?>: <?php echo $php_accelerator ?></span></li>
				</ul>
			</div>

	<?php ($hook = get_hook('ain_pre_database')) ? eval($hook) : null; ?>

			<div class="card-body">
				<h3 class="ct-legend hn"><span><?php echo ForumCore::$lang['Database'] ?></span></h3>
				<ul class="data-list">
					<li><span><?php echo implode(' ', $forum_db->get_version()) ?></span></li>
	<?php if (isset($total_records) && isset($total_size)): ?>
					<li><span><?php echo ForumCore::$lang['Rows'] ?>: <?php echo forum_number_format($total_records) ?></span></li>
					<li><span><?php echo ForumCore::$lang['Size'] ?>: <?php echo $total_size ?></span></li>
	<?php endif; ?>
				</ul>
			</div>

	<?php endif; 
	($hook = get_hook('ain_items_end')) ? eval($hook) : null; ?>

		</div>

	<?php
*/
	($hook = get_hook('ain_end')) ? eval($hook) : null;

	require FORUM_ROOT.'footer.php';
}
