<?php
/**
 * Ban management page.
 *
 * Allows administrators and moderators to create, modify, and delete bans.
 *
 * @copyright (C) 2023 HiveBB, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package HiveBB
 */

use \HiveBB\ForumCore;
use \HiveBB\DBLayer;
use \HiveBB\ForumUser;

defined( 'ABSPATH' ) OR die();

($hook = get_hook('aba_start')) ? eval($hook) : null;

if (!is_admin())
	message(ForumCore::$lang['No permission']);

if (ForumUser::$forum_user['g_id'] != FORUM_ADMIN && (ForumUser::$forum_user['g_moderator'] != '1' || ForumUser::$forum_user['g_mod_ban_users'] == '0'))
	message(ForumCore::$lang['No permission']);

// Load the admin.php language file
ForumCore::add_lang('admin_common');
ForumCore::add_lang('admin_bans');

$forum_db = new DBLayer;

// Add/edit a ban (stage 1)
if (isset($_REQUEST['add_ban']) || isset($_GET['edit_ban']))
{
	if (isset($_GET['add_ban']) || isset($_POST['add_ban']))
	{
		// If the id of the user to ban was provided through GET (a link from profile.php)
		if (isset($_GET['add_ban']))
		{
			$add_ban = intval($_GET['add_ban']);
			if ($add_ban < 2)
				message(ForumCore::$lang['Bad request']);

			$user_id = $add_ban;

			($hook = get_hook('aba_add_ban_selected')) ? eval($hook) : null;

			$query = array(
				'SELECT'	=> 'u.group_id, u.username, u.email, u.registration_ip',
				'FROM'		=> 'users AS u',
				'WHERE'		=> 'u.id='.$user_id
			);
			($hook = get_hook('aba_add_ban_qr_get_user_by_id')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			$banned_user_info = $forum_db->fetch_row($result);
			if (!$banned_user_info)
			{
				message(ForumCore::$lang['No user id message']);
			}

			list($group_id, $ban_user, $ban_email, $ban_ip) = $banned_user_info;
		}
		else	// Otherwise the username is in POST
		{
			$ban_user = forum_trim($_POST['new_ban_user']);

			($hook = get_hook('aba_add_ban_form_submitted')) ? eval($hook) : null;

			if ($ban_user != '')
			{
				$query = array(
					'SELECT'	=> 'u.id, u.group_id, u.username, u.email, u.registration_ip',
					'FROM'		=> 'users AS u',
					'WHERE'		=> 'u.username=\''.$forum_db->escape($ban_user).'\' AND u.id>1'
				);
				($hook = get_hook('aba_add_ban_qr_get_user_by_username')) ? eval($hook) : null;
				$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
				$banned_user_info = $forum_db->fetch_row($result);
				if (!$banned_user_info)
				{
					message(ForumCore::$lang['No user username message']);
				}

				list($user_id, $group_id, $ban_user, $ban_email, $ban_ip) = $banned_user_info;
			}
		}

		// Make sure we're not banning an admin
		if (isset($group_id) && $group_id == FORUM_ADMIN)
			message(ForumCore::$lang['User is admin message']);

		// If we have a $user_id, we can try to find the last known IP of that user
		if (isset($user_id))
		{
			$query = array(
				'SELECT'	=> 'p.poster_ip',
				'FROM'		=> 'posts AS p',
				'WHERE'		=> 'p.poster_id='.$user_id,
				'ORDER BY'	=> 'p.posted DESC',
				'LIMIT'		=> '1'
			);

			($hook = get_hook('aba_add_ban_qr_get_last_known_ip')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			$ban_ip_from_db = $forum_db->result($result);

			if ($ban_ip_from_db)
			{
				$ban_ip = $ban_ip_from_db;
			}
		}

		$mode = 'add';
	}
	else	// We are editing a ban
	{
		$ban_id = intval($_GET['edit_ban']);
		if ($ban_id < 1)
			message(ForumCore::$lang['Bad request']);

		($hook = get_hook('aba_edit_ban_selected')) ? eval($hook) : null;

		$query = array(
			'SELECT'	=> 'b.username, b.ip, b.email, b.message, b.expire',
			'FROM'		=> 'bans AS b',
			'WHERE'		=> 'b.id='.$ban_id
		);

		($hook = get_hook('aba_edit_ban_qr_get_ban_data')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$banned_user_info = $forum_db->fetch_row($result);

		if (!$banned_user_info)
		{
			message(ForumCore::$lang['Bad request']);
		}

		list($ban_user, $ban_ip, $ban_email, $ban_message, $ban_expire) = $banned_user_info;

		// We just use GMT for expire dates, as its a date rather than a day I don't think its worth worrying about
		$ban_expire = ($ban_expire != '') ? gmdate('Y-m-d', $ban_expire) : '';

		$mode = 'edit';
	}

	// Setup the form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index']))
	);
	if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN)
		ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Users'], forum_link(ForumCore::$forum_url['admin_users']));
	ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Bans'], forum_link(ForumCore::$forum_url['admin_bans']));
	ForumCore::$forum_page['crumbs'][] = ForumCore::$lang['Ban advanced'];

	($hook = get_hook('aba_add_edit_ban_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'users');
	define('FORUM_PAGE', 'admin-bans');
	require FORUM_ROOT.'header.php';

	($hook = get_hook('aba_add_edit_ban_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo ForumCore::$lang['Ban advanced heading'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div class="ct-box warn-box">
			<p class="warn"><?php echo ForumCore::$lang['Ban IP warning'] ?></p>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_bans') ); ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_bans') ); ?>" />
				<input type="hidden" name="mode" value="<?php echo $mode ?>" />
<?php if ($mode == 'edit'): ?>
				<input type="hidden" name="ban_id" value="<?php echo $ban_id ?>" />
<?php endif; ?>
			</div>
<?php ($hook = get_hook('aba_add_edit_ban_pre_criteria_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo ForumCore::$lang['Ban criteria legend'] ?></span></legend>
<?php ($hook = get_hook('aba_add_edit_ban_pre_username')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Username to ban label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="ban_user" size="40" maxlength="25" value="<?php if (isset($ban_user)) echo forum_htmlencode($ban_user); ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aba_add_edit_ban_pre_email')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['E-mail/domain to ban label'] ?></span> <small><?php echo ForumCore::$lang['E-mail/domain help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="ban_email" size="40" maxlength="80" value="<?php if (isset($ban_email)) echo forum_htmlencode(strtolower($ban_email)); ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aba_add_edit_ban_pre_ip')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['IP-addresses to ban label'] ?></span> <small><?php echo ForumCore::$lang['IP-addresses help']; if ($ban_user != '' && isset($user_id)) echo ' '.ForumCore::$lang['IP-addresses help stats'].'<a href="'.forum_link(ForumCore::$forum_url['admin_users']).'?ip_stats='.$user_id.'">'.ForumCore::$lang['IP-addresses help link'].'</a>' ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="ban_ip" size="40" maxlength="255" value="<?php if (isset($ban_ip)) echo $ban_ip; ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aba_add_edit_ban_pre_message')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Ban message label'] ?></span> <small><?php echo ForumCore::$lang['Ban message help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="ban_message" size="40" maxlength="255" value="<?php if (isset($ban_message)) echo forum_htmlencode($ban_message); ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aba_add_edit_ban_pre_expire')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Expire date label'] ?></span> <small><?php echo ForumCore::$lang['Expire date help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="ban_expire" size="20" maxlength="10" value="<?php if (isset($ban_expire)) echo $ban_expire; ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aba_add_edit_ban_criteria_pre_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('aba_add_edit_ban_criteria_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="add_edit_ban" value=" <?php echo ForumCore::$lang['Save ban'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	($hook = get_hook('aba_add_edit_ban_end')) ? eval($hook) : null;

	require FORUM_ROOT.'footer.php';
}

// Remove a ban
else if (isset($_GET['del_ban']) && is_admin())
{
	$ban_id = intval($_GET['del_ban']);
	if ($ban_id < 1)
		message(ForumCore::$lang['Bad request']);

	define('FORUM_PAGE_SECTION', 'users');
	define('FORUM_PAGE', 'admin-bans');
	require FORUM_ROOT.'header.php';

	($hook = get_hook('aba_main_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo ForumCore::$lang['New ban heading'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div class="ct-box warn-box">
			<p><?php echo ForumCore::$lang['Advanced ban info'] ?></p>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_bans') ); ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_bans') ); ?>" />
				<input type="hidden" name="ban_id" value="<?php echo $ban_id; ?>" />
			</div>
			<div class="frm-buttons">
				<span class="submit primary caution"><input type="submit" name="del_ban_comply" value="<?php echo ForumCore::$lang['Remove ban'] ?>" /></span>
				<span class="cancel"><input type="submit" name="del_ban_cancel" value="<?php echo ForumCore::$lang['Cancel'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

}

else
{
	// Setup the form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;
	ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['admin_bans']).'&amp;action=more';

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index']))
	);
	if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN)
		ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Users'], forum_link(ForumCore::$forum_url['admin_users']));
	ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Bans'], forum_link(ForumCore::$forum_url['admin_bans']));


	// Fetch user count
	$query = array(
		'SELECT'	=>	'COUNT(id)',
		'FROM'		=>	'bans'
	);

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	ForumCore::$forum_page['num_bans'] = $forum_db->result($result);
	ForumCore::$forum_page['num_pages'] = ceil(ForumCore::$forum_page['num_bans'] / ForumUser::$forum_user['disp_topics']);
	ForumCore::$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > ForumCore::$forum_page['num_pages']) ? 1 : intval($_GET['p']);
	ForumCore::$forum_page['start_from'] = ForumUser::$forum_user['disp_topics'] * (ForumCore::$forum_page['page'] - 1);
	ForumCore::$forum_page['finish_at'] = min((ForumUser::$forum_user['disp_topics']), (ForumCore::$forum_page['num_bans']));

	// Generate paging
	ForumCore::$forum_page['page_post']['paging']='<p class="paging"><span class="pages">'.ForumCore::$lang['Pages'].'</span> '.paginate(ForumCore::$forum_page['num_pages'], ForumCore::$forum_page['page'], ForumCore::$forum_url['admin_bans'], ForumCore::$lang['Paging separator'], null, true).'</p>';

	// Navigation links for header and page numbering for title/meta description
	if (ForumCore::$forum_page['page'] < ForumCore::$forum_page['num_pages'])
	{
		ForumCore::$forum_page['nav']['last'] = '<link rel="last" href="'.forum_sublink(ForumCore::$forum_url['admin_bans'], ForumCore::$forum_url['page'], ForumCore::$forum_page['num_pages']).'" title="'.ForumCore::$lang['Page'].' '.ForumCore::$forum_page['num_pages'].'" />';
		ForumCore::$forum_page['nav']['next'] = '<link rel="next" href="'.forum_sublink(ForumCore::$forum_url['admin_bans'], ForumCore::$forum_url['page'], (ForumCore::$forum_page['page'] + 1)).'" title="'.ForumCore::$lang['Page'].' '.(ForumCore::$forum_page['page'] + 1).'" />';
	}
	if (ForumCore::$forum_page['page'] > 1)
	{
		ForumCore::$forum_page['nav']['prev'] = '<link rel="prev" href="'.forum_sublink(ForumCore::$forum_url['admin_bans'], ForumCore::$forum_url['page'], (ForumCore::$forum_page['page'] - 1)).'" title="'.ForumCore::$lang['Page'].' '.(ForumCore::$forum_page['page'] - 1).'" />';
		ForumCore::$forum_page['nav']['first'] = '<link rel="first" href="'.forum_link(ForumCore::$forum_url['admin_bans']).'" title="'.ForumCore::$lang['Page'].' 1" />';
	}

	($hook = get_hook('aba_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'users');
	define('FORUM_PAGE', 'admin-bans');
	require FORUM_ROOT.'header.php';

	($hook = get_hook('aba_main_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo ForumCore::$lang['New ban heading'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div class="ct-box warn-box">
			<p><?php echo ForumCore::$lang['Advanced ban info'] ?></p>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(); ?>" />
			</div>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['New ban legend'] ?></strong></legend>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Username to ban label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="new_ban_user" size="25" maxlength="25" /></span>
					</div>
				</div>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="add_ban" value=" <?php echo ForumCore::$lang['Add ban'] ?> " /></span>
			</div>
		</form>
	</div>
<?php

	// Reset counters
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

	?>
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo ForumCore::$lang['Existing bans heading'] ?></span></h2>
		</div>
		<div class="main-content main-frm">
	<?php

	if (ForumCore::$forum_page['num_bans'] > 0)
	{

?>
		<div class="ct-group">
<?php

		// Grab the bans
		$query = array(
			'SELECT'	=> 'b.*, u.username AS ban_creator_username',
			'FROM'		=> 'bans AS b',
			'JOINS'		=> array(
				array(
					'LEFT JOIN'		=> 'users AS u',
					'ON'			=> 'u.id=b.ban_creator'
				)
			),
			'ORDER BY'	=> 'b.id',
			'LIMIT'		=> ForumCore::$forum_page['start_from'].', '.ForumCore::$forum_page['finish_at']
		);

		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		ForumCore::$forum_page['item_num'] = 0;
		while ($cur_ban = $forum_db->fetch_assoc($result))
		{
			ForumCore::$forum_page['ban_info'] = array();
			ForumCore::$forum_page['ban_creator'] = ($cur_ban['ban_creator_username'] != '') ? '<a href="'.forum_link(ForumCore::$forum_url['user'], $cur_ban['ban_creator']).'">'.forum_htmlencode($cur_ban['ban_creator_username']).'</a>' : ForumCore::$lang['Unknown'];

			if ($cur_ban['username'] != '')
				ForumCore::$forum_page['ban_info']['username'] = '<li><span>'.ForumCore::$lang['Username'].'</span> <strong>'.forum_htmlencode($cur_ban['username']).'</strong></li>';

			if ($cur_ban['email'] != '')
				ForumCore::$forum_page['ban_info']['email'] = '<li><span>'.ForumCore::$lang['E-mail'].'</span> <strong>'.forum_htmlencode($cur_ban['email']).'</strong></li>';

			if ($cur_ban['ip'] != '')
				ForumCore::$forum_page['ban_info']['ip'] = '<li><span>'.ForumCore::$lang['IP-ranges'].'</span> <strong>'.$cur_ban['ip'].'</strong></li>';

			if ($cur_ban['expire'] != '')
				ForumCore::$forum_page['ban_info']['expire'] = '<li><span>'.ForumCore::$lang['Expires'].'</span> <strong>'.format_time($cur_ban['expire'], 1).'</strong></li>';

			if ($cur_ban['message'] != '')
				ForumCore::$forum_page['ban_info']['message'] ='<li><span>'.ForumCore::$lang['Message'].'</span> <strong>'.forum_htmlencode($cur_ban['message']).'</strong></li>';

			($hook = get_hook('aba_view_ban_pre_display')) ? eval($hook) : null;

?>
			<div class="ct-set set<?php echo ++ForumCore::$forum_page['item_num'] ?>">
				<div class="ct-box warn-box">
					<div class="ct-legend">
						<h3><span><?php printf(ForumCore::$lang['Current ban head'], ForumCore::$forum_page['ban_creator']) ?></span></h3>
						<p><?php printf(ForumCore::$lang['Edit or remove'], '<a href="'.pun_admin_link(ForumCore::$forum_url['admin_bans']).'&amp;edit_ban='.$cur_ban['id'].'">'.ForumCore::$lang['Edit ban'].'</a>', '<a href="'.pun_admin_link(ForumCore::$forum_url['admin_bans']).'&del_ban='.$cur_ban['id'].'">'.ForumCore::$lang['Remove ban'].'</a>') ?></p>
					</div>
<?php if (!empty(ForumCore::$forum_page['ban_info'])): ?>
				<ul>
					<?php echo implode("\n", ForumCore::$forum_page['ban_info'])."\n" ?>
					</ul>
<?php endif; ?>
				</div>
			</div>
<?php

	}

?>
		</div>
<?php

	}
	else
	{

?>
		<div class="ct-box warn-box">
			<p><?php echo ForumCore::$lang['No bans'] ?></p>
		</div>
<?php

	}

?>
	</div>
<?php

	($hook = get_hook('aba_end')) ? eval($hook) : null;

	require FORUM_ROOT.'footer.php';
}