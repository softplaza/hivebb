<?php
/**
 * Word censor management page.
 *
 * Allows administrators and moderators to add, modify, and delete the word censors used by the software when censoring is enabled.
 *
 * @copyright (C) 2023 HiveBB, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package HiveBB
 */

use \HiveBB\ForumCore;
use \HiveBB\DBLayer;
use \HiveBB\ForumUser;

defined( 'ABSPATH' ) OR die();

($hook = get_hook('acs_start')) ? eval($hook) : null;

if (!is_admin())
	message(ForumCore::$lang['No permission']);

// Load the admin.php language file
ForumCore::add_lang('admin_common');
ForumCore::add_lang('admin_censoring');

$forum_db = new DBLayer;


// Load the cached censors
if (file_exists(FORUM_CACHE_DIR.'cache_censors.php'))
	include FORUM_CACHE_DIR.'cache_censors.php';

if (!defined('FORUM_CENSORS_LOADED'))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_censors_cache();
	require FORUM_CACHE_DIR.'cache_censors.php';
}

// Setup the form
ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

// Setup breadcrumbs
ForumCore::$forum_page['crumbs'] = array(
	array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
	array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index']))
);
if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN)
	ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Settings'], forum_link(ForumCore::$forum_url['admin_settings_setup']));
ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Censoring'], forum_link(ForumCore::$forum_url['admin_management_censoring']));


($hook = get_hook('acs_pre_header_load')) ? eval($hook) : null;

define('FORUM_PAGE_SECTION', 'management');
define('FORUM_PAGE', 'admin-censoring');
require FORUM_ROOT.'header.php';

($hook = get_hook('acs_main_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo ForumCore::$lang['Censored word head'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_censoring') ); ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_censoring') ); ?>" />
			</div>
			<div class="ct-box warn-box" id="info-censored-intro">
				<p><?php echo ForumCore::$lang['Add censored word intro']; if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN) printf(' '.ForumCore::$lang['Add censored word extra'], '<a class="nowrap" href="'.forum_link(ForumCore::$forum_url['admin_settings_features']).'">'.ForumCore::$lang['Settings'].' &rarr; '.ForumCore::$lang['Features'].'</a>') ?></p>
			</div>
			<fieldset class="frm-group frm-hdgroup group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo ForumCore::$lang['Add censored word legend'] ?></span></legend>
<?php ($hook = get_hook('acs_pre_add_word_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?><?php echo (ForumCore::$forum_page['item_count'] == 1) ? ' mf-head' : ' mf-extra' ?>">
					<legend><span><?php echo ForumCore::$lang['Add new word legend'] ?></span></legend>
					<div class="mf-box">
<?php ($hook = get_hook('acs_pre_add_search_for')) ? eval($hook) : null; ?>
						<div class="mf-field mf-field1">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span class="fld-label"><?php echo ForumCore::$lang['Censored word label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="new_search_for" size="24" maxlength="60" required /></span>
						</div>
<?php ($hook = get_hook('acs_pre_add_replace_with')) ? eval($hook) : null; ?>
						<div class="mf-field">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span class="fld-label"><?php echo ForumCore::$lang['Replacement label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="new_replace_with" size="24" maxlength="60" required /></span>
						</div>
<?php ($hook = get_hook('acs_pre_add_submit')) ? eval($hook) : null; ?>
						<div class="mf-field">
							<span class="submit"><input type="submit" name="add_word" value=" <?php echo ForumCore::$lang['Add word'] ?> " /></span>
						</div>
					</div>
<?php ($hook = get_hook('acs_pre_add_word_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('acs_add_word_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
		</form>
<?php

if (!empty($forum_censors))
{
	// Reset
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_censoring') ); ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_censoring') ); ?>" />
			</div>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo ForumCore::$lang['Edit censored word legend'] ?></span></legend>
<?php
	foreach ($forum_censors as $censor_key => $cur_word)
	{
?>
<?php ($hook = get_hook('acs_pre_edit_word_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set mf-extra set<?php echo ++ForumCore::$forum_page['item_count'] ?><?php echo (ForumCore::$forum_page['item_count'] == 1) ? ' mf-head' : ' mf-extra' ?>">
					<legend><span><?php echo ForumCore::$lang['Existing censored word legend'] ?></span></legend>
					<div class="mf-box">
<?php ($hook = get_hook('acs_pre_edit_search_for')) ? eval($hook) : null; ?>
						<div class="mf-field mf-field1">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Censored word label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="search_for[<?php echo $cur_word['id'] ?>]" value="<?php echo forum_htmlencode($cur_word['search_for']) ?>" size="24" maxlength="60" required /></span>
						</div>
<?php ($hook = get_hook('acs_pre_edit_replace_with')) ? eval($hook) : null; ?>
						<div class="mf-field">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Replacement label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="replace_with[<?php echo $cur_word['id'] ?>]" value="<?php echo forum_htmlencode($cur_word['replace_with']) ?>" size="24" maxlength="60" required /></span>
						</div>
<?php ($hook = get_hook('acs_pre_edit_submit')) ? eval($hook) : null; ?>
						<div class="mf-field">
							<span class="submit"><input type="submit" name="update[<?php echo $cur_word['id'] ?>]" value="<?php echo ForumCore::$lang['Update'] ?>" /> <input type="submit" name="remove[<?php echo $cur_word['id'] ?>]" value="<?php echo ForumCore::$lang['Remove'] ?>" formnovalidate /></span>
						</div>
					</div>
<?php ($hook = get_hook('acs_pre_edit_word_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('acs_edit_word_fieldset_end')) ? eval($hook) : null; ?>

<?php
	}
?>
			</fieldset>
		</form>
	</div>
<?php

}
else
{

?>
		<div class="frm-form">
			<div class="ct-box warn-box">
				<p><?php echo ForumCore::$lang['No censored words'] ?></p>
			</div>
		</div>
	</div>
<?php

}

($hook = get_hook('acs_end')) ? eval($hook) : null;

require FORUM_ROOT.'footer.php';
