<?php
/**
 * Topic pruning page.
 *
 * Allows administrators to delete older topics from the site.
 *
 * @copyright (C) 2023 HiveBB, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package HiveBB
 */

use \HiveBB\ForumCore;
use \HiveBB\DBLayer;

defined( 'ABSPATH' ) OR die();

($hook = get_hook('apr_start')) ? eval($hook) : null;

if (!is_admin())
	message(ForumCore::$lang['No permission']);

// Load the admin.php language file
ForumCore::add_lang('admin_common');
ForumCore::add_lang('admin_prune');

$forum_db = new DBLayer;

if (isset($_GET['action']) || isset($_POST['prune']) || isset($_POST['prune_comply']))
{
	$prune_days = intval($_POST['req_prune_days']);
	if ($prune_days < 0)
		message(ForumCore::$lang['Days to prune message']);

	$prune_date = time() - ($prune_days * 86400);
	$prune_from = $_POST['prune_from'];

	if ($prune_from != 'all')
	{
		$prune_from = intval($prune_from);

		// Fetch the forum name (just for cosmetic reasons)
		$query = array(
			'SELECT'	=> 'f.forum_name',
			'FROM'		=> 'forums AS f',
			'WHERE'		=> 'f.id='.$prune_from
		);

		($hook = get_hook('apr_prune_comply_qr_get_forum_name')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$forum = forum_htmlencode($forum_db->result($result));
	}
	else
		$forum = 'all forums';

	// Count the number of topics to prune
	$query = array(
		'SELECT'	=> 'COUNT(t.id)',
		'FROM'		=> 'topics AS t',
		'WHERE'		=> 't.last_post<'.$prune_date.' AND t.moved_to IS NULL'
	);

	if ($prune_from != 'all')
		$query['WHERE'] .= ' AND t.forum_id='.$prune_from;
	if (!isset($_POST['prune_sticky']))
		$query['WHERE'] .= ' AND t.sticky=0';

	($hook = get_hook('apr_prune_comply_qr_get_topic_count')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$num_topics = $forum_db->result($result);

	if (!$num_topics)
		message(ForumCore::$lang['No days old message']);


	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index'])),
		array(ForumCore::$lang['Management'], forum_link(ForumCore::$forum_url['admin_reports'])),
		array(ForumCore::$lang['Prune topics'], forum_link(ForumCore::$forum_url['admin_management_prune'])),
		ForumCore::$lang['Confirm prune heading']
	);

	($hook = get_hook('apr_prune_comply_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'management');
	define('FORUM_PAGE', 'admin-prune');
	require FORUM_ROOT.'header.php';

	($hook = get_hook('apr_prune_comply_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php printf(ForumCore::$lang['Prune details head'], ($forum == 'all forums') ? ForumCore::$lang['All forums'] : $forum ) ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_prune') ); ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_prune') ) ?>" />
				<input type="hidden" name="prune_days" value="<?php echo $prune_days ?>" />
				<input type="hidden" name="prune_sticky" value="<?php echo intval($_POST['prune_sticky']) ?>" />
				<input type="hidden" name="prune_from" value="<?php echo $prune_from ?>" />
			</div>
			<div class="ct-box warn-box">
				<p class="warn"><span><?php printf(ForumCore::$lang['Prune topics info 1'], $num_topics, isset($_POST['prune_sticky']) ? ' ('.ForumCore::$lang['Include sticky'].')' : '') ?></span></p>
				<p class="warn"><span><?php printf(ForumCore::$lang['Prune topics info 2'], $prune_days) ?></span></p>
			</div>
<?php ($hook = get_hook('apr_prune_comply_pre_buttons')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="prune_comply" value="<?php echo ForumCore::$lang['Prune topics'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	($hook = get_hook('apr_prune_comply_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());

	require FORUM_ROOT.'footer.php';
}
else
{
	// Setup form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index'])),
		array(ForumCore::$lang['Management'], forum_link(ForumCore::$forum_url['admin_management_reports'])),
		array(ForumCore::$lang['Prune topics'], forum_link(ForumCore::$forum_url['admin_management_prune']))
	);

	($hook = get_hook('apr_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'management');
	define('FORUM_PAGE', 'admin-prune');
	require FORUM_ROOT.'header.php';

	($hook = get_hook('apr_main_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo ForumCore::$lang['Prune settings head'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div class="ct-box warn-box">
			<p><?php echo ForumCore::$lang['Prune intro'] ?></p>
			<p class="important"><?php echo ForumCore::$lang['Prune caution'] ?></p>
		</div>
		<div id="req-msg" class="req-warn ct-box error-box">
			<p class="important"><?php echo ForumCore::$lang['Required warn'] ?></p>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(); ?>" />
				<input type="hidden" name="form_sent" value="1" />
			</div>
<?php ($hook = get_hook('apr_pre_prune_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo ForumCore::$lang['Prune legend'] ?></span></legend>
<?php ($hook = get_hook('apr_pre_prune_from')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box select">
					<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span class="fld-label"><?php echo ForumCore::$lang['Prune from'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="prune_from">
							<option value="all"><?php echo ForumCore::$lang['All forums'] ?></option>
<?php

	$query = array(
		'SELECT'	=> 'c.id AS cid, c.cat_name, f.id AS fid, f.forum_name',
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

	($hook = get_hook('apr_qr_get_forum_list')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$cur_category = 0;
	while ($forum = $forum_db->fetch_assoc($result))
	{
		($hook = get_hook('apr_pre_prune_forum_loop_start')) ? eval($hook) : null;
		
		if ($forum['cid'] != $cur_category)	// Are we still in the same category?
		{
			if ($cur_category)
				echo "\t\t\t\t\t\t\t\t".'</optgroup>'."\n";

			echo "\t\t\t\t\t\t\t\t".'<optgroup label="'.forum_htmlencode($forum['cat_name']).'">'."\n";
			$cur_category = $forum['cid'];
		}

		echo "\t\t\t\t\t\t\t\t\t".'<option value="'.$forum['fid'].'">'.forum_htmlencode($forum['forum_name']).'</option>'."\n";
		
		($hook = get_hook('apr_pre_prune_forum_loop_end')) ? eval($hook) : null;
	}

?>
						</optgroup>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('apr_pre_prune_days')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Days old'] ?></span></label><br />
						<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="req_prune_days" size="4" maxlength="4" required /></span>
					</div>
				</div>
<?php ($hook = get_hook('apr_pre_prune_sticky')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="prune_sticky" value="1" checked="checked" /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Prune sticky enable'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('apr_pre_prune_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('apr_prune_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="prune" value="<?php echo ForumCore::$lang['Prune topics'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	require FORUM_ROOT.'footer.php';
}
