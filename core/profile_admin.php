<?php
/**
 * Allows users to view and edit their details.
 *
 * @copyright (C) 2023 HiveBB, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package HiveBB
 */

use \HiveBB\ForumCore;
use \HiveBB\ForumUser;
use \HiveBB\DBLayer;

defined( 'ABSPATH' ) OR die();

// SHORTCODE [pun_content]
add_shortcode('pun_content', function ()
{
	$section = isset($_GET['section']) ? $_GET['section'] : 'about';

	$forum_db = new DBLayer;

	if (ForumUser::$forum_user['g_id'] != FORUM_ADMIN && (ForumUser::$forum_user['g_moderator'] != '1' || ForumUser::$forum_user['g_mod_ban_users'] == '0' || ForumUser::$forum_user['id'] == ForumCore::$id))
	message(ForumCore::$lang['Bad request']);

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(sprintf(ForumCore::$lang['Users profile'], ForumUser::$user['username']), forum_link(ForumCore::$forum_url['user'], ForumCore::$id)),
		ForumCore::$lang['Section admin']
	);

	// Setup form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;
	ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['profile_admin'], ForumCore::$id);

	// Setup ban and delete options
	ForumCore::$forum_page['user_management'] = array();

	if (ForumUser::$forum_user['g_moderator'] == '1')
		ForumCore::$forum_page['user_management']['ban'] = '<div class="ct-set set'.++ForumCore::$forum_page['item_count'].'">'."\n\t\t\t\t".'<div class="ct-box">'."\n\t\t\t\t\t".'<h3 class="ct-legend hn">'.ForumCore::$lang['Ban user'].'</h3>'."\n\t\t\t\t".'<p><a href="'.forum_link(ForumCore::$forum_url['admin_bans']).'&amp;add_ban='.ForumCore::$id.'">'.ForumCore::$lang['Ban user info'].'</a></p>'."\n\t\t\t\t".'</div>'."\n\t\t\t".'</div>';
	
	else if (ForumUser::$forum_user['g_moderator'] != '1' && ForumUser::$user['g_id'] != FORUM_ADMIN )
	{
		ForumCore::$forum_page['user_management']['ban'] = '<div class="ct-set set'.++ForumCore::$forum_page['item_count'].'">'."\n\t\t\t\t".'<div class="ct-box">'."\n\t\t\t\t\t".'<h3 class="ct-legend hn">'.ForumCore::$lang['Ban user'].'</h3>'."\n\t\t\t\t".'<p><a href="'.pun_admin_link(ForumCore::$forum_url['admin_bans']).'&amp;add_ban='.ForumCore::$id.'">'.ForumCore::$lang['Ban user info'].'</a></p>'."\n\t\t\t\t".'</div>'."\n\t\t\t".'</div>';

		ForumCore::$forum_page['user_management']['delete'] = '<div class="ct-set set'.++ForumCore::$forum_page['item_count'].'">'."\n\t\t\t\t".'<div class="ct-box">'."\n\t\t\t\t\t".'<h3 class="ct-legend hn">'.ForumCore::$lang['Delete user'].'</h3>'."\n\t\t\t\t".'<p><a href="'.forum_link(ForumCore::$forum_url['delete_user'], ForumCore::$id).'">'.ForumCore::$lang['Delete user info'].'</a></p>'."\n\t\t\t\t".'</div>'."\n\t\t\t".'</div>';
	}

	($hook = get_hook('pf_change_details_admin_output_start')) ? eval($hook) : null;

?>
	<div class="main-menu gen-content">
		<ul>
			<?php echo implode("\n\t\t", ForumCore::$forum_page['main_menu']) ?>
		</ul>
	</div>

	<div class="main-subhead">
		<h2 class="hn"><span><?php echo ForumCore::$lang['User management'] ?></span></h2>
	</div>

	<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_profile&section='.$section) ); ?>">
		<div class="hidden">
			<input type="hidden" name="user_id" value="<?php echo ForumCore::$id ?>" />
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_profile&section='.$section) ); ?>" />
		</div>
		<div class="main-content main-frm">
			<div class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
<?php

		($hook = get_hook('pf_change_details_admin_pre_user_management')) ? eval($hook) : null;

	if (!empty(ForumCore::$forum_page['user_management']))
	{

		echo "\t\t\t".implode("\n\t\t\t", ForumCore::$forum_page['user_management'])."\n";

		($hook = get_hook('pf_change_details_admin_pre_membership')) ? eval($hook) : null;

		if (ForumUser::$forum_user['g_moderator'] != '1' && !ForumCore::$forum_page['own_profile'])
		{

			($hook = get_hook('pf_change_details_admin_pre_group_membership')) ? eval($hook) : null;

?>
			<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
				<div class="sf-box select">
					<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['User group'] ?></span></label><br />
					<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="group_id">
<?php

			$query = array(
				'SELECT'	=> 'g.g_id, g.g_title',
				'FROM'		=> 'groups AS g',
				'WHERE'		=> 'g.g_id!='.FORUM_GUEST,
				'ORDER BY'	=> 'g.g_title'
			);

			($hook = get_hook('pf_change_details_admin_qr_get_groups')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			while ($cur_group = $forum_db->fetch_assoc($result))
			{
				if ($cur_group['g_id'] == ForumUser::$user['g_id'] || ($cur_group['g_id'] == ForumCore::$forum_config['o_default_user_group'] && ForumUser::$user['g_id'] == ''))
					echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.forum_htmlencode($cur_group['g_title']).'</option>'."\n";
				else
					echo "\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.forum_htmlencode($cur_group['g_title']).'</option>'."\n";
			}

?>
					</select></span>
				</div>
			</div>
<?php ($hook = get_hook('pf_change_details_admin_pre_group_membership_submit')) ? eval($hook) : null; ?>
			<div class="sf-set button-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<span class="submit primary"><input type="submit" name="update_group_membership" value="<?php echo ForumCore::$lang['Update groups'] ?>" /></span>
				</div>
			</div>
<?php

		}
	}

	($hook = get_hook('pf_change_details_admin_pre_mod_assignment')) ? eval($hook) : null;

	if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN && (ForumUser::$user['g_id'] == FORUM_ADMIN || ForumUser::$user['g_moderator'] == '1'))
	{
		($hook = get_hook('pf_change_details_admin_pre_mod_assignment_fieldset')) ? eval($hook) : null;

?>
			<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
				<legend><span><?php echo ForumCore::$lang['Moderator assignment'] ?></span></legend>
<?php ($hook = get_hook('pf_change_details_admin_pre_forum_checklist')) ? eval($hook) : null; ?>
				<div class="mf-box">
					<div class="checklist">
<?php

		$query = array(
			'SELECT'	=> 'c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.moderators',
			'FROM'		=> 'categories AS c',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'forums AS f',
					'ON'			=> 'c.id=f.cat_id'
				)
			),
			'WHERE'		=> 'f.redirect_url IS NULL',
			'ORDER BY'	=> 'c.disp_position, c.id, f.disp_position'
		);

		($hook = get_hook('pf_change_details_admin_qr_get_cats_and_forums')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$cur_category = 0;
		while ($cur_forum = $forum_db->fetch_assoc($result))
		{
			($hook = get_hook('pf_change_details_admin_forum_loop_start')) ? eval($hook) : null;

			if ($cur_forum['cid'] != $cur_category)	// A new category since last iteration?
			{
				if ($cur_category)
						echo "\n\t\t\t\t\t\t".'</fieldset>'."\n";

				echo "\t\t\t\t\t\t".'<fieldset>'."\n\t\t\t\t\t\t\t".'<legend><span>'.$cur_forum['cat_name'].':</span></legend>'."\n";
				$cur_category = $cur_forum['cid'];
			}

			$moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

			echo "\t\t\t\t\t\t\t".'<div class="checklist-item"><span class="fld-input"><input type="checkbox" id="fld'.(++ForumCore::$forum_page['fld_count']).'" name="moderator_in['.$cur_forum['fid'].']" value="1"'.((in_array(ForumCore::$id, $moderators)) ? ' checked="checked"' : '').' /></span> <label for="fld'.ForumCore::$forum_page['fld_count'].'">'.forum_htmlencode($cur_forum['forum_name']).'</label></div>'."\n";
			
			($hook = get_hook('pf_change_details_admin_forum_loop_end')) ? eval($hook) : null;
		}

		if ($cur_category)
			echo "\t\t\t\t\t\t".'</fieldset>'."\n";
?>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_admin_pre_mod_assignment_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_details_admin_mod_assignment_fieldset_end')) ? eval($hook) : null; ?>
			<div class="mf-set button-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
				<div class="mf-box text">
					<span class="submit primary"><input type="submit" name="update_forums" value="<?php echo ForumCore::$lang['Update forums'] ?>" /></span>
				</div>
			</div>
<?php

		($hook = get_hook('pf_change_details_admin_form_end')) ? eval($hook) : null;
	}

?>
		</div>
		<div class="frm-buttons">
			<span class="submit primary"><?php echo ForumCore::$lang['Instructions'] ?></span>
		</div>
	</div>
	</form>
<?php

	($hook = get_hook('pf_change_details_admin_end')) ? eval($hook) : null;

});

