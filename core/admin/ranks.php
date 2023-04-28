<?php
/**
 * Rank management page.
 *
 * Allows administrators to control the tags given to posters based on their post count.
 *
 * @copyright (C) 2023 HiveBB, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package HiveBB
 */

use \HiveBB\ForumCore;
use \HiveBB\DBLayer;
use \HiveBB\ForumUser;

 defined( 'ABSPATH' ) OR die();

($hook = get_hook('ark_start')) ? eval($hook) : null;

if (!is_admin())
	message(ForumCore::$lang['No permission']);

// Load the admin.php language file
ForumCore::add_lang('admin_common');
ForumCore::add_lang('admin_ranks');

$forum_db = new DBLayer;

// Load the cached ranks
if (file_exists(FORUM_CACHE_DIR.'cache_ranks.php'))
	include FORUM_CACHE_DIR.'cache_ranks.php';

if (!defined('FORUM_RANKS_LOADED'))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_ranks_cache();
	require FORUM_CACHE_DIR.'cache_ranks.php';
}

// Setup the form
ForumCore::$forum_page['fld_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['group_count'] = 0;

// Setup breadcrumbs
ForumCore::$forum_page['crumbs'] = array(
	array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
	array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index'])),
	array(ForumCore::$lang['Users'], forum_link(ForumCore::$forum_url['admin_users'])),
	array(ForumCore::$lang['Ranks'], forum_link(ForumCore::$forum_url['admin_ranks']))
);

($hook = get_hook('ark_pre_header_load')) ? eval($hook) : null;

define('FORUM_PAGE_SECTION', 'users');
define('FORUM_PAGE', 'admin-ranks');
require FORUM_ROOT.'header.php';

($hook = get_hook('ark_main_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo ForumCore::$lang['Rank head'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_ranks') ); ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_ranks') ); ?>" />
			</div>
			<div class="ct-box warn-box" id="info-ranks-intro">
				<p><?php printf(ForumCore::$lang['Add rank intro'], '<a class="nowrap" href="'.forum_link(ForumCore::$forum_url['admin_settings_features']).'">'.ForumCore::$lang['Settings'].' &rarr; '.ForumCore::$lang['Features'].'</a>') ?></p>
			</div>
			<fieldset class="frm-group frm-hdgroup group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Add rank legend'] ?></strong></legend>
<?php ($hook = get_hook('ark_pre_add_rank_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?><?php echo (ForumCore::$forum_page['item_count'] == 1) ? ' mf-head' : ' mf-extra' ?>">
					<legend><span><?php echo ForumCore::$lang['New rank'] ?></span></legend>
					<div class="mf-box">
<?php ($hook = get_hook('ark_pre_add_rank_title')) ? eval($hook) : null; ?>
						<div class="mf-field mf-field1 text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span class="fld-label"><?php echo ForumCore::$lang['Rank title label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="new_rank" size="24" maxlength="50" required /></span>
						</div>
<?php ($hook = get_hook('ark_pre_add_rank_min_posts')) ? eval($hook) : null; ?>
						<div class="mf-field text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span class="fld-label"><?php echo ForumCore::$lang['Min posts label'] ?></span></label><br />
							<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="new_min_posts" size="7" maxlength="7" required /></span>
						</div>
<?php ($hook = get_hook('ark_pre_add_rank_submit')) ? eval($hook) : null; ?>
						<div class="mf-field text">
							<span class="submit"><input type="submit" name="add_rank" value="<?php echo ForumCore::$lang['Add rank'] ?>" /></span>
						</div>
					</div>
<?php ($hook = get_hook('ark_pre_add_rank_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('ark_add_rank_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
		</form>
<?php

if (!empty($forum_ranks))
{
	// Reset fieldset counter
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_ranks') ); ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_ranks') ); ?>" />
			</div>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo ForumCore::$lang['Existing ranks legend'] ?></span></legend>
<?php

	foreach ($forum_ranks as $rank_key => $cur_rank)
	{

	?>
<?php ($hook = get_hook('ark_pre_edit_cur_rank_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set mf-extra set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<legend><span><?php echo ForumCore::$lang['Existing rank'] ?></span></legend>
					<div class="mf-box">
<?php ($hook = get_hook('ark_pre_edit_cur_rank_title')) ? eval($hook) : null; ?>
						<div class="mf-field text mf-field1">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Rank title label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="rank[<?php echo $cur_rank['id'] ?>]" value="<?php echo forum_htmlencode($cur_rank['rank']) ?>" size="24" maxlength="50" required /></span>
						</div>
<?php ($hook = get_hook('ark_pre_edit_cur_rank_min_posts')) ? eval($hook) : null; ?>
						<div class="mf-field text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span class="fld-label"><?php echo ForumCore::$lang['Min posts label'] ?></span></label><br />
							<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="min_posts[<?php echo $cur_rank['id'] ?>]" value="<?php echo $cur_rank['min_posts'] ?>" size="7" maxlength="7" required /></span>
						</div>
<?php ($hook = get_hook('ark_pre_edit_cur_rank_submit')) ? eval($hook) : null; ?>
						<div class="mf-field text">
							<span class="submit"><input type="submit" name="update[<?php echo $cur_rank['id'] ?>]" value="<?php echo ForumCore::$lang['Update'] ?>" /> <input type="submit" name="remove[<?php echo $cur_rank['id'] ?>]" value="<?php echo ForumCore::$lang['Remove'] ?>" /></span>
						</div>
					</div>
<?php ($hook = get_hook('ark_pre_edit_cur_rank_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php

		($hook = get_hook('ark_edit_cur_rank_fieldset_end')) ? eval($hook) : null;

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
				<p><?php echo ForumCore::$lang['No ranks'] ?></p>
			</div>
		</div>
	</div>
<?php

}

($hook = get_hook('ark_end')) ? eval($hook) : null;

require FORUM_ROOT.'footer.php';
