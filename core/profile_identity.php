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

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(sprintf(ForumCore::$lang['Users profile'], ForumUser::$user['username']), forum_link(ForumCore::$forum_url['user'], ForumCore::$id)),
		ForumCore::$lang['Section identity']
	);
	// Setup the form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;
	ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['profile_identity'], ForumCore::$id);

	if (ForumUser::$forum_user['is_admmod'] && (ForumUser::$forum_user['g_id'] == FORUM_ADMIN || ForumUser::$forum_user['g_mod_rename_users'] == '1'))
		ForumCore::$forum_page['hidden_fields']['old_username'] = '<input type="hidden" name="old_username" value="'.forum_htmlencode(ForumUser::$user['username']).'" />';

	// Does the form have required fields
	ForumCore::$forum_page['has_required'] = (((ForumUser::$forum_user['is_admmod'] && (ForumUser::$forum_user['g_id'] == FORUM_ADMIN || ForumUser::$forum_user['g_mod_rename_users'] == '1')) || ForumUser::$forum_user['is_admmod']) ? true : false);


	($hook = get_hook('pf_change_details_identity_output_start')) ? eval($hook) : null;

?>
	<div class="main-menu gen-content">
		<ul>
			<?php echo implode("\n\t\t", ForumCore::$forum_page['main_menu']) ?>
		</ul>
	</div>

	<div class="main-subhead">
		<h2 class="hn"><span><?php printf((ForumCore::$forum_page['own_profile']) ? ForumCore::$lang['Identity welcome'] : ForumCore::$lang['Identity welcome user'], forum_htmlencode(ForumUser::$user['username'])) ?></span></h2>
	</div>

	<div class="main-content main-frm">
<?php

	// If there were any errors, show them
	if (!empty(ForumCore::$errors))
	{
		ForumCore::$forum_page['errors'] = array();
			foreach (ForumCore::$errors as $cur_error)
			ForumCore::$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

		($hook = get_hook('pf_change_details_identity_pre_errors')) ? eval($hook) : null;

?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo ForumCore::$lang['Profile update errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php

	}

if (ForumCore::$forum_page['has_required']): ?>
		<div id="req-msg" class="req-warn ct-box error-box">
			<p class="important"><?php echo ForumCore::$lang['Required warn'] ?></p>
		</div>
<?php endif; ?>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_profile&section='.$section) ); ?>">
			<div class="hidden">
				<input type="hidden" name="user_id" value="<?php echo ForumCore::$id ?>" />
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_profile&section='.$section) ); ?>" />
				<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['hidden_fields'])."\n" ?>
			</div>
<?php if (ForumCore::$forum_page['has_required']): ($hook = get_hook('pf_change_details_identity_pre_req_info_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Required information'] ?></strong></legend>
<?php ($hook = get_hook('pf_change_details_identity_pre_username')) ? eval($hook) : null; ?>
<?php if (ForumUser::$forum_user['is_admmod'] && (ForumUser::$forum_user['g_id'] == FORUM_ADMIN || ForumUser::$forum_user['g_mod_rename_users'] == '1')): ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Username'] ?></span> <small><?php echo ForumCore::$lang['Username help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="req_username" value="<?php echo(isset($_POST['req_username']) ? forum_htmlencode($_POST['req_username']) : forum_htmlencode(ForumUser::$user['username'])) ?>" size="35" maxlength="25" required /></span>
					</div>
				</div>
<?php endif; ($hook = get_hook('pf_change_details_identity_pre_email')) ? eval($hook) : null; ?>
<?php if (ForumUser::$forum_user['is_admmod']): ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['E-mail'] ?></span> <small><?php echo ForumCore::$lang['E-mail help'] ?></small></label><br />
						<span class="fld-input"><input type="email" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="req_email" value="<?php echo(isset($_POST['req_email']) ? forum_htmlencode($_POST['req_email']) : forum_htmlencode(ForumUser::$user['email'])) ?>" size="35" maxlength="80" required /></span>
					</div>
				</div>
<?php endif; ($hook = get_hook('pf_change_details_identity_pre_req_info_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_details_identity_req_info_fieldset_end')) ? eval($hook) : null; ?>
<?php endif; ($hook = get_hook('pf_change_details_identity_pre_personal_fieldset')) ? eval($hook) : null; ?><?php ForumCore::$forum_page['item_count'] = 0; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Personal legend'] ?></strong></legend>
<?php ($hook = get_hook('pf_change_details_identity_pre_realname')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Realname'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[realname]" value="<?php echo(isset($form['realname']) ? forum_htmlencode($form['realname']) : forum_htmlencode(ForumUser::$user['realname'])) ?>" size="35" maxlength="40" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_title')) ? eval($hook) : null; ?>
<?php if (ForumUser::$forum_user['g_set_title'] == '1'): ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Title'] ?></span><small><?php echo ForumCore::$lang['Leave blank'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="title" value="<?php echo(isset($_POST['title']) ? forum_htmlencode($_POST['title']) : forum_htmlencode(ForumUser::$user['title'])) ?>" size="35" maxlength="50" /></span><br />
					</div>
				</div>
<?php endif; ?>
<?php ($hook = get_hook('pf_change_details_identity_pre_location')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Location'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[location]" value="<?php echo((isset($form['location']) ? forum_htmlencode($form['location']) : forum_htmlencode(ForumUser::$user['location']))) ?>" size="35" maxlength="30" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_admin_note')) ? eval($hook) : null; ?>
<?php if (ForumUser::$forum_user['is_admmod']): ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Admin note'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" type="text" name="admin_note" value="<?php echo(isset($_POST['admin_note']) ? forum_htmlencode($_POST['admin_note']) : forum_htmlencode(ForumUser::$user['admin_note'])) ?>" size="35" maxlength="30" /></span>
					</div>
				</div>
<?php endif; ($hook = get_hook('pf_change_details_identity_pre_num_posts')) ? eval($hook) : null; ?>
<?php if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN): ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Edit count'] ?></span></label><br />
						<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="num_posts" value="<?php echo ForumUser::$user['num_posts'] ?>" size="8" maxlength="8" /></span>
					</div>
				</div>
<?php endif; ($hook = get_hook('pf_change_details_identity_pre_personal_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_details_identity_personal_fieldset_end')) ? eval($hook) : null; ?><?php ForumCore::$forum_page['item_count'] = 0; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Contact legend'] ?></strong></legend>
<?php ($hook = get_hook('pf_change_details_identity_pre_url')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Website'] ?></span></label><br />
						<span class="fld-input"><input type="url" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[url]" value="<?php echo(isset($form['url']) ? forum_htmlencode($form['url']) : forum_htmlencode(ForumUser::$user['url'])) ?>" size="35" maxlength="80" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_facebook')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Facebook'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[facebook]" placeholder="<?php echo ForumCore::$lang['Name or Url'] ?>" value="<?php echo(isset($form['facebook']) ? forum_htmlencode($form['facebook']) : forum_htmlencode(ForumUser::$user['facebook'])) ?>" size="35" maxlength="80" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_twitter')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Twitter'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[twitter]" placeholder="<?php echo ForumCore::$lang['Name or Url'] ?>" value="<?php echo(isset($form['twitter']) ? forum_htmlencode($form['twitter']) : forum_htmlencode(ForumUser::$user['twitter'])) ?>" size="35" maxlength="80" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_linkedin')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['LinkedIn'] ?></span></label><br />
						<span class="fld-input"><input type="url" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[linkedin]" value="<?php echo(isset($form['linkedin']) ? forum_htmlencode($form['linkedin']) : forum_htmlencode(ForumUser::$user['linkedin'])) ?>" size="35" maxlength="80" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_contact_fieldset_end')) ? eval($hook) : null; ?>				
			</fieldset>
<?php ($hook = get_hook('pf_change_details_identity_contact_fieldset_end')) ? eval($hook) : null; ?>			
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Contact messengers legend'] ?></strong></legend>
<?php ($hook = get_hook('pf_change_details_identity_pre_jabber')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Jabber'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" type="email" name="form[jabber]" value="<?php echo(isset($form['jabber']) ? forum_htmlencode($form['jabber']) : forum_htmlencode(ForumUser::$user['jabber'])) ?>" size="35" maxlength="80" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_skype')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Skype'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" type="text" name="form[skype]" value="<?php echo(isset($form['skype']) ? forum_htmlencode($form['skype']) : forum_htmlencode(ForumUser::$user['skype'])) ?>" size="35" maxlength="80" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_msn')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['MSN'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" type="text" name="form[msn]" value="<?php echo(isset($form['msn']) ? forum_htmlencode($form['msn']) : forum_htmlencode(ForumUser::$user['msn'])) ?>" size="35" maxlength="80" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_icq')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['ICQ'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" type="text" name="form[icq]" value="<?php echo(isset($form['icq']) ? forum_htmlencode($form['icq']) : ForumUser::$user['icq']) ?>" size="20" maxlength="12" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_aim')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['AOL IM'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" type="text" name="form[aim]" value="<?php echo(isset($form['aim']) ? forum_htmlencode($form['aim']) : forum_htmlencode(ForumUser::$user['aim'])) ?>" size="20" maxlength="30" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_yahoo')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Yahoo'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" type="text" name="form[yahoo]" value="<?php echo(isset($form['yahoo']) ? forum_htmlencode($form['yahoo']) : forum_htmlencode(ForumUser::$user['yahoo'])) ?>" size="20" maxlength="30" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_identity_pre_messengers_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_details_identity_messengers_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="update" value="<?php echo ForumCore::$lang['Update profile'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	($hook = get_hook('pf_change_details_identity_end')) ? eval($hook) : null;

});

