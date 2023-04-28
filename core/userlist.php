<?php
/**
 * Provides a list of forum users that can be sorted based on various criteria.
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

($hook = get_hook('ul_start')) ? eval($hook) : null;

if (ForumUser::$forum_user['g_read_board'] == '0')
	message(ForumCore::$lang['No view']);
else if (ForumUser::$forum_user['g_view_users'] == '0')
	message(ForumCore::$lang['No permission']);

// Load the userlist.php language file
//require FORUM_ROOT.'lang/'.ForumUser::$forum_user['language'].'/userlist.php';
ForumCore::add_lang('userlist');

($hook = get_hook('ul_pre_header_load')) ? eval($hook) : null;

define('FORUM_ALLOW_INDEX', 1);

define('FORUM_PAGE', 'userlist');
require FORUM_ROOT.'header.php';

// SHORTCODE [pun_content]
add_shortcode('pun_content', function ()
{
	$forum_db = new DBLayer;

	// Miscellaneous setup
	ForumCore::$forum_page['show_post_count'] = (ForumCore::$forum_config['o_show_post_count'] == '1' || ForumUser::$forum_user['is_admmod']) ? true : false;

	ForumCore::$forum_page['username'] = '';
	if (isset($_GET['username']) && is_string($_GET['username'])) {
		if ($_GET['username'] != '-' && ForumUser::$forum_user['g_search_users'] == '1') {
			ForumCore::$forum_page['username'] = $_GET['username'];
		}
	}

	ForumCore::$forum_page['show_group'] = (!isset($_GET['show_group']) || intval($_GET['show_group']) < -1 && intval($_GET['show_group']) > 2) ? -1 : intval($_GET['show_group']);
	ForumCore::$forum_page['sort_by'] = (!isset($_GET['sort_by']) || $_GET['sort_by'] != 'username' && $_GET['sort_by'] != 'registered' && ($_GET['sort_by'] != 'num_posts' || !ForumCore::$forum_page['show_post_count'])) ? 'username' : $_GET['sort_by'];
	ForumCore::$forum_page['sort_dir'] = (!isset($_GET['sort_dir']) || strtoupper($_GET['sort_dir']) != 'ASC' && strtoupper($_GET['sort_dir']) != 'DESC') ? 'ASC' : strtoupper($_GET['sort_dir']);


	// Create any SQL for the WHERE clause
	$where_sql = array();
	$like_command = 'LIKE';

	if (ForumUser::$forum_user['g_search_users'] == '1' && ForumCore::$forum_page['username'] != '')
		$where_sql[] = 'u.username '.$like_command.' \''.$forum_db->escape(str_replace('*', '%', ForumCore::$forum_page['username'])).'\'';
	if (ForumCore::$forum_page['show_group'] > -1)
		$where_sql[] = 'u.group_id='.ForumCore::$forum_page['show_group'];


	// Fetch user count
	$query = array(
		'SELECT'	=> 'COUNT(u.id)',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.id > 1 AND u.group_id != '.FORUM_UNVERIFIED
	);

	if (!empty($where_sql))
		$query['WHERE'] .= ' AND '.implode(' AND ', $where_sql);

	($hook = get_hook('ul_qr_get_user_count')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	ForumCore::$forum_page['num_users'] = $forum_db->result($result);

	// Determine the user offset (based on $_GET['p'])
	ForumCore::$forum_page['num_pages'] = ceil(ForumCore::$forum_page['num_users'] / 50);
	ForumCore::$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > ForumCore::$forum_page['num_pages']) ? 1 : intval($_GET['p']);
	ForumCore::$forum_page['start_from'] = 50 * (ForumCore::$forum_page['page'] - 1);
	ForumCore::$forum_page['finish_at'] = min(50, (ForumCore::$forum_page['num_users']));

	ForumCore::$forum_page['users_searched'] = ((ForumUser::$forum_user['g_search_users'] == '1' && ForumCore::$forum_page['username'] != '') || ForumCore::$forum_page['show_group'] > -1);

	if (ForumCore::$forum_page['num_users'] > 0)
		ForumCore::$forum_page['items_info'] = generate_items_info(((ForumCore::$forum_page['users_searched']) ? ForumCore::$lang['Users found'] : ForumCore::$lang['Users']), (ForumCore::$forum_page['start_from'] + 1), ForumCore::$forum_page['num_users']);
	else
		ForumCore::$forum_page['items_info'] = ForumCore::$lang['Users'];

	// Generate paging links
	ForumCore::$forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">'.ForumCore::$lang['Pages'].'</span> '.paginate(ForumCore::$forum_page['num_pages'], ForumCore::$forum_page['page'], ForumCore::$forum_url['users_browse'], ForumCore::$lang['Paging separator'], array(ForumCore::$forum_page['show_group'], ForumCore::$forum_page['sort_by'], ForumCore::$forum_page['sort_dir'], (ForumCore::$forum_page['username'] != '') ? urlencode(ForumCore::$forum_page['username']) : '-')).'</p>';

	// Navigation links for header and page numbering for title/meta description
	if (ForumCore::$forum_page['page'] < ForumCore::$forum_page['num_pages'])
	{
		ForumCore::$forum_page['nav']['last'] = '<link rel="last" href="'.forum_sublink(ForumCore::$forum_url['users_browse'], ForumCore::$forum_url['page'], ForumCore::$forum_page['num_pages'], array(ForumCore::$forum_page['show_group'], ForumCore::$forum_page['sort_by'], ForumCore::$forum_page['sort_dir'], (ForumCore::$forum_page['username'] != '') ? urlencode(ForumCore::$forum_page['username']) : '-')).'" title="'.ForumCore::$lang['Page'].' '.ForumCore::$forum_page['num_pages'].'" />';
		ForumCore::$forum_page['nav']['next'] = '<link rel="next" href="'.forum_sublink(ForumCore::$forum_url['users_browse'], ForumCore::$forum_url['page'], (ForumCore::$forum_page['page'] + 1), array(ForumCore::$forum_page['show_group'], ForumCore::$forum_page['sort_by'], ForumCore::$forum_page['sort_dir'], (ForumCore::$forum_page['username'] != '') ? urlencode(ForumCore::$forum_page['username']) : '-')).'" title="'.ForumCore::$lang['Page'].' '.(ForumCore::$forum_page['page'] + 1).'" />';
	}
	if (ForumCore::$forum_page['page'] > 1)
	{
		ForumCore::$forum_page['nav']['prev'] = '<link rel="prev" href="'.forum_sublink(ForumCore::$forum_url['users_browse'], ForumCore::$forum_url['page'], (ForumCore::$forum_page['page'] - 1), array(ForumCore::$forum_page['show_group'], ForumCore::$forum_page['sort_by'], ForumCore::$forum_page['sort_dir'], (ForumCore::$forum_page['username'] != '') ? urlencode(ForumCore::$forum_page['username']) : '-')).'" title="'.ForumCore::$lang['Page'].' '.(ForumCore::$forum_page['page'] - 1).'" />';
		ForumCore::$forum_page['nav']['first'] = '<link rel="first" href="'.forum_link(ForumCore::$forum_url['users_browse'], array(ForumCore::$forum_page['show_group'], ForumCore::$forum_page['sort_by'], ForumCore::$forum_page['sort_dir'], (ForumCore::$forum_page['username'] != '') ? urlencode(ForumCore::$forum_page['username']) : '-')).'" title="'.ForumCore::$lang['Page'].' 1" />';
	}

	// Setup main options
	if (empty($_GET))
		ForumCore::$forum_page['main_head_options'] = array();
	else
		ForumCore::$forum_page['main_head_options'] = array(
			'new_search'	=> '<span'.(empty(ForumCore::$forum_page['main_foot_options']) ? ' class="first-item"' : '').'><a href="'.forum_link(ForumCore::$forum_url['users']).'">'.ForumCore::$lang['Perform new search'].'</a></span>'
		);

	// Setup form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;
	ForumCore::$forum_page['form_action'] = FORUM_BASE_URL.'/userlist.php';

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		ForumCore::$lang['User list']
	);

	// Setup main heading
	if (ForumCore::$forum_page['num_pages'] > 1)
		ForumCore::$forum_page['main_head_pages'] = sprintf(ForumCore::$lang['Page info'], ForumCore::$forum_page['page'], ForumCore::$forum_page['num_pages']);


	($hook = get_hook('ul_main_output_start')) ? eval($hook) : null;

?>
	<div class="main-head">
<?php

		if (!empty(ForumCore::$forum_page['main_head_options']))
			echo "\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_head_options']).'</p>'."\n";

?>
		<h2 class="hn"><span><?php echo ForumCore::$forum_page['items_info'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<form id="afocus" method="get" accept-charset="utf-8" action="">
		<div class="frm-form">
<?php ($hook = get_hook('ul_search_fieldset_start')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['User find legend'] ?></strong></legend>
<?php ($hook = get_hook('ul_pre_username')) ? eval($hook) : null; ?>
<?php if (ForumUser::$forum_user['g_search_users'] == '1'): ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Search for username'] ?></span> <small><?php echo ForumCore::$lang['Username help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="username" value="<?php echo forum_htmlencode(ForumCore::$forum_page['username']) ?>" size="35" maxlength="25" /></span>
					</div>
				</div>
<?php endif; ?>
<?php ($hook = get_hook('ul_pre_group_select')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['User group'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="show_group">
						<option value="-1"<?php if (ForumCore::$forum_page['show_group'] == -1) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['All users'] ?></option>
<?php

	($hook = get_hook('ul_search_new_group_option')) ? eval($hook) : null;

	// Get the list of user groups (excluding the guest group)
	$query = array(
		'SELECT'	=> 'g.g_id, g.g_title',
		'FROM'		=> 'groups AS g',
		'WHERE'		=> 'g.g_id!='.FORUM_GUEST,
		'ORDER BY'	=> 'g.g_id'
	);

	($hook = get_hook('ul_qr_get_groups')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	while ($cur_group = $forum_db->fetch_assoc($result))
	{
		if ($cur_group['g_id'] == ForumCore::$forum_page['show_group'])
			echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.forum_htmlencode($cur_group['g_title']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.forum_htmlencode($cur_group['g_title']).'</option>'."\n";
	}

?>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('ul_pre_sort_by')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Sort users by'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="sort_by">
						<option value="username"<?php if (ForumCore::$forum_page['sort_by'] == 'username') echo ' selected="selected"' ?>><?php echo ForumCore::$lang['Username'] ?></option>
						<option value="registered"<?php if (ForumCore::$forum_page['sort_by'] == 'registered') echo ' selected="selected"' ?>><?php echo ForumCore::$lang['Registered'] ?></option>
<?php if (ForumCore::$forum_page['show_post_count']): ?>
						<option value="num_posts"<?php if (ForumCore::$forum_page['sort_by'] == 'num_posts') echo ' selected="selected"' ?>><?php echo ForumCore::$lang['No of posts'] ?></option>
<?php endif; ($hook = get_hook('ul_new_sort_by_option')) ? eval($hook) : null; ?>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('ul_pre_sort_order_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<legend><span><?php echo ForumCore::$lang['User sort order'] ?></span></legend>
<?php ($hook = get_hook('ul_pre_sort_order')) ? eval($hook) : null; ?>
					<div class="mf-box mf-yesno">
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="sort_dir" value="ASC"<?php if (ForumCore::$forum_page['sort_dir'] == 'ASC') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Ascending'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="sort_dir" value="DESC"<?php if (ForumCore::$forum_page['sort_dir'] == 'DESC') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Descending'] ?></label>
						</div>
					</div>
<?php ($hook = get_hook('ul_pre_sort_order_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('ul_pre_search_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('ul_search_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="search" value="<?php echo ForumCore::$lang['Submit user search'] ?>" /></span>
			</div>
		</div>
		</form>
<?php

	// Grab the users
	$query = array(
		'SELECT'	=> 'u.id, u.username, u.title, u.num_posts, u.registered, g.g_id, g.g_user_title',
		'FROM'		=> 'users AS u',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'groups AS g',
				'ON'			=> 'g.g_id=u.group_id'
			)
		),
		'WHERE'		=> 'u.id > 1 AND u.group_id != '.FORUM_UNVERIFIED,
		'ORDER BY'	=> ForumCore::$forum_page['sort_by'].' '.ForumCore::$forum_page['sort_dir'].', u.id ASC',
		'LIMIT'		=> ForumCore::$forum_page['start_from'].', 50'
	);

	if (!empty($where_sql))
		$query['WHERE'] .= ' AND '.implode(' AND ', $where_sql);

	($hook = get_hook('ul_qr_get_users')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$founded_user_datas = array();
	while ($user_data = $forum_db->fetch_assoc($result))
	{
		$founded_user_datas[] = $user_data;
	}

	ForumCore::$forum_page['item_count'] = 0;

	if (!empty($founded_user_datas))
	{
		($hook = get_hook('ul_results_pre_header')) ? eval($hook) : null;

		ForumCore::$forum_page['table_header'] = array();
		ForumCore::$forum_page['table_header']['username'] = '<th class="tc'.count(ForumCore::$forum_page['table_header']).'" scope="col">'.ForumCore::$lang['Username'].'</th>';
		ForumCore::$forum_page['table_header']['title'] = '<th class="tc'.count(ForumCore::$forum_page['table_header']).'" scope="col">'.ForumCore::$lang['Title'].'</th>';

		if (ForumCore::$forum_page['show_post_count'])
			ForumCore::$forum_page['table_header']['posts'] = '<th class="tc'.count(ForumCore::$forum_page['table_header']).'" scope="col">'.ForumCore::$lang['Posts'].'</th>';

		ForumCore::$forum_page['table_header']['registered'] = '<th class="tc'.count(ForumCore::$forum_page['table_header']).'" scope="col">'.ForumCore::$lang['Registered'].'</th>';

		($hook = get_hook('ul_results_pre_header_output')) ? eval($hook) : null;

?>
		<div class="ct-group">
			<table>
				<caption><?php echo ForumCore::$lang['Table summary'] ?></caption>
				<thead>
					<tr>
						<?php echo implode("\n\t\t\t\t\t\t", ForumCore::$forum_page['table_header'])."\n" ?>
					</tr>
				</thead>
				<tbody>
<?php

		foreach ($founded_user_datas as $user_data)
		{
			($hook = get_hook('ul_results_row_pre_data')) ? eval($hook) : null;

			ForumCore::$forum_page['table_row'] = array();
			ForumCore::$forum_page['table_row']['username'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'"><a href="'.forum_link(ForumCore::$forum_url['user'], $user_data['id']).'">'.forum_htmlencode($user_data['username']).'</a></td>';
			ForumCore::$forum_page['table_row']['title'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'">'.get_title($user_data).'</td>';

			if (ForumCore::$forum_page['show_post_count'])
				ForumCore::$forum_page['table_row']['posts'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'">'.forum_number_format($user_data['num_posts']).'</td>';

			ForumCore::$forum_page['table_row']['registered'] = '<td class="tc'.count(ForumCore::$forum_page['table_row']).'">'.format_time($user_data['registered'], 1).'</td>';

			++ForumCore::$forum_page['item_count'];

			($hook = get_hook('ul_results_row_pre_data_output')) ? eval($hook) : null;

?>
				<tr class="<?php echo (ForumCore::$forum_page['item_count'] % 2 != 0) ? 'odd' : 'even' ?><?php if (ForumCore::$forum_page['item_count'] == 1) echo ' row1'; ?>">
					<?php echo implode("\n\t\t\t\t\t\t", ForumCore::$forum_page['table_row'])."\n" ?>
				</tr>
<?php

		}

?>
				</tbody>
			</table>
		</div>
<?php

	}
	else
	{

?>
		<div class="ct-box">
			<p><strong><?php echo ForumCore::$lang['No users found'] ?></strong></p>
		</div>
<?php

	}

?>
	</div>
	<div class="main-foot">
<?php

		if (!empty(ForumCore::$forum_page['main_foot_options']))
			echo "\n\t\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_foot_options']).'</p>';

?>
		<h2 class="hn"><span><?php echo ForumCore::$forum_page['items_info'] ?></span></h2>
	</div>
<?php

	($hook = get_hook('ul_end')) ? eval($hook) : null;

});

require FORUM_ROOT.'footer.php';
