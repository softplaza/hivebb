<?php
/**
 * User search page.
 *
 * Allows administrators or moderators to search the existing users based on various criteria.
 *
 * @copyright (C) 2023 HiveBB, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package HiveBB
 */

use \HiveBB\ForumCore;
use \HiveBB\DBLayer;
use \HiveBB\ForumUser;

defined( 'ABSPATH' ) OR die();

require FORUM_ROOT.'include/common.php';

$section = isset($_GET['section']) ? $_GET['section'] : null;
$page = isset($_GET['page']) ? $_GET['page'] : null;

$forum_db = new DBLayer;

if ($section == 'groups')
{
	require FORUM_ROOT . 'admin/groups.php';
}
else if ($section == 'ranks')
{
	require FORUM_ROOT . 'admin/ranks.php';
}
else if ($section == 'bans')
{
	require FORUM_ROOT . 'admin/bans.php';
}
else
{
	($hook = get_hook('aus_start')) ? eval($hook) : null;

	if (!is_admin())
		message(ForumCore::$lang['No permission']);

	// Load the admin.php language file
	ForumCore::add_lang('admin_common');
	ForumCore::add_lang('admin_users');
	ForumCore::add_lang('admin_bans');
	ForumCore::add_lang('misc');

	if (isset($_POST['delete_users']))
	{
		if (ForumUser::$forum_user['g_id'] != FORUM_ADMIN)
			message(ForumCore::$lang['No permission']);

		if (empty($_POST['users']))
			message(ForumCore::$lang['No users selected']);

		($hook = get_hook('aus_delete_users_selected')) ? eval($hook) : null;

		if (!is_array($_POST['users']))
			$users = explode(',', $_POST['users']);
		else
			$users = array_keys($_POST['users']);

		$users = array_map('intval', $users);

		// We check to make sure there are no administrators in this list
		$query = array(
			'SELECT'	=> 'COUNT(u.id)',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.id IN ('.implode(',', $users).') AND u.group_id='.FORUM_ADMIN
		);

		($hook = get_hook('aus_delete_users_qr_check_for_admins')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($forum_db->result($result) > 0)
			message(ForumCore::$lang['Delete admin message']);

		// Setup form
		ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

		// Setup breadcrumbs
		ForumCore::$forum_page['crumbs'] = array(
			array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
			array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index'])),
			array(ForumCore::$lang['Users'], forum_link(ForumCore::$forum_url['admin_users'])),
			array(ForumCore::$lang['Searches'], forum_link(ForumCore::$forum_url['admin_users'])),
			ForumCore::$lang['Delete users']
		);

		($hook = get_hook('aus_delete_users_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE_SECTION', 'users');
		define('FORUM_PAGE', 'admin-users');
		require FORUM_ROOT.'header.php';

		($hook = get_hook('aus_delete_users_output_start')) ? eval($hook) : null;

	?>
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo ForumCore::$lang['Confirm delete'] ?></span></h2>
		</div>
		<div class="main-content main-frm">
			<div class="ct-box warn-box">
				<p class="warn"><?php echo ForumCore::$lang['Delete warning'] ?></p>
			</div>
			<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_users') ); ?>">
				<div class="hidden">
					<input type="hidden" name="page" value="<?php echo $page ?>">
					<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_users') ) ?>" />
					<input type="hidden" name="users" value="<?php echo implode(',', $users) ?>" />
				</div>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><span><?php echo ForumCore::$lang['Delete posts legend'] ?></span></legend>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box checkbox">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="delete_posts" value="1" checked="checked" /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Delete posts'] ?></span> <?php echo ForumCore::$lang['Delete posts label'] ?></label>
						</div>
					</div>
				</fieldset>
				<div class="frm-buttons">
					<span class="submit primary caution"><input type="submit" name="delete_users_comply" value="<?php echo ForumCore::$lang['Delete users'] ?>" /></span>
					<span class="cancel"><input type="submit" name="delete_users_cancel" value="<?php echo ForumCore::$lang['Cancel'] ?>" /></span>
				</div>
			</form>
		</div>
	<?php

		($hook = get_hook('aus_delete_users_end')) ? eval($hook) : null;

		require FORUM_ROOT.'footer.php';
	}

	else if (isset($_POST['ban_users']))
	{
		if (ForumUser::$forum_user['g_id'] != FORUM_ADMIN && (ForumUser::$forum_user['g_moderator'] != '1' || ForumUser::$forum_user['g_mod_ban_users'] == '0'))
			message(ForumCore::$lang['No permission']);

		if (empty($_POST['users']))
			message(ForumCore::$lang['No users selected']);

		($hook = get_hook('aus_ban_users_selected')) ? eval($hook) : null;

		if (!is_array($_POST['users']))
			$users = explode(',', $_POST['users']);
		else
			$users = array_keys($_POST['users']);

		$users = array_map('intval', $users);

		// We check to make sure there are no administrators in this list
		$query = array(
			'SELECT'	=> 'COUNT(u.id)',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.id IN ('.implode(',', $users).') AND u.group_id='.FORUM_ADMIN
		);
		($hook = get_hook('aus_ban_users_qr_check_for_admins')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($forum_db->result($result) > 0)
			message(ForumCore::$lang['Ban admin message']);

		// Setup form
		ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

		// Setup breadcrumbs
		ForumCore::$forum_page['crumbs'] = array(
			array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
			array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index']))
		);
		if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN)
			ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Users'], forum_link(ForumCore::$forum_url['admin_users']));
		ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Searches'], forum_link(ForumCore::$forum_url['admin_users']));
		ForumCore::$forum_page['crumbs'][] = ForumCore::$lang['Ban users'];

		($hook = get_hook('aus_ban_users_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE_SECTION', 'users');
		define('FORUM_PAGE', 'admin-users');
		require FORUM_ROOT.'header.php';

		($hook = get_hook('aus_ban_users_output_start')) ? eval($hook) : null;

	?>
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo ForumCore::$lang['Ban users'] ?></span></h2>
		</div>
		<div class="main-content main-frm">
			<div class="ct-box">
				<p><?php echo ForumCore::$lang['Mass ban info'] ?></p>
			</div>
			<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_users') ); ?>">
				<div class="hidden">
					<input type="hidden" name="page" value="<?php echo $page ?>">
					<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_users') ) ?>" />
					<input type="hidden" name="users" value="<?php echo implode(',', $users) ?>" />
				</div>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><span><?php echo ForumCore::$lang['Ban settings legend'] ?></span></legend>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Ban message label'] ?></span> <small><?php echo ForumCore::$lang['Ban message help'] ?></small></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="ban_message" size="50" maxlength="255" /></span>
						</div>
					</div>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Expire date label'] ?></span> <small><?php echo ForumCore::$lang['Expire date help'] ?></small></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="ban_expire" size="17" maxlength="10" /></span>
						</div>
					</div>
				</fieldset>
				<div class="frm-buttons">
					<span class="submit primary"><input type="submit" name="ban_users_comply" value="<?php echo ForumCore::$lang['Ban'] ?>" /></span>
				</div>
			</form>
		</div>
	<?php

		($hook = get_hook('aus_ban_users_end')) ? eval($hook) : null;

		require FORUM_ROOT.'footer.php';
	}

	else if (isset($_POST['change_group']))
	{
		if (ForumUser::$forum_user['g_id'] != FORUM_ADMIN)
			message(ForumCore::$lang['No permission']);

		if (empty($_POST['users']))
			message(ForumCore::$lang['No users selected']);

		($hook = get_hook('aus_change_group_selected')) ? eval($hook) : null;

		if (!is_array($_POST['users']))
			$users = explode(',', $_POST['users']);
		else
			$users = array_keys($_POST['users']);

		$users = array_map('intval', $users);

		// Setup form
		ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

		// Setup breadcrumbs
		ForumCore::$forum_page['crumbs'] = array(
			array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
			array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index'])),
			array(ForumCore::$lang['Users'], forum_link(ForumCore::$forum_url['admin_users'])),
			array(ForumCore::$lang['Searches'], forum_link(ForumCore::$forum_url['admin_users'])),
			ForumCore::$lang['Change group']
		);

		($hook = get_hook('aus_change_group_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE_SECTION', 'users');
		define('FORUM_PAGE', 'admin-users');
		require FORUM_ROOT.'header.php';

		($hook = get_hook('aus_change_group_output_start')) ? eval($hook) : null;

	?>
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo ForumCore::$lang['Change group head'] ?></span></h2>
		</div>
		<div class="main-content main-frm">
			<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_users') ); ?>">
				<div class="hidden">
					<input type="hidden" name="page" value="<?php echo $page ?>">
					<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_users') ) ?>" />
					<input type="hidden" name="users" value="<?php echo implode(',', $users) ?>" />
				</div>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><span><?php echo ForumCore::$lang['Move users legend'] ?></span></legend>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box select">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Move users to label'] ?></span></label><br />
							<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="move_to_group">
	<?php

		$query = array(
			'SELECT'	=> 'g.g_id, g.g_title',
			'FROM'		=> 'groups AS g',
			'WHERE'		=> 'g.g_id!='.FORUM_GUEST,
			'ORDER BY'	=> 'g.g_title'
		);

		($hook = get_hook('aus_change_group_qr_get_groups')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		while ($cur_group = $forum_db->fetch_assoc($result))
		{
			if ($cur_group['g_id'] == ForumCore::$forum_config['o_default_user_group'])	// Pre-select the default Members group
				echo "\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.forum_htmlencode($cur_group['g_title']).'</option>'."\n";
			else
				echo "\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.forum_htmlencode($cur_group['g_title']).'</option>'."\n";
		}

	?>
							</select></span>
						</div>
					</div>
				</fieldset>
				<div class="frm-buttons">
					<span class="submit primary"><input type="submit" name="change_group_comply" value="<?php echo ForumCore::$lang['Change group'] ?>" /></span>
					<span class="cancel"><input type="submit" name="change_group_cancel" value="<?php echo ForumCore::$lang['Cancel'] ?>" /></span>
				</div>
			</form>
		</div>
	<?php

		($hook = get_hook('aus_change_group_end')) ? eval($hook) : null;

		require FORUM_ROOT.'footer.php';
	}

	// Show IP statistics for a certain user ID
	else if (isset($_GET['ip_stats']))
	{
		$ip_stats = intval($_GET['ip_stats']);
		if ($ip_stats < 1)
			message(ForumCore::$lang['Bad request']);

		($hook = get_hook('aus_ip_stats_selected')) ? eval($hook) : null;

		$query = array(
			'SELECT'	=> 'p.poster_ip, MAX(p.posted) AS last_used, COUNT(p.id) AS used_times',
			'FROM'		=> 'posts AS p',
			'WHERE'		=> 'p.poster_id='.$ip_stats,
			'GROUP BY'	=> 'p.poster_ip',
			'ORDER BY'	=> 'last_used DESC'
		);
		($hook = get_hook('aus_ip_stats_qr_get_user_ips')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$founded_ips = array();
		while ($cur_ip = $forum_db->fetch_assoc($result))
		{
			$founded_ips[] = $cur_ip;
		}

		ForumCore::$forum_page['num_users'] = count($founded_ips);

		// Setup breadcrumbs
		ForumCore::$forum_page['crumbs'] = array(
			array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
			array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index']))
		);
		if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN)
			ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Users'], forum_link(ForumCore::$forum_url['admin_users']));
		ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Searches'], forum_link(ForumCore::$forum_url['admin_users']));
		ForumCore::$forum_page['crumbs'][] = ForumCore::$lang['User search results'];

		($hook = get_hook('aus_ip_stats_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE_SECTION', 'users');
		define('FORUM_PAGE', 'admin-iresults');
		require FORUM_ROOT.'header.php';

		// Set up table headers
		ForumCore::$forum_page['table_header'] = array();
		ForumCore::$forum_page['table_header']['ip'] = '<th class="tc'.count(ForumCore::$forum_page['table_header']).'" scope="col">'.ForumCore::$lang['IP address'].'</th>';
		ForumCore::$forum_page['table_header']['lastused'] = '<th class="tc'.count(ForumCore::$forum_page['table_header']).'" scope="col">'.ForumCore::$lang['Last used'].'</th>';
		ForumCore::$forum_page['table_header']['timesfound'] = '<th class="tc'.count(ForumCore::$forum_page['table_header']).'" scope="col">'.ForumCore::$lang['Times found'].'</th>';
		ForumCore::$forum_page['table_header']['actions'] = '<th class="tc'.count(ForumCore::$forum_page['table_header']).'" scope="col">'.ForumCore::$lang['Actions'].'</th>';

		($hook = get_hook('aus_ip_stats_output_start')) ? eval($hook) : null;

	?>
		<div class="main-head">
	<?php

		if (!empty(ForumCore::$forum_page['main_head_options']))
			echo "\n\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_head_options']).'</p>';

	?>
			<h2 class="hn"><span><?php printf(ForumCore::$lang['IP addresses found'], ForumCore::$forum_page['num_users']) ?></span></h2>
		</div>
		<div class="main-content main-forum">
			<table>
				<thead>
					<tr>
						<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['table_header'])."\n" ?>
					</tr>
				</thead>
				<tbody>
	<?php

		if (ForumCore::$forum_page['num_users'])
		{
			ForumCore::$forum_page['item_count'] = 0;

			foreach ($founded_ips as $cur_ip)
			{
				++ForumCore::$forum_page['item_count'];

				ForumCore::$forum_page['item_style'] = ((ForumCore::$forum_page['item_count'] % 2 != 0) ? 'odd' : 'even');
				if (ForumCore::$forum_page['item_count'] == 1)
					ForumCore::$forum_page['item_style'] .= ' row1';

				($hook = get_hook('aus_ip_stats_pre_row_generation')) ? eval($hook) : null;

				ForumCore::$forum_page['table_row'] = array();
				ForumCore::$forum_page['table_row']['ip'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"><a href="'.forum_link(ForumCore::$forum_url['get_host'], $cur_ip['poster_ip']).'">'.$cur_ip['poster_ip'].'</a></td>';
				ForumCore::$forum_page['table_row']['lastused'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'">'.format_time($cur_ip['last_used']).'</td>';
				ForumCore::$forum_page['table_row']['timesfound'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'">'.$cur_ip['used_times'].'</td>';
				ForumCore::$forum_page['table_row']['actions'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"><a href="'.forum_link(ForumCore::$forum_url['admin_users']).'?show_users='.$cur_ip['poster_ip'].'">'.ForumCore::$lang['Find more users'].'</a></td>';

				($hook = get_hook('aus_ip_stats_pre_row_output')) ? eval($hook) : null;

	?>
					<tr class="<?php echo ForumCore::$forum_page['item_style'] ?>">
						<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['table_row'])."\n" ?>
					</tr>
	<?php

			}
		}
		else
		{
			($hook = get_hook('aus_ip_stats_pre_no_results_row_generation')) ? eval($hook) : null;

			ForumCore::$forum_page['table_row'] = array();
			ForumCore::$forum_page['table_row']['ip'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'">'.ForumCore::$lang['No posts by user'].'</td>';
			ForumCore::$forum_page['table_row']['lastused'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"> - </td>';
			ForumCore::$forum_page['table_row']['timesfound'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"> - </td>';
			ForumCore::$forum_page['table_row']['actions'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"> - </td>';

			($hook = get_hook('aus_ip_stats_pre_no_results_row_output')) ? eval($hook) : null;

	?>
					<tr class="odd row1">
						<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['table_row'])."\n" ?>
					</tr>
	<?php

		}


	?>
				</tbody>
			</table>
		</div>
		<div class="main-foot">
	<?php

		if (!empty(ForumCore::$forum_page['main_foot_options']))
			echo "\n\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_foot_options']).'</p>';

	?>
			<h2 class="hn"><span><?php printf(ForumCore::$lang['IP addresses found'], ForumCore::$forum_page['num_users']) ?></span></h2>
		</div>
	<?php

		($hook = get_hook('aus_ip_stats_end')) ? eval($hook) : null;

		require FORUM_ROOT.'footer.php';
	}

	// Show users that have at one time posted with the specified IP address
	else if (isset($_GET['show_users']))
	{
		$ip = $_GET['show_users'];

		if (empty($ip) || (!preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $ip) && !preg_match('/^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$/', $ip)))
			message(ForumCore::$lang['Invalid IP address']);

		($hook = get_hook('aus_show_users_selected')) ? eval($hook) : null;

		// Load the misc.php language file
		require FORUM_ROOT.'lang/'.ForumUser::$forum_user['language'].'/misc.php';

		$query = array(
			'SELECT'	=> 'DISTINCT p.poster_id, p.poster',
			'FROM'		=> 'posts AS p',
			'WHERE'		=> 'p.poster_ip=\''.$forum_db->escape($ip).'\'',
			'ORDER BY'	=> 'p.poster DESC'
		);

		($hook = get_hook('aus_show_users_qr_get_users_matching_ip')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$users = array();
		while ($cur_user = $forum_db->fetch_assoc($result))
		{
			$users[] = $cur_user;
		}

		ForumCore::$forum_page['num_users'] = count($users);

		// Setup breadcrumbs
		ForumCore::$forum_page['crumbs'] = array(
			array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
			array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index']))
		);
		if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN)
			ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Users'], forum_link(ForumCore::$forum_url['admin_users']));
		ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Searches'], forum_link(ForumCore::$forum_url['admin_users']));
		ForumCore::$forum_page['crumbs'][] = ForumCore::$lang['User search results'];

		($hook = get_hook('aus_show_users_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE_SECTION', 'users');
		define('FORUM_PAGE', 'admin-uresults');
		require FORUM_ROOT.'header.php';

		// Set up table headers
		ForumCore::$forum_page['table_header'] = array();
		ForumCore::$forum_page['table_header']['username'] = '<th class="tc'.count(ForumCore::$forum_page['table_header']).'" scope="col">'.ForumCore::$lang['User information'].'</th>';
		ForumCore::$forum_page['table_header']['title'] = '<th class="tc'.count(ForumCore::$forum_page['table_header']).'" scope="col">'.ForumCore::$lang['Title column'].'</th>';
		ForumCore::$forum_page['table_header']['posts'] = '<th class="tc'.count(ForumCore::$forum_page['table_header']).'" scope="col">'.ForumCore::$lang['Posts'].'</th>';
		ForumCore::$forum_page['table_header']['actions'] = '<th class="tc'.count(ForumCore::$forum_page['table_header']).'" scope="col">'.ForumCore::$lang['Actions'].'</th>';
		ForumCore::$forum_page['table_header']['select'] = '<th class="tc'.count(ForumCore::$forum_page['table_header']).'" scope="col">'.ForumCore::$lang['Select'] .'</th>';

		if (ForumCore::$forum_page['num_users'] > 0)
			ForumCore::$forum_page['main_head_options']['select'] = ForumCore::$forum_page['main_foot_options']['select'] = '<span class="select-all js_link" data-check-form="aus-show-users-results-form">'.ForumCore::$lang['Select all'].'</span>';

		($hook = get_hook('aus_show_users_output_start')) ? eval($hook) : null;

	?>
		<div class="main-head">
	<?php

		if (!empty(ForumCore::$forum_page['main_head_options']))
			echo "\n\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_head_options']).'</p>';

	?>
			<h2 class="hn"><span><?php printf(ForumCore::$lang['Users found'], ForumCore::$forum_page['num_users']) ?></span></h2>
		</div>
		<form id="aus-show-users-results-form" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo pun_admin_link(ForumCore::$forum_url['admin_users']); ?>">
		<div class="main-content main-frm">
			<div class="hidden">
				<input type="hidden" name="page" value="<?php echo $page ?>">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( pun_admin_link(ForumCore::$forum_url['admin_users']) ); ?>" />
			</div>
			<table>
				<thead>
					<tr>
						<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['table_header'])."\n" ?>
					</tr>
				</thead>
				<tbody>
	<?php

		if (ForumCore::$forum_page['num_users'] > 0)
		{
			ForumCore::$forum_page['item_count'] = 0;

			// Loop through users and print out some info
			foreach ($users as $user)
			{
				$query = array(
					'SELECT'	=> 'u.id, u.username, u.email, u.title, u.num_posts, u.admin_note, g.g_id, g.g_user_title',
					'FROM'		=> 'users AS u',
					'JOINS'		=> array(
						array(
							'INNER JOIN'	=> 'groups AS g',
							'ON'			=> 'g.g_id=u.group_id'
						)
					),
					'WHERE'		=> 'u.id>1 AND u.id='.$user['poster_id']
				);

				($hook = get_hook('aus_show_users_qr_get_user_details')) ? eval($hook) : null;
				$result2 = $forum_db->query_build($query) or error(__FILE__, __LINE__);

				++ForumCore::$forum_page['item_count'];

				ForumCore::$forum_page['item_style'] = ((ForumCore::$forum_page['item_count'] % 2 != 0) ? 'odd' : 'even');
				if (ForumCore::$forum_page['item_count'] == 1)
					ForumCore::$forum_page['item_style'] .= ' row1';

				($hook = get_hook('aus_show_users_pre_row_generation')) ? eval($hook) : null;

				if ($user_data = $forum_db->fetch_assoc($result2))
				{
					ForumCore::$forum_page['table_row'] = array();
					ForumCore::$forum_page['table_row']['username'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"><span><a href="'.forum_link(ForumCore::$forum_url['user'], $user_data['id']).'">'.forum_htmlencode($user_data['username']).'</a></span><span class="usermail"><a href="mailto:'.forum_htmlencode($user_data['email']).'">'.forum_htmlencode($user_data['email']).'</a></span>'.(($user_data['admin_note'] != '') ? '<span class="usernote">'.ForumCore::$lang['Admin note'].' '.forum_htmlencode($user_data['admin_note']).'</span>' : '').'</td>';
					ForumCore::$forum_page['table_row']['title'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'">'.get_title($user_data).'</td>';
					ForumCore::$forum_page['table_row']['posts'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'">'.forum_number_format($user_data['num_posts']).'</td>';
					ForumCore::$forum_page['table_row']['actions'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"><span><a href="'.forum_link(ForumCore::$forum_url['admin_users']).'?ip_stats='.$user_data['id'].'">'.ForumCore::$lang['View IP stats'].'</a></span> <span><a href="'.forum_link(ForumCore::$forum_url['search_user_posts'], $user_data['id']).'">'.ForumCore::$lang['Show posts'].'</a></span></td>';
					ForumCore::$forum_page['table_row']['select'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"><input type="checkbox" name="users['.$user_data['id'].']" value="1" /></td>';
				}
				else
				{
					ForumCore::$forum_page['table_row'] = array();
					ForumCore::$forum_page['table_row']['username'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'">'.forum_htmlencode($user['poster']).'</td>';
					ForumCore::$forum_page['table_row']['title'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'">'.ForumCore::$lang['Guest'].'</td>';
					ForumCore::$forum_page['table_row']['posts'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"> - </td>';
					ForumCore::$forum_page['table_row']['actions'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"> - </td>';
					ForumCore::$forum_page['table_row']['select'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"> - </td>';
				}

				($hook = get_hook('aus_show_users_pre_row_output')) ? eval($hook) : null;

	?>
					<tr class="<?php echo ForumCore::$forum_page['item_style'] ?>">
						<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['table_row'])."\n" ?>
					</tr>
	<?php

			}
		}
		else
		{
				($hook = get_hook('aus_show_users_pre_no_results_row_generation')) ? eval($hook) : null;

				ForumCore::$forum_page['table_row'] = array();
				ForumCore::$forum_page['table_row']['username'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'">'.ForumCore::$lang['Cannot find IP'].'</td>';
				ForumCore::$forum_page['table_row']['title'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"> - </td>';
				ForumCore::$forum_page['table_row']['posts'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"> - </td>';
				ForumCore::$forum_page['table_row']['actions'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"> - </td>';
				ForumCore::$forum_page['table_row']['select'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"> - </td>';

				($hook = get_hook('aus_show_users_pre_no_results_row_output')) ? eval($hook) : null;

	?>
					<tr class="odd row1">
						<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['table_row'])."\n" ?>
					</tr>
	<?php

		}

	?>
				</tbody>
			</table>
		</div>
	<?php

		// Setup control buttons
		ForumCore::$forum_page['mod_options'] = array();

		if (ForumCore::$forum_page['num_users'] > 0)
		{
			if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN || (ForumUser::$forum_user['g_moderator'] == '1' && ForumUser::$forum_user['g_mod_ban_users'] == '1'))
				ForumCore::$forum_page['mod_options']['ban'] = '<span class="submit'.((empty(ForumCore::$forum_page['mod_options'])) ? ' first-item' : '').'"><input type="submit" name="ban_users" value="'.ForumCore::$lang['Ban'].'" /></span>';

			if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN)
			{
				ForumCore::$forum_page['mod_options']['delete'] = '<span class="submit'.((empty(ForumCore::$forum_page['mod_options'])) ? ' first-item' : '').'"><input type="submit" name="delete_users" value="'.ForumCore::$lang['Delete'].'" /></span>';
				ForumCore::$forum_page['mod_options']['change_group'] = '<span class="submit'.((empty(ForumCore::$forum_page['mod_options'])) ? ' first-item' : '').'"><input type="submit" name="change_group" value="'.ForumCore::$lang['Change group'].'" /></span>';
			}
		}

		($hook = get_hook('aus_show_users_pre_moderation_buttons')) ? eval($hook) : null;

		if (!empty(ForumCore::$forum_page['mod_options']))
		{
	?>
		<div class="main-options gen-content">
			<p class="options"><?php echo implode(' ', ForumCore::$forum_page['mod_options']) ?></p>
		</div>
	<?php

		}

	?>
		</form>
		<div class="main-foot">
	<?php

		if (!empty(ForumCore::$forum_page['main_foot_options']))
			echo "\n\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_foot_options']).'</p>';

	?>
			<h2 class="hn"><span><?php printf(ForumCore::$lang['Users found'], ForumCore::$forum_page['num_users']) ?></span></h2>
		</div>
	<?php

		// Init JS helper for select-all
		//$forum_loader->add_js('PUNBB.common.addDOMReadyEvent(PUNBB.common.initToggleCheckboxes);', array('type' => 'inline'));

		($hook = get_hook('aus_show_users_end')) ? eval($hook) : null;

		require FORUM_ROOT.'footer.php';
	}

	else if (isset($_GET['find_user']))
	{
		$form = isset($_GET['form']) ? $_GET['form'] : array();

		// trim() all elements in $form
		$form = array_map('forum_trim', $form);
		$conditions = $query_str = array();

		//Check up for order_by and direction values
		$order_by = isset($_GET['order_by']) ? forum_trim($_GET['order_by']) : null;
		$direction = isset($_GET['direction']) ? forum_trim($_GET['direction']) : null;
		if ($order_by === null || $direction === null)
			message(ForumCore::$lang['Bad request']);

		if (!in_array($order_by, array('username', 'email', 'num_posts', 'num_posts', 'registered')) || !in_array($direction, array('ASC', 'DESC')))
			message(ForumCore::$lang['Bad request']);

		($hook = get_hook('aus_find_user_selected')) ? eval($hook) : null;

		$query_str[] = 'order_by='.$order_by;
		$query_str[] = 'direction='.$direction;

		$posts_greater = isset($_GET['posts_greater']) ? forum_trim($_GET['posts_greater']) : '';
		$posts_less = isset($_GET['posts_less']) ? forum_trim($_GET['posts_less']) : '';
		$last_post_after = isset($_GET['last_post_after']) ? forum_trim($_GET['last_post_after']) : '';
		$last_post_before = isset($_GET['last_post_before']) ? forum_trim($_GET['last_post_before']) : '';
		$registered_after = isset($_GET['registered_after']) ? forum_trim($_GET['registered_after']) : '';
		$registered_before = isset($_GET['registered_before']) ? forum_trim($_GET['registered_before']) : '';
		$user_group = isset($_GET['user_group']) ? intval($_GET['user_group']) : -1;

		$query_str[] = 'user_group='.$user_group;

		if ((!empty($posts_greater) || !empty($posts_less)) && !ctype_digit($posts_greater.$posts_less))
			message(ForumCore::$lang['Non numeric value message']);

		// Try to convert date/time to timestamps
		if ($last_post_after != '')
		{
			$query_str[] = 'last_post_after='.$last_post_after;

			$last_post_after = strtotime($last_post_after);
			if ($last_post_after === false || $last_post_after == -1)
				message(ForumCore::$lang['Invalid date/time message']);

			$conditions[] = 'u.last_post>'.$last_post_after;
		}
		if ($last_post_before != '')
		{
			$query_str[] = 'last_post_before='.$last_post_before;

			$last_post_before = strtotime($last_post_before);
			if ($last_post_before === false || $last_post_before == -1)
				message(ForumCore::$lang['Invalid date/time message']);

			$conditions[] = 'u.last_post<'.$last_post_before;
		}
		if ($registered_after != '')
		{
			$query_str[] = 'registered_after='.$registered_after;

			$registered_after = strtotime($registered_after);
			if ($registered_after === false || $registered_after == -1)
				message(ForumCore::$lang['Invalid date/time message']);

			$conditions[] = 'u.registered>'.$registered_after;
		}
		if ($registered_before != '')
		{
			$query_str[] = 'registered_before='.$registered_before;

			$registered_before = strtotime($registered_before);
			if ($registered_before === false || $registered_before == -1)
				message(ForumCore::$lang['Invalid date/time message']);

			$conditions[] = 'u.registered<'.$registered_before;
		}

		$like_command = 'LIKE';
		foreach ($form as $key => $input)
		{
			if ($input != '' && in_array($key, array('username', 'email', 'title', 'realname', 'url', 'jabber', 'icq', 'msn', 'aim', 'yahoo', 'location', 'signature', 'admin_note')))
			{
				$conditions[] = 'u.'.$forum_db->escape($key).' '.$like_command.' \''.$forum_db->escape(str_replace('*', '%', $input)).'\'';
				$query_str[] = 'form%5B'.$key.'%5D='.urlencode($input);
			}
		}

		if ($posts_greater != '')
		{
			$query_str[] = 'posts_greater='.$posts_greater;
			$conditions[] = 'u.num_posts>'.$posts_greater;
		}
		if ($posts_less != '')
		{
			$query_str[] = 'posts_less='.$posts_less;
			$conditions[] = 'u.num_posts<'.$posts_less;
		}

		if ($user_group > -1)
			$conditions[] = 'u.group_id='.intval($user_group);

		if (empty($conditions))
			message(ForumCore::$lang['No search terms message']);

		// Load the misc.php language file
		require FORUM_ROOT.'lang/'.ForumUser::$forum_user['language'].'/misc.php';

		// Fetch user count
		$query = array(
			'SELECT'	=> 'COUNT(id)',
			'FROM'		=> 'users AS u',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'		=> 'groups AS g',
					'ON'			=> 'g.g_id=u.group_id'
				)
			),
			'WHERE'		=> 'u.id>1 AND '.implode(' AND ', $conditions)
		);

		($hook = get_hook('aus_find_user_qr_count_find_users')) ? eval($hook) : null;

		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		ForumCore::$forum_page['num_users'] = $forum_db->result($result);
		ForumCore::$forum_page['num_pages'] = ceil(ForumCore::$forum_page['num_users'] / ForumUser::$forum_user['disp_topics']);
		ForumCore::$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > ForumCore::$forum_page['num_pages']) ? 1 : $_GET['p'];
		ForumCore::$forum_page['start_from'] = ForumUser::$forum_user['disp_topics'] * (ForumCore::$forum_page['page'] - 1);
		ForumCore::$forum_page['finish_at'] = min((ForumUser::$forum_user['disp_topics']), (ForumCore::$forum_page['num_users']));

		// Setup breadcrumbs
		ForumCore::$forum_page['crumbs'] = array(
			array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
			array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index']))
		);
		if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN)
			ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Users'], forum_link(ForumCore::$forum_url['admin_users']));
		ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Searches'], forum_link(ForumCore::$forum_url['admin_users']));
		ForumCore::$forum_page['crumbs'][] = ForumCore::$lang['User search results'];

		// Generate paging
		ForumCore::$forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">'.ForumCore::$lang['Pages'].'</span> '.paginate(ForumCore::$forum_page['num_pages'], ForumCore::$forum_page['page'], ForumCore::$forum_url['admin_users'].'?find_user=&amp;'.implode('&amp;', $query_str), ForumCore::$lang['Paging separator'], null, true).'</p>';

		($hook = get_hook('aus_find_user_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE_SECTION', 'users');
		define('FORUM_PAGE', 'admin-uresults');
		require FORUM_ROOT.'header.php';

		// Set up table headers
		ForumCore::$forum_page['table_header'] = array();
		ForumCore::$forum_page['table_header']['username'] = '<th class="tc'.count(ForumCore::$forum_page['table_header']).'" scope="col">'.ForumCore::$lang['User information'].'</th>';
		ForumCore::$forum_page['table_header']['title'] = '<th class="tc'.count(ForumCore::$forum_page['table_header']).'" scope="col">'.ForumCore::$lang['Title column'].'</th>';
		ForumCore::$forum_page['table_header']['posts'] = '<th class="tc'.count(ForumCore::$forum_page['table_header']).'" scope="col">'.ForumCore::$lang['Posts'].'</th>';
		ForumCore::$forum_page['table_header']['actions'] = '<th class="tc'.count(ForumCore::$forum_page['table_header']).'" scope="col">'.ForumCore::$lang['Actions'].'</th>';
		ForumCore::$forum_page['table_header']['select'] = '<th class="tc'.count(ForumCore::$forum_page['table_header']).'" scope="col">'.ForumCore::$lang['Select'] .'</th>';

		if (ForumCore::$forum_page['num_users'] > 0)
			ForumCore::$forum_page['main_head_options']['select'] = ForumCore::$forum_page['main_foot_options']['select'] = '<span class="select-all js_link" data-check-form="aus-find-user-results-form">'.ForumCore::$lang['Select all'].'</span>';

		($hook = get_hook('aus_find_user_output_start')) ? eval($hook) : null;

	?>
		<div class="main-head">
	<?php

		if (!empty(ForumCore::$forum_page['main_head_options']))
			echo "\n\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_head_options']).'</p>';

	?>
			<h2 class="hn"><span><?php printf(ForumCore::$lang['Users found'], ForumCore::$forum_page['num_users']) ?></span></h2>
		</div>
		<form id="aus-find-user-results-form" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo pun_admin_link(ForumCore::$forum_url['admin_users']); ?>">
		<div class="main-content main-forum">
			<div class="hidden">
				<input type="hidden" name="page" value="<?php echo $page ?>">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( pun_admin_link(ForumCore::$forum_url['admin_users']) ); ?>" />
			</div>
			<table>
				<thead>
					<tr>
						<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['table_header'])."\n" ?>
					</tr>
				</thead>
				<tbody>
	<?php

		// Find any users matching the conditions
		$query = array(
			'SELECT'	=> 'u.id, u.username, u.email, u.title, u.num_posts, u.admin_note, g.g_id, g.g_user_title',
			'FROM'		=> 'users AS u',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'		=> 'groups AS g',
					'ON'			=> 'g.g_id=u.group_id'
				)
			),
			'WHERE'		=> 'u.id>1 AND '.implode(' AND ', $conditions),
			'ORDER BY'	=> $order_by.' '.$direction,
			'LIMIT'		=> ForumCore::$forum_page['start_from'].', '.ForumCore::$forum_page['finish_at']
		);

		($hook = get_hook('aus_find_user_qr_find_users')) ? eval($hook) : null;

		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if (ForumCore::$forum_page['num_users'] > 0)
		{
			ForumCore::$forum_page['item_count'] = 0;

			while ($user_data = $forum_db->fetch_assoc($result))
			{
				++ForumCore::$forum_page['item_count'];

				// This script is a special case in that we want to display "Not verified" for non-verified users
				if (($user_data['g_id'] == '' || $user_data['g_id'] == FORUM_UNVERIFIED) && $user_data['title'] != ForumCore::$lang['Banned'])
					$user_title = '<strong>'.ForumCore::$lang['Not verified'].'</strong>';
				else
					$user_title = get_title($user_data);

				ForumCore::$forum_page['item_style'] = ((ForumCore::$forum_page['item_count'] % 2 != 0) ? 'odd' : 'even');
				if (ForumCore::$forum_page['item_count'] == 1)
					ForumCore::$forum_page['item_style'] .= ' row1';

				($hook = get_hook('aus_find_user_pre_row_generation')) ? eval($hook) : null;

				ForumCore::$forum_page['table_row'] = array();
				ForumCore::$forum_page['table_row']['username'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"><span><a href="'.forum_link(ForumCore::$forum_url['user'], $user_data['id']).'">'.forum_htmlencode($user_data['username']).'</a></span><span class="usermail"><a href="mailto:'.forum_htmlencode($user_data['email']).'">'.forum_htmlencode($user_data['email']).'</a></span>'.(($user_data['admin_note'] != '') ? '<span class="usernote">'.ForumCore::$lang['Admin note'].' '.forum_htmlencode($user_data['admin_note']).'</span>' : '').'</td>';
				ForumCore::$forum_page['table_row']['title'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'">'.$user_title.'</td>';
				ForumCore::$forum_page['table_row']['posts'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'">'.forum_number_format($user_data['num_posts']).'</td>';
				ForumCore::$forum_page['table_row']['actions'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"><span><a href="'.forum_link(ForumCore::$forum_url['admin_users']).'?ip_stats='.$user_data['id'].'">'.ForumCore::$lang['View IP stats'].'</a></span> <span><a href="'.forum_link(ForumCore::$forum_url['search_user_posts'], $user_data['id']).'">'.ForumCore::$lang['Show posts'].'</a></span></td>';
				ForumCore::$forum_page['table_row']['select'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"><input type="checkbox" name="users['.$user_data['id'].']" value="1" /></td>';

				($hook = get_hook('aus_find_user_pre_row_output')) ? eval($hook) : null;

	?>
					<tr class="<?php echo ForumCore::$forum_page['item_style'] ?>">
						<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['table_row'])."\n" ?>
					</tr>
	<?php
			}
		}
		else
		{
				($hook = get_hook('aus_find_user_pre_no_results_row_generation')) ? eval($hook) : null;

				ForumCore::$forum_page['table_row'] = array();
				ForumCore::$forum_page['table_row']['username'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'">'.ForumCore::$lang['No match'].'</td>';
				ForumCore::$forum_page['table_row']['title'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"> - </td>';
				ForumCore::$forum_page['table_row']['posts'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"> - </td>';
				ForumCore::$forum_page['table_row']['actions'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"> - </td>';
				ForumCore::$forum_page['table_row']['select'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"> - </td>';

				($hook = get_hook('aus_find_user_pre_no_results_row_output')) ? eval($hook) : null;
	?>
					<tr class="odd row1">
						<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['table_row'])."\n" ?>
					</tr>
	<?php
		}
	?>
				</tbody>
			</table>
		</div>
	<?php

		// Setup control buttons
		ForumCore::$forum_page['mod_options'] = array();

		if (ForumCore::$forum_page['num_users'] > 0)
		{
			if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN || (ForumUser::$forum_user['g_moderator'] == '1' && ForumUser::$forum_user['g_mod_ban_users'] == '1'))
				ForumCore::$forum_page['mod_options']['ban'] = '<span class="submit'.((empty(ForumCore::$forum_page['mod_options'])) ? ' first-item' : '').'"><input type="submit" name="ban_users" value="'.ForumCore::$lang['Ban'].'" /></span>';

			if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN)
			{
				ForumCore::$forum_page['mod_options']['delete'] = '<span class="submit'.((empty(ForumCore::$forum_page['mod_options'])) ? ' first-item' : '').'"><input type="submit" name="delete_users" value="'.ForumCore::$lang['Delete'].'" /></span>';
				ForumCore::$forum_page['mod_options']['change_group'] = '<span class="submit'.((empty(ForumCore::$forum_page['mod_options'])) ? ' first-item' : '').'"><input type="submit" name="change_group" value="'.ForumCore::$lang['Change group'].'" /></span>';
			}
		}

		($hook = get_hook('aus_find_user_pre_moderation_buttons')) ? eval($hook) : null;

		if (!empty(ForumCore::$forum_page['mod_options']))
		{
	?>
		<div class="main-options gen-content">
			<p class="options"><?php echo implode(' ', ForumCore::$forum_page['mod_options']) ?></p>
		</div>
	<?php

		}

	?>
		</form>
		<div class="main-foot">
	<?php

		if (!empty(ForumCore::$forum_page['main_foot_options']))
			echo "\n\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_foot_options']).'</p>';

	?>
			<h2 class="hn"><span><?php printf(ForumCore::$lang['Users found'], ForumCore::$forum_page['num_users']) ?></span></h2>
		</div>
	<?php

		// Init JS helper for select-all
		//$forum_loader->add_js('PUNBB.common.addDOMReadyEvent(PUNBB.common.initToggleCheckboxes);', array('type' => 'inline'));

		($hook = get_hook('aus_find_user_end')) ? eval($hook) : null;

		require FORUM_ROOT.'footer.php';
	}
	else
	{

		($hook = get_hook('aus_new_action')) ? eval($hook) : null;

		// Setup form
		ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

		// Setup breadcrumbs
		ForumCore::$forum_page['crumbs'] = array(
			array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
			array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index']))
		);
		if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN)
			ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Users'], forum_link(ForumCore::$forum_url['admin_users']));
		ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Searches'], forum_link(ForumCore::$forum_url['admin_users']));

		($hook = get_hook('aus_search_form_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE_SECTION', 'users');
		define('FORUM_PAGE', 'admin-users');
		require FORUM_ROOT.'header.php';

		($hook = get_hook('aus_search_form_output_start')) ? eval($hook) : null;

	?>
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo ForumCore::$lang['Search head'] ?></span></h2>
		</div>
		<div class="main-content main-frm">
			<form class="frm-form" method="get" accept-charset="utf-8" action="">
				<div class="hidden">
					<input type="hidden" name="page" value="<?php echo $page ?>">
				</div>
				<div class="content-head">
					<h3 class="hn"><span><?php echo ForumCore::$lang['User search head'] ?></span></h3>
				</div>
	<?php ($hook = get_hook('aus_search_form_pre_user_details_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Searches personal legend'] ?></strong></legend>
	<?php ($hook = get_hook('aus_search_form_pre_username')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Username label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[username]" size="35" maxlength="25" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_user_title')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Title label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[title]" size="35" maxlength="50" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_realname')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Real name label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[realname]" size="35" maxlength="40" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_location')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Location label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[location]" size="35" maxlength="30" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_signature')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Signature label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[signature]" size="35" maxlength="512" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_admin_note')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Admin note label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[admin_note]" size="35" maxlength="30" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_user_details_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
	<?php ($hook = get_hook('aus_search_form_user_details_fieldset_end')) ? eval($hook) : null; ?>
	<?php ForumCore::$forum_page['item_count'] = 0; ?>
	<?php ($hook = get_hook('aus_search_form_pre_user_contacts_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Searches contact legend'] ?></strong></legend>
	<?php ($hook = get_hook('aus_search_form_pre_email')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['E-mail address label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[email]" size="35" maxlength="80" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_website')) ? eval($hook) : null; ?>				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Website label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[url]" size="35" maxlength="100" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_jabber')) ? eval($hook) : null; ?>				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Jabber label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[jabber]" size="35" maxlength="80" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_icq')) ? eval($hook) : null; ?>				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['ICQ label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[icq]" size="12" maxlength="12" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_msn')) ? eval($hook) : null; ?>				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['MSN Messenger label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[msn]" size="35" maxlength="80" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_aim')) ? eval($hook) : null; ?>				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['AOL IM label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[aim]" size="20" maxlength="20" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_yahoo')) ? eval($hook) : null; ?>				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Yahoo Messenger label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[yahoo]" size="20" maxlength="20" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_user_contacts_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
	<?php ($hook = get_hook('aus_search_form_user_contacts_fieldset_end')) ? eval($hook) : null; ?>
	<?php ForumCore::$forum_page['item_count'] = 0; ?>
	<?php ($hook = get_hook('aus_search_form_pre_user_activity_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Searches activity legend'] ?></strong></legend>
	<?php ($hook = get_hook('aus_search_form_pre_min_posts')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box frm-short text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['More posts label'] ?></span> <small><?php echo ForumCore::$lang['Number of posts help'] ?></small></label><br />
							<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="posts_greater" size="5" maxlength="8" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_max_posts')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box frm-short text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Less posts label'] ?></span> <small><?php echo ForumCore::$lang['Number of posts help'] ?></small></label><br />
							<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="posts_less" size="5" maxlength="8" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_last_post_after')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Last post after label'] ?></span> <small><?php echo ForumCore::$lang['Date format help'] ?></small></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="last_post_after" size="24" maxlength="19" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_last_post_before')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Last post before label'] ?></span><small><?php echo ForumCore::$lang['Date format help'] ?></small></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="last_post_before" size="24" maxlength="19" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_registered_after')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Registered after label'] ?></span> <small><?php echo ForumCore::$lang['Date format help'] ?></small></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="registered_after" size="24" maxlength="19" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_registered_before')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Registered before label'] ?></span> <small><?php echo ForumCore::$lang['Date format help'] ?></small></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="registered_before" size="24" maxlength="19" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_user_activity_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
	<?php

	($hook = get_hook('aus_search_form_user_activity_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

	?>
				<div class="content-head">
					<h3 class="hn"><span><?php echo ForumCore::$lang['User results head'] ?></span></h3>
				</div>
	<?php ($hook = get_hook('aus_search_form_pre_results_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['User results legend'] ?></strong></legend>
	<?php ($hook = get_hook('aus_search_form_pre_sort_by')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box select">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Order by label'] ?></span></label><br />
							<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="order_by">
								<option value="username" selected="selected"><?php echo ForumCore::$lang['Username'] ?></option>
								<option value="email"><?php echo ForumCore::$lang['E-mail'] ?></option>
								<option value="num_posts"><?php echo ForumCore::$lang['Posts'] ?></option>
								<option value="last_post"><?php echo ForumCore::$lang['Last post'] ?></option>
								<option value="registered"><?php echo ForumCore::$lang['Registered'] ?></option>
	<?php ($hook = get_hook('aus_search_form_new_sort_by_option')) ? eval($hook) : null; ?>
							</select></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_sort_order')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box select">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Sort order label'] ?></span></label><br />
							<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="direction">
								<option value="ASC" selected="selected"><?php echo ForumCore::$lang['Ascending'] ?></option>
								<option value="DESC"><?php echo ForumCore::$lang['Descending'] ?></option>
							</select></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_filter_group')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box select">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['User group label'] ?></span></label><br />
							<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="user_group">
								<option value="-1" selected="selected"><?php echo ForumCore::$lang['All groups'] ?></option>
								<option value="<?php echo FORUM_UNVERIFIED ?>"><?php echo ForumCore::$lang['Unverified users'] ?></option>
	<?php

		$query = array(
			'SELECT'	=> 'g.g_id, g.g_title',
			'FROM'		=> 'groups AS g',
			'WHERE'		=> 'g.g_id!='.FORUM_GUEST,
			'ORDER BY'	=> 'g.g_title'
		);

		($hook = get_hook('aus_search_form_qr_get_groups')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		while ($cur_group = $forum_db->fetch_assoc($result))
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.forum_htmlencode($cur_group['g_title']).'</option>'."\n";

		($hook = get_hook('aus_search_form_new_filter_group_option')) ? eval($hook) : null;

	?>
							</select></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_results_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
	<?php ($hook = get_hook('aus_search_form_results_fieldset_end')) ? eval($hook) : null; ?>
				<div class="frm-buttons">
					<span class="submit primary"><input type="submit" name="find_user" value="<?php echo ForumCore::$lang['Submit search'] ?>" /></span>
				</div>
			</form>
		</div>
	<?php

		// Reset counter
		ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

	?>

		<div class="main-subhead">
			<h2 class="hn"><span><?php echo ForumCore::$lang['IP search head'] ?></span></h2>
		</div>
		<div class="main-content main-frm">
			<form class="frm-form" method="get" accept-charset="utf-8" action="">
				<input type="hidden" name="page" value="<?php echo $page ?>">

	<?php ($hook = get_hook('aus_search_form_pre_ip_search_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['IP search legend'] ?></strong></legend>
	<?php ($hook = get_hook('aus_search_form_pre_ip_address')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['IP address label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="show_users" size="18" maxlength="15" required /></span>
						</div>
					</div>
	<?php ($hook = get_hook('aus_search_form_pre_ip_search_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
	<?php ($hook = get_hook('aus_search_form_ip_search_fieldset_end')) ? eval($hook) : null; ?>
				<div class="frm-buttons">
					<span class="submit primary"><input type="submit" value=" <?php echo ForumCore::$lang['Submit search'] ?> " /></span>
				</div>
			</form>
		</div>
	<?php

		($hook = get_hook('aus_end')) ? eval($hook) : null;

		require FORUM_ROOT.'footer.php';
	}
}