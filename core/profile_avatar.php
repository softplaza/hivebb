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

defined( 'ABSPATH' ) OR die();

// SHORTCODE [pun_content]
add_shortcode('pun_content', function ()
{
	$section = isset($_GET['section']) ? $_GET['section'] : 'about';

	ForumCore::$forum_page['avatar_markup'] = generate_avatar_markup(ForumCore::$id, ForumUser::$user['avatar'], ForumUser::$user['avatar_width'], ForumUser::$user['avatar_height'], ForumUser::$user['username'], TRUE);

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(sprintf(ForumCore::$lang['Users profile'], ForumUser::$user['username']), forum_link(ForumCore::$forum_url['user'], ForumCore::$id)),
		ForumCore::$lang['Section avatar']
	);

	// Setup the form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;
	ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['profile_avatar'], ForumCore::$id);

	ForumCore::$forum_page['hidden_fields'] = array(
		'max_file_size'	=> '<input type="hidden" name="MAX_FILE_SIZE" value="'.ForumCore::$forum_config['o_avatars_size'].'" />',
	);

	// Setup form information
	ForumCore::$forum_page['frm_info'] = array();

	if (!empty(ForumCore::$forum_page['avatar_markup']))
	{
		ForumCore::$forum_page['frm_info']['avatar_replace'] = '<li><span>'.ForumCore::$lang['Avatar info replace'].'</span></li>';
		ForumCore::$forum_page['frm_info']['avatar_type'] = '<li><span>'.ForumCore::$lang['Avatar info type'].'</span></li>';
		ForumCore::$forum_page['frm_info']['avatar_size'] = '<li><span>'.sprintf(ForumCore::$lang['Avatar info size'], ForumCore::$forum_config['o_avatars_width'], ForumCore::$forum_config['o_avatars_height'], forum_number_format(ForumCore::$forum_config['o_avatars_size']), forum_number_format(ceil(ForumCore::$forum_config['o_avatars_size'] / 1024))).'</span></li>';
		ForumCore::$forum_page['avatar_demo'] = ForumCore::$forum_page['avatar_markup'];
	}
	else
	{
		ForumCore::$forum_page['frm_info']['avatar_none'] = '<li><span>'.ForumCore::$lang['Avatar info none'].'</span></li>';
		ForumCore::$forum_page['frm_info']['avatar_info'] = '<li><span>'.ForumCore::$lang['Avatar info type'].'</span></li>';
		ForumCore::$forum_page['frm_info']['avatar_size'] = '<li><span>'.sprintf(ForumCore::$lang['Avatar info size'], ForumCore::$forum_config['o_avatars_width'], ForumCore::$forum_config['o_avatars_height'], forum_number_format(ForumCore::$forum_config['o_avatars_size']), forum_number_format(ceil(ForumCore::$forum_config['o_avatars_size'] / 1024))).'</span></li>';
	}

			($hook = get_hook('pf_change_details_avatar_output_start')) ? eval($hook) : null;

?>

	<div class="main-menu gen-content">
		<ul>
			<?php echo implode("\n\t\t", ForumCore::$forum_page['main_menu']) ?>
		</ul>
	</div>

	<div class="main-subhead">
		<h2 class="hn"><span><?php printf((ForumCore::$forum_page['own_profile']) ? ForumCore::$lang['Avatar welcome'] : ForumCore::$lang['Avatar welcome user'], forum_htmlencode(ForumUser::$user['username'])) ?></span></h2>
	</div>
	<div class="main-content main-frm">
<?php

		// If there were any errors, show them
		if (!empty(ForumCore::$errors))
		{
			ForumCore::$forum_page['errors'] = array();
			foreach (ForumCore::$errors as $cur_error)
				ForumCore::$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

			($hook = get_hook('pf_change_details_avatar_pre_errors')) ? eval($hook) : null;

?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo ForumCore::$lang['Profile update errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t", ForumCore::$forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php

		}

?>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_profile&section='.$section) ); ?>" enctype="multipart/form-data">
			<div class="hidden">
				<input type="hidden" name="user_id" value="<?php echo ForumCore::$id ?>" />
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_profile&section='.$section) ); ?>" />
				<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['hidden_fields'])."\n" ?>
			</div>
<?php ($hook = get_hook('pf_change_details_avatar_pre_fieldset')) ? eval($hook) : null; ?>
			<div class="ct-box info-box">
				<ul class="info-list">
					<?php echo implode("\n\t\t\t\t\t", ForumCore::$forum_page['frm_info'])."\n" ?>
				</ul>
			</div>
			<div id="req-msg" class="req-warn ct-box info-box">
				<p class="important"><?php echo ForumCore::$lang['No upload warn'] ?></p>
			</div>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Avatar'] ?></strong></legend>
<?php ($hook = get_hook('pf_change_details_avatar_pre_cur_avatar_info')) ? eval($hook) : null; ?>
				<div class="ct-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="ct-box">
						<h3 class="hn ct-legend"><?php echo ForumCore::$lang['Current avatar'] ?></h3>
<?php if (isset(ForumCore::$forum_page['avatar_demo'])): ?>
						<p class="avatar-demo"><span><?php echo ForumCore::$forum_page['avatar_demo'] ?></span></p>
<?php endif; ?>
						<p><?php echo (isset(ForumCore::$forum_page['avatar_demo'])) ? '<a href="'.forum_link(ForumCore::$forum_url['delete_avatar'], array(ForumCore::$id, generate_form_token('delete_avatar'.ForumCore::$id.ForumUser::$forum_user['id']))).'">'.ForumCore::$lang['Delete avatar info'].'</a>' : ForumCore::$lang['No avatar info'] ?></p>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_avatar_pre_avatar_upload')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Upload avatar file'] ?></span><small><?php echo ForumCore::$lang['Avatar upload help'] ?></small></label><br />
						<span class="fld-input"><input id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="req_file" type="file" size="40" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_avatar_pre_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_details_avatar_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="update" value="<?php echo ForumCore::$lang['Update profile'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	($hook = get_hook('pf_change_details_avatar_end')) ? eval($hook) : null;

});

