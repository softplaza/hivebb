<?php
/**
 * Search index rebuilding script.
 *
 * Allows administrators to rebuild the index used to search the posts and topics.
 *
 * @copyright (C) 2023 HiveBB, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package HiveBB
 */
use \HiveBB\ForumCore;
use \HiveBB\DBLayer;
use \HiveBB\ForumUser;

defined( 'ABSPATH' ) OR die();

// Tell common.php that we don't want output buffering
define('FORUM_DISABLE_BUFFERING', 1);

($hook = get_hook('ari_start')) ? eval($hook) : null;

if (!is_admin())
	message(ForumCore::$lang['No permission']);

// Load the admin.php language file
ForumCore::add_lang('admin_common');
ForumCore::add_lang('admin_reindex');

$forum_db = new DBLayer;

$page = isset($_GET['page']) ? $_GET['page'] : null;
$section = isset($_GET['section']) ? $_GET['section'] : null;

if (isset($_GET['i_per_page']) && isset($_GET['i_start_at']))
{
	$per_page = intval($_GET['i_per_page']);
	$start_at = intval($_GET['i_start_at']);
	if ($per_page < 1 || $start_at < 1)
		message(ForumCore::$lang['Bad request']);

	($hook = get_hook('ari_cycle_start')) ? eval($hook) : null;

	@set_time_limit(0);

	// If this is the first cycle of posts we empty the search index before we proceed
	if (isset($_GET['i_empty_index']))
	{
		$query = array(
			'DELETE'	=> 'search_matches'
		);

		($hook = get_hook('ari_cycle_qr_empty_search_matches')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		$query = array(
			'DELETE'	=> 'search_words'
		);

		($hook = get_hook('ari_cycle_qr_empty_search_words')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		$result = $forum_db->query('ALTER TABLE '.$forum_db->prefix.'search_words auto_increment=1') or error(__FILE__, __LINE__);
	}

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index'])),
		array(ForumCore::$lang['Management'], forum_link(ForumCore::$forum_url['admin_reports'])),
		ForumCore::$lang['Rebuilding index title']
	);

?>
<!DOCTYPE html>
<html lang="<?php ForumCore::$lang['lang_identifier'] ?>" dir="<?php echo ForumCore::$lang['lang_direction'] ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo generate_crumbs(true) ?></title>
<style type="text/css">
body {
	font: 68.75% Verdana, Arial, Helvetica, sans-serif;
	color: #333333;
	background-color: #FFFFFF
}
</style>
</head>
<body>
<p><?php echo ForumCore::$lang['Rebuilding index'] ?></p>

<?php

	if (!defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/search_idx.php';

	// Fetch posts to process
	$query = array(
		'SELECT'	=> 'p.id, p.message, t.id, t.subject, t.first_post_id',
		'FROM'		=> 'posts AS p',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'topics AS t',
				'ON'			=> 't.id=p.topic_id'
			)
		),
		'WHERE'		=> 'p.id >= '.$start_at,
		'ORDER BY'	=> 'p.id',
		'LIMIT'		=> $per_page
	);
	($hook = get_hook('ari_cycle_qr_fetch_posts')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$post_id = 0;
	echo '<p>';
	while ($cur_post = $forum_db->fetch_row($result))
	{
		echo sprintf(ForumCore::$lang['Processing post'], $cur_post[0], $cur_post[2]).'<br />'."\n";

		if ($cur_post[0] == $cur_post[4])	// This is the "topic post" so we have to index the subject as well
			update_search_index('post', $cur_post[0], $cur_post[1], $cur_post[3]);
		else
			update_search_index('post', $cur_post[0], $cur_post[1]);

		$post_id = $cur_post[0];
	}
	echo '</p>';

	// Check if there is more work to do
	$query = array(
		'SELECT'	=> 'p.id',
		'FROM'		=> 'posts AS p',
		'WHERE'		=> 'p.id > '.$post_id,
		'ORDER BY'	=> 'p.id',
		'LIMIT'		=> '1'
	);
	($hook = get_hook('ari_cycle_qr_find_next_post')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$next_posts_to_proced = $forum_db->result($result);

	$query_str = '';
	if (!is_null($next_posts_to_proced) && $next_posts_to_proced !== false)
	{
		$query_str = '?i_per_page='.$per_page.'&i_start_at='.$next_posts_to_proced.'&csrf_token='.generate_form_token('reindex'.ForumUser::$forum_user['id']);
	}

	($hook = get_hook('ari_cycle_end')) ? eval($hook) : null;

	$forum_db->end_transaction();
	$forum_db->close();

	exit('<script type="text/javascript">window.location="'.pun_admin_link(ForumCore::$forum_url['admin_management_reindex']).$query_str.'"</script><br />'.ForumCore::$lang['Javascript redirect'].' <a href="'.pun_admin_link(ForumCore::$forum_url['admin_management_reindex']).$query_str.'">'.ForumCore::$lang['Click to continue'].'</a>.');
}
else
{
	// Get the first post ID from the db
	$query = array(
		'SELECT'	=> 'p.id',
		'FROM'		=> 'posts AS p',
		'ORDER BY'	=> 'p.id',
		'LIMIT'		=> '1'
	);

	($hook = get_hook('ari_qr_find_lowest_post_id')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$first_id = $forum_db->result($result);

	if (is_null($first_id) || $first_id === false)
	{
		unset($first_id);
	}

	// Setup form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index'])),
		array(ForumCore::$lang['Management'], forum_link(ForumCore::$forum_url['admin_management_reports'])),
		array(ForumCore::$lang['Rebuild index'], forum_link(ForumCore::$forum_url['admin_management_reindex']))
	);

	($hook = get_hook('ari_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'management');
	define('FORUM_PAGE', 'admin-reindex');
	require FORUM_ROOT.'header.php';

	($hook = get_hook('ari_main_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo ForumCore::$lang['Reindex heading'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div class="ct-box warn-box">
			<p><?php echo ForumCore::$lang['Reindex info'] ?></p>
			<p class="important"><?php echo ForumCore::$lang['Reindex warning'] ?></p>
			<p class="warn"><?php echo ForumCore::$lang['Empty index warning'] ?></p>
		</div>
		<form class="frm-form" method="get" accept-charset="utf-8" action="">
			<div class="hidden">
				<input type="hidden" name="page" value="<?php echo $page ?>" />
				<input type="hidden" name="section" value="<?php echo $section ?>" />
			</div>
<?php ($hook = get_hook('ari_pre_rebuild_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo ForumCore::$lang['Rebuild index legend'] ?></span></legend>
<?php ($hook = get_hook('ari_pre_rebuild_per_page')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Posts per cycle'] ?></span> <small><?php echo ForumCore::$lang['Posts per cycle info'] ?></small></label><br />
						<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="i_per_page" size="7" maxlength="7" value="100" /></span>
					</div>
				</div>
<?php ($hook = get_hook('ari_pre_rebuild_start_post')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span class="fld-label"><?php echo ForumCore::$lang['Starting post'] ?></span> <small><?php echo ForumCore::$lang['Starting post info'] ?></small></label><br />
						<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="i_start_at" size="7" maxlength="7" value="<?php echo (isset($first_id)) ? $first_id : 0 ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('ari_pre_rebuild_empty_index')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="i_empty_index" value="1" checked="checked" /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Empty index'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('ari_pre_rebuild_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('ari_rebuild_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="rebuild_index" value="<?php echo ForumCore::$lang['Rebuild index'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	($hook = get_hook('ari_end')) ? eval($hook) : null;

	require FORUM_ROOT.'footer.php';
}