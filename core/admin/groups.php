<?php
/**
 * Group management page.
 *
 * Allows administrators to control group permissions.
 *
 * @copyright (C) 2023 HiveBB, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package HiveBB
 */

use \HiveBB\ForumCore;
use \HiveBB\DBLayer;

defined( 'ABSPATH' ) OR die();

($hook = get_hook('agr_start')) ? eval($hook) : null;

if (!is_admin())
	message(ForumCore::$lang['No permission']);

$forum_db = new DBLayer;

// Load the admin.php language file
ForumCore::add_lang('admin_common');
ForumCore::add_lang('admin_groups');

// Add/edit a group (stage 1)
if (isset($_POST['add_group']) || isset($_GET['edit_group']))
{
	if (isset($_POST['add_group']))
	{
		($hook = get_hook('agr_add_group_form_submitted')) ? eval($hook) : null;

		$base_group = intval($_POST['base_group']);

		$query = array(
			'SELECT'	=> 'g.*',
			'FROM'		=> 'groups AS g',
			'WHERE'		=> 'g.g_id='.$base_group
		);

		($hook = get_hook('agr_add_group_qr_get_base_group')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$group = $forum_db->fetch_assoc($result);

		$mode = 'add';
	}
	else	// We are editing a group
	{
		($hook = get_hook('agr_edit_group_form_submitted')) ? eval($hook) : null;

		$group_id = intval($_GET['edit_group']);
		if ($group_id < 1)
			message(ForumCore::$lang['Bad request']);

		$query = array(
			'SELECT'	=> 'g.*',
			'FROM'		=> 'groups AS g',
			'WHERE'		=> 'g.g_id='.$group_id
		);

		($hook = get_hook('agr_edit_group_qr_get_group')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$group = $forum_db->fetch_assoc($result);

		if (!$group)
			message(ForumCore::$lang['Bad request']);

		$mode = 'edit';
	}

	// Setup the form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index'])),
		array(ForumCore::$lang['Users'], forum_link(ForumCore::$forum_url['admin_users'])),
		array(ForumCore::$lang['Groups'], forum_link(ForumCore::$forum_url['admin_groups'])),
		$mode == 'edit' ? ForumCore::$lang['Edit group heading'] : ForumCore::$lang['Add group heading']
	);

	($hook = get_hook('agr_add_edit_group_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'users');
	define('FORUM_PAGE', 'admin-groups');
	require FORUM_ROOT.'header.php';

	($hook = get_hook('agr_add_edit_group_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo ForumCore::$lang['Group settings heading'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div id="req-msg" class="req-warn ct-box error-box">
			<p class="important"><?php echo ForumCore::$lang['Required warn'] ?></p>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_groups') ); ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_groups') ); ?>" />
				<input type="hidden" name="mode" value="<?php echo $mode ?>" />
<?php if ($mode == 'edit'): ?>				<input type="hidden" name="group_id" value="<?php echo $group_id ?>" />
<?php endif; if ($mode == 'add'): ?>				<input type="hidden" name="base_group" value="<?php echo $base_group ?>" />
<?php endif; ?>			</div>
			<div class="content-head">
				<h3 class="hn"><span><?php echo ForumCore::$lang['Group title head'] ?></span></h3>
			</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_basic_details_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo ForumCore::$lang['Group title legend'] ?></span></legend>
<?php ($hook = get_hook('agr_add_edit_group_pre_group_title')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Group title label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="req_title" size="25" maxlength="50" value="<?php if ($mode == 'edit') echo forum_htmlencode($group['g_title']); ?>" required /></span>
					</div>
				</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_user_title')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['User title label'] ?></span> <small><?php echo ForumCore::$lang['User title help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="user_title" size="25" maxlength="50" value="<?php echo forum_htmlencode($group['g_user_title']) ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_basic_details_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	($hook = get_hook('agr_add_edit_group_basic_details_fieldset_end')) ? eval($hook) : null;

	// The rest of the form is for non-admin groups only
	if ($group['g_id'] != FORUM_ADMIN)
	{
		// Reset fieldset counter
		ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
			<div class="content-head">
				<h3 class="hn"><span><?php echo ForumCore::$lang['Group perms head'] ?></span></h3>
			</div>
<?php if ($mode == 'edit' && ForumCore::$forum_config['o_default_user_group'] == $group['g_id']): ?>
				<div class="ct-box warn-box">
					<p class="warn"><?php echo ForumCore::$lang['Moderator default group'] ?></p>
				</div>
<?php endif; ($hook = get_hook('agr_add_edit_group_pre_permissions_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Permissions'] ?></strong></legend>
<?php ($hook = get_hook('agr_add_edit_group_pre_mod_permissions_fieldset')) ? eval($hook) : null; if ($group['g_id'] != FORUM_GUEST): if ($mode != 'edit' || ForumCore::$forum_config['o_default_user_group'] != $group['g_id']): ?>
					<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<legend><span><?php echo ForumCore::$lang['Mod permissions'] ?></span></legend>
						<div class="mf-box">
<?php ($hook = get_hook('agr_add_edit_group_pre_allow_moderate_checkbox')) ? eval($hook) : null; ?>
							<div class="mf-item">
								<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="moderator" value="1"<?php if ($group['g_moderator'] == '1') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow moderate label'] ?> <?php echo ForumCore::$lang['Allow moderate help'] ?></label>
							</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_allow_mod_edit_profiles_checkbox')) ? eval($hook) : null; ?>
							<div class="mf-item">
								<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="mod_edit_users" value="1"<?php if ($group['g_mod_edit_users'] == '1') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow mod edit profiles label'] ?></label>
							</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_allow_mod_edit_userbane_checkbox')) ? eval($hook) : null; ?>
							<div class="mf-item">
								<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="mod_rename_users" value="1"<?php if ($group['g_mod_rename_users'] == '1') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow mod edit username label'] ?></label>
							</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_allow_mod_change_pass_checkbox')) ? eval($hook) : null; ?>
							<div class="mf-item">
								<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="mod_change_passwords" value="1"<?php if ($group['g_mod_change_passwords'] == '1') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow mod change pass label'] ?></label>
							</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_allow_mod_ban_users_checkbox')) ? eval($hook) : null; ?>
							<div class="mf-item">
								<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="mod_ban_users" value="1"<?php if ($group['g_mod_ban_users'] == '1') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow mod bans label'] ?></label>
							</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_mod_permissions_fieldset_end')) ? eval($hook) : null; ?>
						</div>
					</fieldset>
<?php ($hook = get_hook('agr_add_edit_group_mod_permissions_fieldset_end')) ? eval($hook) : null; endif; endif; ?>
					<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<legend><span><?php echo ForumCore::$lang['User permissions'] ?></span></legend>
						<div class="mf-box">
<?php ($hook = get_hook('agr_add_edit_group_pre_allow_read_board_checkbox')) ? eval($hook) : null; ?>
							<div class="mf-item">
								<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="read_board" value="1"<?php if ($group['g_read_board'] == '1') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow read board label'] ?> <?php echo ForumCore::$lang['Allow read board help'] ?></label>
							</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_allow_view_users_checkbox')) ? eval($hook) : null; ?>
							<div class="mf-item">
								<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="view_users" value="1"<?php if ($group['g_view_users'] == '1') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow view users label'] ?></label>
							</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_allow_post_replies_checkbox')) ? eval($hook) : null; ?>
							<div class="mf-item">
								<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="post_replies" value="1"<?php if ($group['g_post_replies'] == '1') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow post replies label'] ?></label>
							</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_allow_post_topics_checkbox')) ? eval($hook) : null; ?>
							<div class="mf-item">
								<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="post_topics" value="1"<?php if ($group['g_post_topics'] == '1') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow post topics label'] ?></label>
							</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_allow_edit_posts_checkbox')) ? eval($hook) : null; if ($group['g_id'] != FORUM_GUEST): ?>
							<div class="mf-item">
								<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="edit_posts" value="1"<?php if ($group['g_edit_posts'] == '1') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow edit posts label'] ?></label>
							</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_allow_delete_posts_checkbox')) ? eval($hook) : null; ?>
							<div class="mf-item">
								<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="delete_posts" value="1"<?php if ($group['g_delete_posts'] == '1') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow delete posts label'] ?></label>
							</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_allow_delete_topics_checkbox')) ? eval($hook) : null; ?>
							<div class="mf-item">
								<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="delete_topics" value="1"<?php if ($group['g_delete_topics'] == '1') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow delete topics label'] ?></label>
							</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_allow_set_user_title_checkbox')) ? eval($hook) : null; ?>
							<div class="mf-item">
								<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="set_title" value="1"<?php if ($group['g_set_title'] == '1') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow set user title label'] ?></label>
							</div>
<?php endif; ($hook = get_hook('agr_add_edit_group_pre_allow_search_checkbox')) ? eval($hook) : null; ?>
							<div class="mf-item">
								<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="search" value="1"<?php if ($group['g_search'] == '1') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow use search label'] ?></label>
							</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_allow_search_users_checkbox')) ? eval($hook) : null; ?>
							<div class="mf-item">
								<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="search_users" value="1"<?php if ($group['g_search_users'] == '1') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow search users label'] ?></label>
							</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_allow_send_email_checkbox')) ? eval($hook) : null; if ($group['g_id'] != FORUM_GUEST): ?>
							<div class="mf-item">
								<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="send_email" value="1"<?php if ($group['g_send_email'] == '1') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow send email label'] ?></label>
							</div>
<?php endif; ($hook = get_hook('agr_add_edit_group_pre_user_permissions_fieldset_end')) ? eval($hook) : null; ?>
						</div>
					</fieldset>
<?php ($hook = get_hook('agr_add_edit_group_user_permissions_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php

		// Reset counter
		ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
			<div class="content-head">
				<h3 class="hn"><span><?php echo ForumCore::$lang['Group flood head'] ?></span></h3>
			</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_flood_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo ForumCore::$lang['Restrictions'] ?></span></legend>
<?php ($hook = get_hook('agr_add_edit_group_pre_post_interval')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Flood interval label'] ?></span> <small><?php echo ForumCore::$lang['Flood interval help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="post_flood" size="5" maxlength="4" value="<?php echo $group['g_post_flood'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('agr_add_edit_group_pre_search_interval')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Search interval label'] ?></span> <small><?php echo ForumCore::$lang['Search interval help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="search_flood" size="5" maxlength="4" value="<?php echo $group['g_search_flood'] ?>" /></span>
					</div>
				</div>
<?php

		($hook = get_hook('agr_add_edit_group_pre_email_interval')) ? eval($hook) : null;

		// The rest of the form is for non-guest groups only
		if ($group['g_id'] != FORUM_GUEST)
		{

?>
				<?php ($hook = get_hook('agr_add_edit_group_pre_email_interval')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Email flood interval label'] ?></span> <small><?php echo ForumCore::$lang['Email flood interval help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="email_flood" size="5" maxlength="4" value="<?php echo $group['g_email_flood'] ?>" /></span>
					</div>
				</div>
<?php

		}

		($hook = get_hook('agr_add_edit_group_pre_flood_fieldset_end')) ? eval($hook) : null;

?>
			</fieldset>
<?php

		($hook = get_hook('agr_add_edit_group_flood_fieldset_end')) ? eval($hook) : null;
	}

?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="add_edit_group" value=" <?php echo ForumCore::$lang['Update group'] ?> " /></span>
			</div>
		</form>
	</div>
<?php

	($hook = get_hook('agr_add_edit_group_end')) ? eval($hook) : null;

	require FORUM_ROOT.'footer.php';
}

// Remove a group
else if (isset($_GET['del_group']))
{
	$group_id = intval($_GET['del_group']);
	if ($group_id <= FORUM_GUEST)
		message(ForumCore::$lang['Bad request']);

	// User pressed the cancel button
	if (isset($_POST['del_group_cancel']))
		redirect(forum_link(ForumCore::$forum_url['admin_groups']), ForumCore::$lang['Cancel redirect']);

	// Make sure we don't remove the default group
	if ($group_id == ForumCore::$forum_config['o_default_user_group'])
		message(ForumCore::$lang['Cannot remove default group']);

	($hook = get_hook('agr_del_group_selected')) ? eval($hook) : null;

	// Check if this group has any members
	$query = array(
		'SELECT'	=> 'g.g_title AS title, COUNT(u.id) AS num_members',
		'FROM'		=> 'groups AS g',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'users AS u',
				'ON'			=> 'g.g_id=u.group_id'
			)
		),
		'WHERE'		=> 'g.g_id='.$group_id,
		'GROUP BY'	=> 'g.g_id, g.g_title'
	);
	($hook = get_hook('agr_del_group_qr_get_group_member_count')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$group_info = $forum_db->fetch_assoc($result);

	// Setup the form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index'])),
		array(ForumCore::$lang['Users'], forum_link(ForumCore::$forum_url['admin_users'])),
		array(ForumCore::$lang['Groups'], forum_link(ForumCore::$forum_url['admin_groups'])),
		ForumCore::$lang['Remove group']
	);

	($hook = get_hook('agr_del_group_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'users');
	define('FORUM_PAGE', 'admin-groups');
	require FORUM_ROOT.'header.php';

	($hook = get_hook('agr_del_group_output_start')) ? eval($hook) : null;
?>
	<div class="main-subhead">

		<h2 class="hn"><span><?php
	if (!empty($group_info))
		printf(ForumCore::$lang['Remove group head'], forum_htmlencode($group_info['title']), $group_info['num_members']);
	else
		echo ForumCore::$lang['No users'];
		?></span></h2>

	</div>
	<div class="main-content main-frm">
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_groups') ); ?>">
			<div class="hidden">
				<input type="hidden" name="group_id" value="<?php echo $group_id ?>" />
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_groups') ); ?>" />
			</div>
<?php ($hook = get_hook('agr_del_group_pre_del_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group set<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo ForumCore::$lang['Remove group legend'] ?></span></legend>
<?php ($hook = get_hook('agr_del_group_pre_move_to_group')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Move users to'] ?></span> <small><?php echo ForumCore::$lang['Remove group help'] ?></small></label><br />
						<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="move_to_group">
<?php

	$query = array(
		'SELECT'	=> 'g.g_id, g.g_title',
		'FROM'		=> 'groups AS g',
		'WHERE'		=> 'g.g_id!='.FORUM_GUEST.' AND g.g_id!='.$group_id,
		'ORDER BY'	=> 'g.g_title'
	);
	($hook = get_hook('agr_del_group_qr_get_groups')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	while ($cur_group = $forum_db->fetch_assoc($result))
	{
		if ($cur_group['g_id'] == ForumCore::$forum_config['o_default_user_group'])	// Pre-select the default Members group
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.forum_htmlencode($cur_group['g_title']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.forum_htmlencode($cur_group['g_title']).'</option>'."\n";
	}

?>

						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('agr_del_group_pre_del_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('agr_del_group_del_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="del_group" value="<?php echo ForumCore::$lang['Remove group'] ?>" /></span>
				<span class="cancel"><input type="submit" name="del_group_cancel" value="<?php echo ForumCore::$lang['Cancel'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	($hook = get_hook('agr_del_group_end')) ? eval($hook) : null;

	require FORUM_ROOT.'footer.php';
}
else
{
	// Setup the form
	ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = ForumCore::$forum_page['group_count'] = 0;

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index'])),
		array(ForumCore::$lang['Users'], forum_link(ForumCore::$forum_url['admin_users'])),
		array(ForumCore::$lang['Groups'], forum_link(ForumCore::$forum_url['admin_groups']))
	);

	($hook = get_hook('agr_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'users');
	define('FORUM_PAGE', 'admin-groups');
	require FORUM_ROOT.'header.php';

	($hook = get_hook('agr_main_output_start')) ? eval($hook) : null;

	?>
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo ForumCore::$lang['Add group heading'] ?></span></h2>
		</div>
		<div class="main-content main-frm">
			<form class="frm-form" method="post" accept-charset="utf-8" action="">
				<div class="hidden">
					<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(); ?>" />
				</div>
	<?php ($hook = get_hook('agr_pre_add_group_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><span><?php echo ForumCore::$lang['Add group legend'] ?></span></legend>
	<?php ($hook = get_hook('agr_pre_add_base_group')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box select">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Base new group label'] ?></span></label><br />
							<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="base_group">
	<?php

	$query = array(
		'SELECT'	=> 'g.g_id, g.g_title',
		'FROM'		=> 'groups AS g',
		'WHERE'		=> 'g_id>'.FORUM_GUEST,
		'ORDER BY'	=> 'g.g_title'
	);

	($hook = get_hook('agr_qr_get_allowed_base_groups')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	while ($cur_group = $forum_db->fetch_assoc($result))
		echo "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].($cur_group['g_id'] == ForumCore::$forum_config['o_default_user_group'] ? '" selected="selected">' : '">').forum_htmlencode($cur_group['g_title']).'</option>'."\n";

	?>
							</select></span>
						</div>
					</div>
	<?php ($hook = get_hook('agr_pre_add_group_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
	<?php ($hook = get_hook('agr_add_group_fieldset_end')) ? eval($hook) : null; ?>
				<div class="frm-buttons">
					<span class="submit primary"><input type="submit" name="add_group" value="<?php echo ForumCore::$lang['Add group'] ?> " /></span>
				</div>
			</form>
		</div>
	<?php

		// Reset fieldset counter
		ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

	?>
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo ForumCore::$lang['Default group heading'] ?></span></h2>
		</div>
		<div class="main-content main-frm">
			<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_groups') ); ?>">
				<div class="hidden">
					<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_groups') ); ?>" />
				</div>
	<?php ($hook = get_hook('agr_pre_default_group_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><span><?php echo ForumCore::$lang['Default group legend'] ?></span></legend>
	<?php ($hook = get_hook('agr_pre_default_group')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box select">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Default group label'] ?></span></label><br />
							<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="default_group">
	<?php

	$query = array(
		'SELECT'	=> 'g.g_id, g.g_title',
		'FROM'		=> 'groups AS g',
		'WHERE'		=> 'g_id>'.FORUM_GUEST.' AND g_moderator=0',
		'ORDER BY'	=> 'g.g_title'
	);

	($hook = get_hook('agr_qr_get_groups')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	while ($cur_group = $forum_db->fetch_assoc($result))
	{
		if ($cur_group['g_id'] == ForumCore::$forum_config['o_default_user_group'])
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.forum_htmlencode($cur_group['g_title']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.forum_htmlencode($cur_group['g_title']).'</option>'."\n";
	}

	?>
							</select></span>
						</div>
					</div>
	<?php ($hook = get_hook('agr_pre_default_group_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
	<?php ($hook = get_hook('agr_default_group_fieldset_end')) ? eval($hook) : null; ?>
				<div class="frm-buttons">
					<span class="submit primary"><input type="submit" name="set_default_group" value="<?php echo ForumCore::$lang['Set default'] ?>" /></span>
				</div>
			</form>
		</div>
	<?php

		// Reset counter
		ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

	?>
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo ForumCore::$lang['Existing groups heading'] ?></span></h2>
		</div>
		<div class="main-content main-frm">
			<div class="ct-box warn-box">
				<p><?php echo ForumCore::$lang['Existing groups intro'] ?></p>
			</div>
			<div class="ct-group">
	<?php

	$query = array(
		'SELECT'	=> 'g.g_id, g.g_title',
		'FROM'		=> 'groups AS g',
		'ORDER BY'	=> 'g.g_title'
	);

	($hook = get_hook('agr_qr_get_group_list')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	ForumCore::$forum_page['item_num'] = 0;
	while ($cur_group = $forum_db->fetch_assoc($result))
	{
		ForumCore::$forum_page['group_options'] = array(
			'edit' => '<span class="first-item"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_groups']).'&edit_group='.$cur_group['g_id'].'">'.ForumCore::$lang['Edit group'].'</a></span>'
		);

		if ($cur_group['g_id'] > FORUM_GUEST)
		{
			if ($cur_group['g_id'] != ForumCore::$forum_config['o_default_user_group'])
				ForumCore::$forum_page['group_options']['remove'] = '<span'.((empty(ForumCore::$forum_page['group_options'])) ? ' class="first-item"' : '').'><a href="'.pun_admin_link(ForumCore::$forum_url['admin_groups']).'&del_group='.$cur_group['g_id'].'">'.ForumCore::$lang['Remove group'].'</a></span>';
			else
				ForumCore::$forum_page['group_options']['remove'] = '<span'.((empty(ForumCore::$forum_page['group_options'])) ? ' class="first-item"' : '').'>'.ForumCore::$lang['Cannot remove default'].'</span>';
		}
		else
			ForumCore::$forum_page['group_options']['remove'] = '<span'.((empty(ForumCore::$forum_page['group_options'])) ? ' class="first-item"' : '').'>'.ForumCore::$lang['Cannot remove group'].'</span>';

		($hook = get_hook('agr_edit_group_row_pre_output')) ? eval($hook) : null;

	?>
				<div class="ct-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="ct-box">
						<h3 class="ct-legend hn"><span><?php echo forum_htmlencode($cur_group['g_title']) ?> <?php if ($cur_group['g_id'] == ForumCore::$forum_config['o_default_user_group']) echo ForumCore::$lang['default']; ?></span></h3>
						<p class="options"><?php echo implode(' ', ForumCore::$forum_page['group_options']) ?></p>
					</div>
				</div>
	<?php

		($hook = get_hook('agr_edit_group_row_post_output')) ? eval($hook) : null;
	}

	?>
			</div>
		</div>
	<?php

	($hook = get_hook('agr_end')) ? eval($hook) : null;

	require FORUM_ROOT.'footer.php';
}