<?php
/**
 * Category management page.
 *
 * Allows administrators to create, reposition, and remove categories.
 *
 * @copyright (C) 2023 HiveBB, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package HiveBB
 */

use \HiveBB\ForumCore;
use \HiveBB\DBLayer;

defined( 'ABSPATH' ) OR die();

($hook = get_hook('acg_start')) ? eval($hook) : null;

// Load the admin.php language file
ForumCore::add_lang('admin_common');
ForumCore::add_lang('admin_categories');

$forum_db = new DBLayer;

// Delete a category
if (isset($_POST['del_cat']))
{
	$cat_to_delete = intval($_POST['cat_to_delete']);
	if ($cat_to_delete < 1)
		message(ForumCore::$lang['Bad request']);

	$query = array(
		'SELECT'	=> 'c.cat_name',
		'FROM'		=> 'categories AS c',
		'WHERE'		=> 'c.id='.$cat_to_delete
	);

	($hook = get_hook('acg_del_cat_qr_get_category_name')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$cat_name = $forum_db->result($result);

	if (is_null($cat_name) || $cat_name === false)
		message(ForumCore::$lang['Bad request']);

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index'])),
		array(ForumCore::$lang['Start'], forum_link(ForumCore::$forum_url['admin_index'])),
		array(ForumCore::$lang['Categories'], forum_link(ForumCore::$forum_url['admin_categories'])),
		ForumCore::$lang['Delete category']
	);

	($hook = get_hook('acg_del_cat_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'start');
	define('FORUM_PAGE', 'admin-categories');
	require FORUM_ROOT.'header.php';

	($hook = get_hook('acg_del_cat_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php printf(ForumCore::$lang['Confirm delete cat'], forum_htmlencode($cat_name)) ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div class="ct-box warn-box">
			<p class="warn"><?php echo ForumCore::$lang['Delete category warning'] ?></p>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_categories') ); ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_categories') ) ?>" />
				<input type="hidden" name="cat_to_delete" value="<?php echo $cat_to_delete ?>" />
			</div>
			<div class="frm-buttons">
				<span class="submit primary caution"><input type="submit" name="del_cat_comply" value="<?php echo ForumCore::$lang['Delete category'] ?>" /></span>
				<span class="cancel"><input type="submit" name="del_cat_cancel" value="<?php echo ForumCore::$lang['Cancel'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	($hook = get_hook('acg_del_cat_end')) ? eval($hook) : null;
}
else
{

	// Generate an array with all categories
	$query = array(
		'SELECT'	=> 'c.id, c.cat_name, c.disp_position',
		'FROM'		=> 'categories AS c',
		'ORDER BY'	=> 'c.disp_position'
	);

	($hook = get_hook('acg_qr_get_categories')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$cat_list = array();
	while ($cur_cat = $forum_db->fetch_assoc($result))
	{
		$cat_list[] = $cur_cat;
	}

	// Setup the form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;
	ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['admin_categories']).'?action=foo';

	ForumCore::$forum_page['hidden_fields'] = array(
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token().'" />'
	);

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index'])),
		array(ForumCore::$lang['Start'], forum_link(ForumCore::$forum_url['admin_index'])),
		array(ForumCore::$lang['Categories'], forum_link(ForumCore::$forum_url['admin_categories']))
	);

	($hook = get_hook('acg_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'start');
	define('FORUM_PAGE', 'admin-categories');
	require FORUM_ROOT.'header.php';

	($hook = get_hook('acg_main_output_start')) ? eval($hook) : null;

	?>
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo ForumCore::$lang['Add category head'] ?></span></h2>
		</div>
		<div class="main-content main-frm">
			<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_categories') ); ?>">
				<div class="hidden">
					<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_categories') ) ?>" />
				</div>
	<?php ($hook = get_hook('acg_pre_add_cat_fieldset')) ? eval($hook) : null; ?>
				<div class="ct-box warn-box">
					<p><?php printf(ForumCore::$lang['Add category info'], '<a href="'.forum_link(ForumCore::$forum_url['admin_forums']).'">'.ForumCore::$lang['Add category info link text'].'</a>') ?></p>
				</div>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><span><?php echo ForumCore::$lang['Add category legend'] ?></span></legend>
	<?php ($hook = get_hook('acg_pre_new_category_name')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['New category label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="new_cat_name" size="35" maxlength="80" required /></span>
						</div>
					</div>
	<?php ($hook = get_hook('acg_pre_new_category_position')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Position label'] ?></span></label><br />
							<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="position" size="3" maxlength="3" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('acg_pre_add_cat_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
	<?php ($hook = get_hook('acg_add_cat_fieldset_end')) ? eval($hook) : null; ?>
				<div class="frm-buttons">
					<span class="submit primary"><input type="submit" name="add_cat" value="<?php echo ForumCore::$lang['Add category'] ?>" /></span>
				</div>
			</form>
		</div>
	<?php

	($hook = get_hook('acg_post_add_cat_form')) ? eval($hook) : null;

	// Reset counter
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

	if (!empty($cat_list))
	{

	?>
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo ForumCore::$lang['Del category head'] ?></span></h2>
		</div>
		<div class="main-content main-frm">
			<form class="frm-form" method="post" accept-charset="utf-8" action="">
				<div class="hidden">
					<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />

				</div>
	<?php ($hook = get_hook('acg_pre_del_cat_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Delete category'] ?></strong></legend>
	<?php ($hook = get_hook('acg_pre_del_category_select')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box select">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Select category label'] ?></span> <small><?php echo ForumCore::$lang['Delete help'] ?></small></label><br />
							<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="cat_to_delete">
	<?php

		foreach ($cat_list as $cur_category)
		{
			echo "\t\t\t\t\t\t\t".'<option value="'.$cur_category['id'].'">'.forum_htmlencode($cur_category['cat_name']).'</option>'."\n";
		}

	?>
							</select></span>
						</div>
					</div>
	<?php ($hook = get_hook('acg_pre_del_cat_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
	<?php ($hook = get_hook('acg_del_cat_fieldset_end')) ? eval($hook) : null; ?>
				<div class="frm-buttons">
					<span class="submit primary"><input type="submit" name="del_cat" value="<?php echo ForumCore::$lang['Delete category'] ?>" /></span>
				</div>
			</form>
		</div>
	<?php

	($hook = get_hook('acg_post_del_cat_form')) ? eval($hook) : null;

	// Reset counter
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

	?>
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo ForumCore::$lang['Edit categories head'] ?></span></h2>
		</div>
		<div class="main-content main-frm">
			<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_categories') ); ?>">
				<div class="hidden">
					<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_categories') ) ?>" />
				</div>
	<?php

		($hook = get_hook('acg_edit_cat_fieldsets_start')) ? eval($hook) : null;
		foreach ($cat_list as $cur_category)
		{
			ForumCore::$forum_page['item_count'] = 0;
			($hook = get_hook('acg_pre_edit_cur_cat_fieldset')) ? eval($hook) : null;

	?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><span><?php echo ForumCore::$lang['Edit category legend'] ?></span></legend>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
	<?php ($hook = get_hook('acg_pre_edit_cat_name')) ? eval($hook) : null; ?>
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Category name label'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="cat_name[<?php echo $cur_category['id'] ?>]" value="<?php echo forum_htmlencode($cur_category['cat_name']) ?>" size="35" maxlength="80" required /></span>
						</div>
	<?php ($hook = get_hook('acg_pre_edit_cat_position')) ? eval($hook) : null; ?>
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Position label'] ?></span></label><br />
							<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="cat_order[<?php echo $cur_category['id'] ?>]" value="<?php echo $cur_category['disp_position'] ?>" size="3" maxlength="3" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('acg_pre_edit_cur_cat_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
	<?php

			($hook = get_hook('acg_edit_cur_cat_fieldset_end')) ? eval($hook) : null;
		}

		($hook = get_hook('acg_edit_cat_fieldsets_end')) ? eval($hook) : null;

	?>
				<div class="frm-buttons">
					<span class="submit primary"><input type="submit" name="update" value="<?php echo ForumCore::$lang['Update all categories'] ?>" /></span>
				</div>
			</form>
		</div>
	<?php

		($hook = get_hook('acg_post_edit_cat_form')) ? eval($hook) : null;
	}

	($hook = get_hook('acg_end')) ? eval($hook) : null;
}

require FORUM_ROOT.'footer.php';
