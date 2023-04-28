<?php
/**
 * Provides various mass-moderation tools to moderators.
 *
 * @copyright (C) 2023 HiveBB, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package HiveBB
 */

use \HiveBB\ForumCore;
use \HiveBB\ForumUser;
use \HiveBB\DBLayer;

defined( 'ABSPATH' ) OR die();

require FORUM_ROOT.'include/common.php';

($hook = get_hook('mr_start')) ? eval($hook) : null;

// Load the misc.php language file
ForumCore::add_lang('misc');

$forum_db = new DBLayer;

// This particular function doesn't require forum-based moderator access. It can be used
// by all moderators and admins.
if (isset($_GET['get_host']))
{
	if (!ForumUser::$forum_user['is_admmod'])
		message(ForumCore::$lang['No permission']);

	$_get_host = $_GET['get_host'];
	if (!is_string($_get_host))
		message(ForumCore::$lang['Bad request']);

	($hook = get_hook('mr_view_ip_selected')) ? eval($hook) : null;

	// Is get_host an IP address or a post ID?
	if (preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $_get_host) || preg_match('/^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$/', $_get_host))
		$ip = $_get_host;
	else
	{
		$get_host = intval($_get_host);
		if ($get_host < 1)
			message(ForumCore::$lang['Bad request']);

		$query = array(
			'SELECT'	=> 'p.poster_ip',
			'FROM'		=> 'posts AS p',
			'WHERE'		=> 'p.id='.$get_host
		);

		($hook = get_hook('mr_view_ip_qr_get_poster_ip')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$ip = $forum_db->result($result);

		if (!$ip)
			message(ForumCore::$lang['Bad request']);
	}

	($hook = get_hook('mr_view_ip_pre_output')) ? eval($hook) : null;

	message(sprintf(ForumCore::$lang['Hostname lookup'], $ip, forum_htmlencode(@gethostbyaddr($ip)), '<a href="'.forum_link(ForumCore::$forum_url['admin_users']).'?show_users='.$ip.'">'.ForumCore::$lang['Show more users'].'</a>'));
}


// All other functions require moderator/admin access
ForumCore::$fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
if (ForumCore::$fid < 1)
	message(ForumCore::$lang['Bad request']);

// Get some info about the forum we're moderating
$query = array(
	'SELECT'	=> 'f.forum_name, f.redirect_url, f.num_topics, f.moderators, f.sort_by',
	'FROM'		=> 'forums AS f',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'forum_perms AS fp',
			'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.ForumUser::$forum_user['g_id'].')'
		)
	),
	'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.ForumCore::$fid
);

($hook = get_hook('mr_qr_get_forum_data')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
ForumCore::$cur_forum = $forum_db->fetch_assoc($result);

if (!ForumCore::$cur_forum)
	message(ForumCore::$lang['Bad request']);

// Make sure we're not trying to moderate a redirect forum
if (ForumCore::$cur_forum['redirect_url'] != '')
	message(ForumCore::$lang['Bad request']);

// Setup the array of moderators
$mods_array = (ForumCore::$cur_forum['moderators'] != '') ? unserialize(ForumCore::$cur_forum['moderators']) : array();

($hook = get_hook('mr_pre_permission_check')) ? eval($hook) : null;

if (ForumUser::$forum_user['g_id'] != FORUM_ADMIN && (ForumUser::$forum_user['g_moderator'] != '1' || !array_key_exists(ForumUser::$forum_user['username'], $mods_array)))
	message(ForumCore::$lang['No permission']);

// Get topic/forum tracking data
if (!ForumUser::$forum_user['is_guest'])
	$tracked_topics = get_tracked_topics();


// Did someone click a cancel button?
if (isset($_POST['cancel']))
	redirect(forum_link(ForumCore::$forum_url['forum'], array(ForumCore::$fid, sef_friendly(ForumCore::$cur_forum['forum_name']))), ForumCore::$lang['Cancel redirect']);

// All topic moderation features require a topic id in GET
if (isset($_GET['tid']))
{
	($hook = get_hook('mr_confirm_delete_posts_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'dialogue');
	require FORUM_ROOT.'header.php';

	// SHORTCODE [pun_content]
	add_shortcode('pun_content', function ()
	{
		$forum_db = new DBLayer;

		($hook = get_hook('mr_post_actions_selected')) ? eval($hook) : null;

		ForumCore::$tid = intval($_GET['tid']);
		if (ForumCore::$tid < 1)
			message(ForumCore::$lang['Bad request']);

		// Fetch some info about the topic
		$query = array(
			'SELECT'	=> 't.subject, t.poster, t.first_post_id, t.posted, t.num_replies',
			'FROM'		=> 'topics AS t',
			'WHERE'		=> 't.id='.ForumCore::$tid.' AND t.moved_to IS NULL'
		);

		($hook = get_hook('mr_post_actions_qr_get_topic_info')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		ForumCore::$cur_topic = $forum_db->fetch_assoc($result);

		if (!ForumCore::$cur_topic)
			message(ForumCore::$lang['Bad request']);

		// User pressed the cancel button
		if (isset($_POST['delete_posts_cancel']))
			redirect(forum_link(ForumCore::$forum_url['topic'], array(ForumCore::$tid, sef_friendly(ForumCore::$cur_topic['subject']))), ForumCore::$lang['Cancel redirect']);

		// Delete one or more posts
		if (isset($_POST['delete_posts']) || isset($_POST['delete_posts_comply']))
		{
			($hook = get_hook('mr_delete_posts_form_submitted')) ? eval($hook) : null;

			$posts = isset($_POST['posts']) && !empty($_POST['posts']) ? $_POST['posts'] : array();
			$posts = array_map('intval', (is_array($posts) ? $posts : explode(',', $posts)));

			if (empty($posts))
				message(ForumCore::$lang['No posts selected']);

			if (isset($_POST['delete_posts_comply']))
			{
				if (!isset($_POST['req_confirm']))
					redirect(forum_link(ForumCore::$forum_url['topic'], array(ForumCore::$tid, sef_friendly(ForumCore::$cur_topic['subject']))), ForumCore::$lang['No confirm redirect']);

				($hook = get_hook('mr_confirm_delete_posts_form_submitted')) ? eval($hook) : null;

				// Verify that the post IDs are valid
				$query = array(
					'SELECT'	=> 'COUNT(p.id)',
					'FROM'		=> 'posts AS p',
					'WHERE'		=> 'p.id IN('.implode(',', $posts).') AND p.id!='.ForumCore::$cur_topic['first_post_id'].' AND p.topic_id='.ForumCore::$tid
				);

				($hook = get_hook('mr_confirm_delete_posts_qr_verify_post_ids')) ? eval($hook) : null;
				$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
				if ($forum_db->result($result) != count($posts))
					message(ForumCore::$lang['Bad request']);

				// Delete the posts
				$query = array(
					'DELETE'	=> 'posts',
					'WHERE'		=> 'id IN('.implode(',', $posts).')'
				);

				($hook = get_hook('mr_confirm_delete_posts_qr_delete_posts')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				if (!defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
					require FORUM_ROOT.'include/search_idx.php';

				strip_search_index($posts);

				sync_topic(ForumCore::$tid);
				sync_forum(ForumCore::$fid);

				//$forum_flash->add_info(ForumCore::$lang['Delete posts redirect']);

				($hook = get_hook('mr_confirm_delete_posts_pre_redirect')) ? eval($hook) : null;

				redirect(forum_link(ForumCore::$forum_url['topic'], array(ForumCore::$tid, sef_friendly(ForumCore::$cur_topic['subject']))), ForumCore::$lang['Delete posts redirect']);
			}

			// Setup form
			ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;
			ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['moderate_topic'], array(ForumCore::$fid, ForumCore::$tid));

			ForumCore::$forum_page['hidden_fields'] = array(
				'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token(ForumCore::$forum_page['form_action']).'" />',
				'posts'			=> '<input type="hidden" name="posts" value="'.implode(',', $posts).'" />'
			);

			// Setup breadcrumbs
			ForumCore::$forum_page['crumbs'] = array(
				array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
				array(ForumCore::$cur_forum['forum_name'], forum_link(ForumCore::$forum_url['forum'], array(ForumCore::$fid, sef_friendly(ForumCore::$cur_forum['forum_name'])))),
				array(ForumCore::$cur_topic['subject'], forum_link(ForumCore::$forum_url['topic'], array(ForumCore::$tid, sef_friendly(ForumCore::$cur_topic['subject'])))),
				ForumCore::$lang['Delete posts']
			);

			($hook = get_hook('mr_confirm_delete_posts_output_start')) ? eval($hook) : null;

	?>
		<div class="main-head">
			<h2 class="hn"><span><?php echo ForumCore::$lang['Confirm post delete'] ?></span></h2>
		</div>
		<div class="main-content main-frm">
			<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo ForumCore::$forum_page['form_action'] ?>">
				<div class="hidden">
					<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['hidden_fields'])."\n" ?>
				</div>
	<?php ($hook = get_hook('mr_confirm_delete_posts_pre_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Delete posts'] ?></strong></legend>
	<?php ($hook = get_hook('mr_confirm_delete_posts_pre_confirm_checkbox')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box checkbox">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="req_confirm" value="1" checked="checked" /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Please confirm'] ?></span> <?php echo ForumCore::$lang['Confirm post delete'] ?>.</label>
						</div>
					</div>
	<?php ($hook = get_hook('mr_confirm_delete_posts_pre_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
	<?php ($hook = get_hook('mr_confirm_delete_posts_fieldset_end')) ? eval($hook) : null; ?>
				<div class="frm-buttons">
					<span class="submit primary caution"><input type="submit" name="delete_posts_comply" value="<?php echo ForumCore::$lang['Delete'] ?>" /></span>
					<span class="cancel"><input type="submit" name="cancel" value="<?php echo ForumCore::$lang['Cancel'] ?>" formnovalidate /></span>
				</div>
			</form>
		</div>
	<?php

			$forum_id = ForumCore::$fid;

			($hook = get_hook('mr_confirm_delete_posts_end')) ? eval($hook) : null;

		}
		else if (isset($_POST['split_posts']) || isset($_POST['split_posts_comply']))
		{


			($hook = get_hook('mr_split_posts_form_submitted')) ? eval($hook) : null;

			$posts = isset($_POST['posts']) && !empty($_POST['posts']) ? $_POST['posts'] : array();
			$posts = array_map('intval', (is_array($posts) ? $posts : explode(',', $posts)));

			if (empty($posts))
				message(ForumCore::$lang['No posts selected']);

			if (isset($_POST['split_posts_comply']))
			{
				if (!isset($_POST['req_confirm']))
					redirect(forum_link(ForumCore::$forum_url['topic'], array(ForumCore::$tid, sef_friendly(ForumCore::$cur_topic['subject']))), ForumCore::$lang['No confirm redirect']);

				// Load the post.php language file
				ForumCore::add_lang('post');

				($hook = get_hook('mr_confirm_split_posts_form_submitted')) ? eval($hook) : null;

				// Verify that the post IDs are valid
				$query = array(
					'SELECT'	=> 'COUNT(p.id)',
					'FROM'		=> 'posts AS p',
					'WHERE'		=> 'p.id IN('.implode(',', $posts).') AND p.id!='.ForumCore::$cur_topic['first_post_id'].' AND p.topic_id='.ForumCore::$tid
				);

				($hook = get_hook('mr_confirm_split_posts_qr_verify_post_ids')) ? eval($hook) : null;
				$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
				if ($forum_db->result($result) != count($posts))
					message(ForumCore::$lang['Bad request']);

				$new_subject = isset($_POST['new_subject']) ? forum_trim($_POST['new_subject']) : '';

				if ($new_subject == '')
					message(ForumCore::$lang['No subject']);
				else if (utf8_strlen($new_subject) > FORUM_SUBJECT_MAXIMUM_LENGTH)
					message(sprintf(ForumCore::$lang['Too long subject'], FORUM_SUBJECT_MAXIMUM_LENGTH));

				// Get data from the new first post
				$query = array(
					'SELECT'	=> 'p.id, p.poster, p.posted',
					'FROM'		=> 'posts AS p',
					'WHERE'		=> 'p.id = '.min($posts)
				);

				($hook = get_hook('mr_confirm_split_posts_qr_get_first_post_data')) ? eval($hook) : null;
				$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
				$first_post_data = $forum_db->fetch_assoc($result);

				// Create the new topic
				$query = array(
					'INSERT'	=> 'poster, subject, posted, first_post_id, forum_id',
					'INTO'		=> 'topics',
					'VALUES'	=> '\''.$forum_db->escape($first_post_data['poster']).'\', \''.$forum_db->escape($new_subject).'\', '.$first_post_data['posted'].', '.$first_post_data['id'].', '.ForumCore::$fid
				);

				($hook = get_hook('mr_confirm_split_posts_qr_add_topic')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
				$new_tid = $forum_db->insert_id();

				// Move the posts to the new topic
				$query = array(
					'UPDATE'	=> 'posts',
					'SET'		=> 'topic_id='.$new_tid,
					'WHERE'		=> 'id IN('.implode(',', $posts).')'
				);

				($hook = get_hook('mr_confirm_split_posts_qr_move_posts')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				// Sync last post data for the old topic, the new topic, and the forum itself
				sync_topic($new_tid);
				sync_topic(ForumCore::$tid);
				sync_forum(ForumCore::$fid);

				//$forum_flash->add_info(ForumCore::$lang['Split posts redirect']);

				($hook = get_hook('mr_confirm_split_posts_pre_redirect')) ? eval($hook) : null;

				redirect(forum_link(ForumCore::$forum_url['topic'], array($new_tid, sef_friendly($new_subject))), ForumCore::$lang['Split posts redirect']);
			}

			// Setup form
			ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;
			ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['moderate_topic'], array(ForumCore::$fid, ForumCore::$tid));

			ForumCore::$forum_page['hidden_fields'] = array(
				'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token(ForumCore::$forum_page['form_action']).'" />',
				'posts'			=> '<input type="hidden" name="posts" value="'.implode(',', $posts).'" />'
			);

			// Setup breadcrumbs
			ForumCore::$forum_page['crumbs'] = array(
				array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
				array(ForumCore::$cur_forum['forum_name'], forum_link(ForumCore::$forum_url['forum'], array(ForumCore::$fid, sef_friendly(ForumCore::$cur_forum['forum_name'])))),
				array(ForumCore::$cur_topic['subject'], forum_link(ForumCore::$forum_url['topic'], array(ForumCore::$tid, sef_friendly(ForumCore::$cur_topic['subject'])))),
				ForumCore::$lang['Split posts']
			);

			($hook = get_hook('mr_confirm_split_posts_output_start')) ? eval($hook) : null;

	?>
		<div class="main-head">
			<h2 class="hn"><span><?php echo ForumCore::$lang['Confirm post split'] ?></span></h2>
		</div>
		<div class="main-content main-frm">
			<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo ForumCore::$forum_page['form_action'] ?>">
				<div class="hidden">
					<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['hidden_fields'])."\n" ?>
				</div>
	<?php ($hook = get_hook('mr_confirm_split_posts_pre_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Split posts'] ?></strong></legend>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
	<?php ($hook = get_hook('mr_confirm_split_posts_pre_subject')) ? eval($hook) : null; ?>
						<div class="sf-box text required">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['New subject'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="new_subject" size="<?php echo FORUM_SUBJECT_MAXIMUM_LENGTH ?>" maxlength="<?php echo FORUM_SUBJECT_MAXIMUM_LENGTH ?>" required /></span>
						</div>
	<?php ($hook = get_hook('mr_confirm_split_posts_pre_confirm_checkbox')) ? eval($hook) : null; ?>
						<div class="sf-box checkbox">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="req_confirm" value="1" checked="checked" /></span>
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Please confirm'] ?></span> <?php echo ForumCore::$lang['Confirm topic split'] ?>.</label>
						</div>
					</div>
	<?php ($hook = get_hook('mr_confirm_split_posts_pre_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
	<?php ($hook = get_hook('mr_confirm_split_posts_fieldset_end')) ? eval($hook) : null; ?>
				<div class="frm-buttons">
					<span class="submit primary"><input type="submit" name="split_posts_comply" value="<?php echo ForumCore::$lang['Split'] ?>" /></span>
					<span class="cancel"><input type="submit" name="cancel" value="<?php echo ForumCore::$lang['Cancel'] ?>" formnovalidate /></span>
				</div>
			</form>
		</div>
	<?php

			$forum_id = ForumCore::$fid;

			($hook = get_hook('mr_confirm_split_posts_end')) ? eval($hook) : null;

		}
		else
		{

			// Show the moderate topic view

			// Load the viewtopic.php language file
			ForumCore::add_lang('topic');

			// Used to disable the Split and Delete buttons if there are no replies to this topic
			ForumCore::$forum_page['button_status'] = (ForumCore::$cur_topic['num_replies'] == 0) ? ' disabled="disabled"' : '';


			// Determine the post offset (based on $_GET['p'])
			ForumCore::$forum_page['num_pages'] = ceil((ForumCore::$cur_topic['num_replies'] + 1) / ForumUser::$forum_user['disp_posts']);
			ForumCore::$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > ForumCore::$forum_page['num_pages']) ? 1 : intval($_GET['p']);
			ForumCore::$forum_page['start_from'] = ForumUser::$forum_user['disp_posts'] * (ForumCore::$forum_page['page'] - 1);
			ForumCore::$forum_page['finish_at'] = min((ForumCore::$forum_page['start_from'] + ForumUser::$forum_user['disp_posts']), (ForumCore::$cur_topic['num_replies'] + 1));
			ForumCore::$forum_page['items_info'] = generate_items_info(ForumCore::$lang['Posts'], (ForumCore::$forum_page['start_from'] + 1), (ForumCore::$cur_topic['num_replies'] + 1));

			// Generate paging links
			ForumCore::$forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">'.ForumCore::$lang['Pages'].'</span> '.paginate(ForumCore::$forum_page['num_pages'], ForumCore::$forum_page['page'], ForumCore::$forum_url['moderate_topic'], ForumCore::$lang['Paging separator'], array(ForumCore::$fid, ForumCore::$tid)).'</p>';

			// Navigation links for header and page numbering for title/meta description
			if (ForumCore::$forum_page['page'] < ForumCore::$forum_page['num_pages'])
			{
				ForumCore::$forum_page['nav']['last'] = '<link rel="last" href="'.forum_sublink(ForumCore::$forum_url['moderate_topic'], ForumCore::$forum_url['page'], ForumCore::$forum_page['num_pages'], array(ForumCore::$fid, ForumCore::$tid)).'" title="'.ForumCore::$lang['Page'].' '.ForumCore::$forum_page['num_pages'].'" />';
				ForumCore::$forum_page['nav']['next'] = '<link rel="next" href="'.forum_sublink(ForumCore::$forum_url['moderate_topic'], ForumCore::$forum_url['page'], (ForumCore::$forum_page['page'] + 1), array(ForumCore::$fid, ForumCore::$tid)).'" title="'.ForumCore::$lang['Page'].' '.(ForumCore::$forum_page['page'] + 1).'" />';
			}
			if (ForumCore::$forum_page['page'] > 1)
			{
				ForumCore::$forum_page['nav']['prev'] = '<link rel="prev" href="'.forum_sublink(ForumCore::$forum_url['moderate_topic'], ForumCore::$forum_url['page'], (ForumCore::$forum_page['page'] - 1), array(ForumCore::$fid, ForumCore::$tid)).'" title="'.ForumCore::$lang['Page'].' '.(ForumCore::$forum_page['page'] - 1).'" />';
				ForumCore::$forum_page['nav']['first'] = '<link rel="first" href="'.forum_link(ForumCore::$forum_url['moderate_topic'], array(ForumCore::$fid, ForumCore::$tid)).'" title="'.ForumCore::$lang['Page'].' 1" />';
			}

			if (ForumCore::$forum_config['o_censoring'] == '1')
				ForumCore::$cur_topic['subject'] = censor_words(ForumCore::$cur_topic['subject']);

			// Setup form
			ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['moderate_topic'], array(ForumCore::$fid, ForumCore::$tid));

			// Setup breadcrumbs
			ForumCore::$forum_page['crumbs'] = array(
				array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
				array(ForumCore::$cur_forum['forum_name'], forum_link(ForumCore::$forum_url['forum'], array(ForumCore::$fid, sef_friendly(ForumCore::$cur_forum['forum_name'])))),
				array(ForumCore::$cur_topic['subject'], forum_link(ForumCore::$forum_url['topic'], array(ForumCore::$tid, sef_friendly(ForumCore::$cur_topic['subject'])))),
				ForumCore::$lang['Moderate topic']
			);

			// Setup main heading
			ForumCore::$forum_page['main_title'] = sprintf(ForumCore::$lang['Moderate topic head'], forum_htmlencode(ForumCore::$cur_topic['subject']));

			ForumCore::$forum_page['main_head_options']['select_all'] = '<span '.(empty(ForumCore::$forum_page['main_head_options']) ? ' class="first-item"' : '').'><span class="select-all js_link" data-check-form="mr-post-actions-form">'.ForumCore::$lang['Select all'].'</span></span>';
			ForumCore::$forum_page['main_foot_options']['select_all'] = '<span '.(empty(ForumCore::$forum_page['main_foot_options']) ? ' class="first-item"' : '').'><span class="select-all js_link" data-check-form="mr-post-actions-form">'.ForumCore::$lang['Select all'].'</span></span>';

			if (ForumCore::$forum_page['num_pages'] > 1)
				ForumCore::$forum_page['main_head_pages'] = sprintf(ForumCore::$lang['Page info'], ForumCore::$forum_page['page'], ForumCore::$forum_page['num_pages']);

			($hook = get_hook('mr_post_actions_output_start')) ? eval($hook) : null;

		?>
			<div class="main-head">
		<?php

			if (!empty(ForumCore::$forum_page['main_head_options']))
				echo "\n\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_head_options']).'</p>';

		?>
				<h2 class="hn"><span><?php echo ForumCore::$forum_page['items_info'] ?></span></h2>
			</div>
			<form id="mr-post-actions-form" class="newform" method="post" accept-charset="utf-8" action="<?php echo ForumCore::$forum_page['form_action'] ?>">
			<div class="main-content main-topic">
				<div class="hidden">
					<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(ForumCore::$forum_page['form_action']) ?>" />
				</div>

		<?php

			if (!defined('FORUM_PARSER_LOADED'))
				require FORUM_ROOT.'include/parser.php';

			ForumCore::$forum_page['item_count'] = 0;	// Keep track of post numbers

			// Retrieve the posts (and their respective poster)
			$query = array(
				'SELECT'	=> 'u.title, u.num_posts, g.g_id, g.g_user_title, p.id, p.poster, p.poster_id, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by',
				'FROM'		=> 'posts AS p',
				'JOINS'		=> array(
					array(
						'INNER JOIN'	=> 'users AS u',
						'ON'			=> 'u.id=p.poster_id'
					),
					array(
						'INNER JOIN'	=> 'groups AS g',
						'ON'			=> 'g.g_id=u.group_id'
					)
				),
				'WHERE'		=> 'p.topic_id='.ForumCore::$tid,
				'ORDER BY'	=> 'p.id',
				'LIMIT'		=> ForumCore::$forum_page['start_from'].','.ForumUser::$forum_user['disp_posts']
			);

			($hook = get_hook('mr_post_actions_qr_get_posts')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			while ($cur_post = $forum_db->fetch_assoc($result))
			{
				($hook = get_hook('mr_post_actions_loop_start')) ? eval($hook) : null;

				++ForumCore::$forum_page['item_count'];

				ForumCore::$forum_page['post_ident'] = array();
				ForumCore::$forum_page['message'] = array();
				ForumCore::$forum_page['user_ident'] = array();
				$cur_post['username'] = $cur_post['poster'];

				// Generate the post heading
				ForumCore::$forum_page['post_ident']['num'] = '<span class="post-num">'.forum_number_format(ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']).'</span>';

				if ($cur_post['poster_id'] > 1)
					ForumCore::$forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf((($cur_post['id'] == ForumCore::$cur_topic['first_post_id']) ? ForumCore::$lang['Topic byline'] : ForumCore::$lang['Reply byline']), ((ForumUser::$forum_user['g_view_users'] == '1') ? '<a title="'.sprintf(ForumCore::$lang['Go to profile'], forum_htmlencode($cur_post['username'])).'" href="'.forum_link(ForumCore::$forum_url['user'], $cur_post['poster_id']).'">'.forum_htmlencode($cur_post['username']).'</a>' : '<strong>'.forum_htmlencode($cur_post['username']).'</strong>')).'</span>';
				else
					ForumCore::$forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf((($cur_post['id'] == ForumCore::$cur_topic['first_post_id']) ? ForumCore::$lang['Topic byline'] : ForumCore::$lang['Reply byline']), '<strong>'.forum_htmlencode($cur_post['username']).'</strong>').'</span>';

				ForumCore::$forum_page['post_ident']['link'] = '<span class="post-link"><a class="permalink" rel="bookmark" title="'.ForumCore::$lang['Permalink post'].'" href="'.forum_link(ForumCore::$forum_url['post'], $cur_post['id']).'">'.format_time($cur_post['posted']).'</a></span>';

				if ($cur_post['edited'] != '')
					ForumCore::$forum_page['post_ident']['edited'] = '<span class="post-edit">'.sprintf(ForumCore::$lang['Last edited'], forum_htmlencode($cur_post['edited_by']), format_time($cur_post['edited'])).'</span>';

				($hook = get_hook('mr_row_pre_item_ident_merge')) ? eval($hook) : null;

				// Generate the checkbox field
				if ($cur_post['id'] != ForumCore::$cur_topic['first_post_id'])
					ForumCore::$forum_page['item_select'] = '<p class="item-select"><input type="checkbox" id="fld'.$cur_post['id'].'" name="posts[]" value="'.$cur_post['id'].'" /> <label for="fld'.$cur_post['id'].'">'.ForumCore::$lang['Select post'].' '.forum_number_format(ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']).'</label></p>';

				// Generate author identification
				ForumCore::$forum_page['author_ident']['username'] = '<li class="username">'.(($cur_post['poster_id'] > '1') ? '<a title="'.sprintf(ForumCore::$lang['Go to profile'], forum_htmlencode($cur_post['username'])).'" href="'.forum_link(ForumCore::$forum_url['user'], $cur_post['poster_id']).'">'.forum_htmlencode($cur_post['username']).'</a>' : '<strong>'.forum_htmlencode($cur_post['username']).'</strong>').'</li>';
				ForumCore::$forum_page['author_ident']['usertitle'] = '<li class="usertitle"><span>'.get_title($cur_post).'</span></li>';

				// Give the post some class
				ForumCore::$forum_page['item_status'] = array(
					'post',
					(ForumCore::$forum_page['item_count'] % 2 != 0) ? 'odd' : 'even'
				);

				if (ForumCore::$forum_page['item_count'] == 1)
					ForumCore::$forum_page['item_status']['firstpost'] = 'firstpost';

				if ((ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']) == ForumCore::$forum_page['finish_at'])
					ForumCore::$forum_page['item_status']['lastpost'] = 'lastpost';

				if ($cur_post['id'] == ForumCore::$cur_topic['first_post_id'])
					ForumCore::$forum_page['item_status']['topicpost'] = 'topicpost';
				else
					ForumCore::$forum_page['item_status']['replypost'] = 'replypost';

				// Generate the post title
				if ($cur_post['id'] == ForumCore::$cur_topic['first_post_id'])
					ForumCore::$forum_page['item_subject'] = sprintf(ForumCore::$lang['Topic title'], ForumCore::$cur_topic['subject']);
				else
					ForumCore::$forum_page['item_subject'] = sprintf(ForumCore::$lang['Reply title'], ForumCore::$cur_topic['subject']);

				ForumCore::$forum_page['item_subject'] = forum_htmlencode(ForumCore::$forum_page['item_subject']);

				// Perform the main parsing of the message (BBCode, smilies, censor words etc)
				ForumCore::$forum_page['message']['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

				($hook = get_hook('mr_post_actions_row_pre_display')) ? eval($hook) : null;

		?>
					<div class="<?php echo implode(' ', ForumCore::$forum_page['item_status']) ?>">
						<div id="p<?php echo $cur_post['id'] ?>" class="posthead">
							<h3 class="hn post-ident"><?php echo implode(' ', ForumCore::$forum_page['post_ident']) ?></h3>
		<?php ($hook = get_hook('mr_post_actions_pre_item_select')) ? eval($hook) : null; ?>
		<?php if (isset(ForumCore::$forum_page['item_select'])) echo "\t\t\t\t".ForumCore::$forum_page['item_select']."\n" ?>
		<?php ($hook = get_hook('mr_post_actions_new_post_head_option')) ? eval($hook) : null; ?>
						</div>
						<div class="postbody">
							<div class="post-author">
								<ul class="author-ident">
									<?php echo implode("\n\t\t\t\t\t\t", ForumCore::$forum_page['author_ident'])."\n" ?>
								</ul>
		<?php ($hook = get_hook('mr_post_actions_new_user_ident_data')) ? eval($hook) : null; ?>
							</div>
							<div class="post-entry">
								<h4 class="entry-title"><?php echo ForumCore::$forum_page['item_subject'] ?></h4>
								<div class="entry-content">
									<?php echo implode("\n\t\t\t\t\t\t\t", ForumCore::$forum_page['message'])."\n" ?>
								</div>
		<?php ($hook = get_hook('mr_post_actions_new_post_entry_data')) ? eval($hook) : null; ?>
							</div>
						</div>
					</div>
		<?php

			}

		?>
			</div>
		<?php

		ForumCore::$forum_page['mod_options'] = array(
			'del_posts'		=> '<span class="submit first-item"><input type="submit" name="delete_posts" value="'.ForumCore::$lang['Delete posts'].'" /></span>',
			'split_posts'	=> '<span class="submit"><input type="submit" name="split_posts" value="'.ForumCore::$lang['Split posts'].'" /></span>',
			'del_topic'		=> '<span><a href="'.forum_link(ForumCore::$forum_url['delete'], ForumCore::$cur_topic['first_post_id']).'">'.ForumCore::$lang['Delete whole topic'].'</a></span>'
		);

		($hook = get_hook('mr_post_actions_pre_mod_options')) ? eval($hook) : null;

		?>

			<div class="main-options mod-options gen-content">
				<p class="options"><?php echo implode(' ', ForumCore::$forum_page['mod_options']) ?></p>
			</div>
			</form>
			<div class="main-foot">
		<?php

			if (!empty(ForumCore::$forum_page['main_foot_options']))
				echo "\n\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_foot_options']).'</p>';

		?>
				<h2 class="hn"><span><?php echo ForumCore::$forum_page['items_info'] ?></span></h2>
			</div>
		<?php

			$forum_id = ForumCore::$fid;

			// Init JS helper for select-all
			//$forum_loader->add_js('PUNBB.common.addDOMReadyEvent(PUNBB.common.initToggleCheckboxes);', array('type' => 'inline'));

			($hook = get_hook('mr_post_actions_end')) ? eval($hook) : null;

		}
	});
}


// Move one or more topics
if (isset($_REQUEST['move_topics']) || isset($_POST['move_topics_to']))
{
	($hook = get_hook('mr_move_topics_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'dialogue');
	require FORUM_ROOT.'header.php';

	// SHORTCODE [pun_content]
	add_shortcode('pun_content', function ()
	{
		$forum_db = new DBLayer;

		if (isset($_POST['move_topics_to']))
		{
			($hook = get_hook('mr_confirm_move_topics_form_submitted')) ? eval($hook) : null;

			$topics = isset($_POST['topics']) && !empty($_POST['topics']) ? explode(',', $_POST['topics']) : array();
			$topics = array_map('intval', $topics);

			$move_to_forum = isset($_POST['move_to_forum']) ? intval($_POST['move_to_forum']) : 0;
			if (empty($topics) || $move_to_forum < 1)
				message(ForumCore::$lang['Bad request']);

			// Fetch the forum name for the forum we're moving to
			$query = array(
				'SELECT'	=> 'f.forum_name',
				'FROM'		=> 'forums AS f',
				'WHERE'		=> 'f.id='.$move_to_forum
			);

			($hook = get_hook('mr_confirm_move_topics_qr_get_move_to_forum_name')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			$move_to_forum_name = $forum_db->result($result);

			if (!$move_to_forum_name)
				message(ForumCore::$lang['Bad request']);

			// Verify that the topic IDs are valid
			$query = array(
				'SELECT'	=> 'COUNT(t.id)',
				'FROM'		=> 'topics AS t',
				'WHERE'		=> 't.id IN('.implode(',', $topics).') AND t.forum_id='.ForumCore::$fid
			);

			($hook = get_hook('mr_confirm_move_topics_qr_verify_topic_ids')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			if ($forum_db->result($result) != count($topics))
				message(ForumCore::$lang['Bad request']);

			// Delete any redirect topics if there are any (only if we moved/copied the topic back to where it where it was once moved from)
			$query = array(
				'DELETE'	=> 'topics',
				'WHERE'		=> 'forum_id='.$move_to_forum.' AND moved_to IN('.implode(',', $topics).')'
			);

			($hook = get_hook('mr_confirm_move_topics_qr_delete_redirect_topics')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			// Move the topic(s)
			$query = array(
				'UPDATE'	=> 'topics',
				'SET'		=> 'forum_id='.$move_to_forum,
				'WHERE'		=> 'id IN('.implode(',', $topics).')'
			);

			($hook = get_hook('mr_confirm_move_topics_qr_move_topics')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			// Should we create redirect topics?
			if (isset($_POST['with_redirect']))
			{
				foreach ($topics as ForumCore::$cur_topic)
				{
					// Fetch info for the redirect topic
					$query = array(
						'SELECT'	=> 't.poster, t.subject, t.posted, t.last_post',
						'FROM'		=> 'topics AS t',
						'WHERE'		=> 't.id='.ForumCore::$cur_topic
					);

					($hook = get_hook('mr_confirm_move_topics_qr_get_redirect_topic_data')) ? eval($hook) : null;
					$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
					$moved_to = $forum_db->fetch_assoc($result);

					// Create the redirect topic
					$query = array(
						'INSERT'	=> 'poster, subject, posted, last_post, moved_to, forum_id',
						'INTO'		=> 'topics',
						'VALUES'	=> '\''.$forum_db->escape($moved_to['poster']).'\', \''.$forum_db->escape($moved_to['subject']).'\', '.$moved_to['posted'].', '.$moved_to['last_post'].', '.ForumCore::$cur_topic.', '.ForumCore::$fid
					);

					($hook = get_hook('mr_confirm_move_topics_qr_add_redirect_topic')) ? eval($hook) : null;
					$forum_db->query_build($query) or error(__FILE__, __LINE__);
				}
			}

			sync_forum(ForumCore::$fid);			// Synchronize the forum FROM which the topic was moved
			sync_forum($move_to_forum);	// Synchronize the forum TO which the topic was moved

			ForumCore::$forum_page['redirect_msg'] = (count($topics) > 1) ? ForumCore::$lang['Move topics redirect'] : ForumCore::$lang['Move topic redirect'];

			//$forum_flash->add_info(ForumCore::$forum_page['redirect_msg']);

			($hook = get_hook('mr_confirm_move_topics_pre_redirect')) ? eval($hook) : null;

			redirect(forum_link(ForumCore::$forum_url['forum'], array($move_to_forum, sef_friendly($move_to_forum_name))), ForumCore::$forum_page['redirect_msg']);
		}

		if (isset($_POST['move_topics']))
		{
			$topics = isset($_POST['topics']) && is_array($_POST['topics']) ? $_POST['topics'] : array();
			$topics = array_map('intval', $topics);

			if (empty($topics))
				message(ForumCore::$lang['No topics selected']);

			if (count($topics) == 1)
			{
				$topics = $topics[0];
				$action = 'single';
			}
			else
				$action = 'multiple';
		}
		else
		{
			$topics = intval($_GET['move_topics']);
			if ($topics < 1)
				message(ForumCore::$lang['Bad request']);

			$action = 'single';
		}
		if ($action == 'single')
		{
			// Fetch the topic subject
			$query = array(
				'SELECT'	=> 't.subject',
				'FROM'		=> 'topics AS t',
				'WHERE'		=> 't.id='.$topics
			);

			($hook = get_hook('mr_move_topics_qr_get_topic_to_move_subject')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			$subject = $forum_db->result($result);

			if (!$subject)
			{
				message(ForumCore::$lang['Bad request']);
			}
		}

		// Get forums we can move the post into
		$query = array(
			'SELECT'	=> 'c.id AS cid, c.cat_name, f.id AS fid, f.forum_name',
			'FROM'		=> 'categories AS c',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'forums AS f',
					'ON'			=> 'c.id=f.cat_id'
				),
				array(
					'LEFT JOIN'		=> 'forum_perms AS fp',
					'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.ForumUser::$forum_user['g_id'].')'
				)
			),
			'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.redirect_url IS NULL AND f.id!='.ForumCore::$fid,
			'ORDER BY'	=> 'c.disp_position, c.id, f.disp_position'
		);

		($hook = get_hook('mr_move_topics_qr_get_target_forums')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$forum_list = array();
		while ($cur_sel_forum = $forum_db->fetch_assoc($result))
		{
			$forum_list[] = $cur_sel_forum;
		}

		if (empty($forum_list))
		{
			message(ForumCore::$lang['Nowhere to move']);
		}


		// Setup form
		ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;
		ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['moderate_forum'], ForumCore::$fid);

		ForumCore::$forum_page['hidden_fields'] = array(
			'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token(ForumCore::$forum_page['form_action']).'" />',
			'topics'		=> '<input type="hidden" name="topics" value="'.($action == 'single' ? $topics : implode(',', $topics)).'" />'
		);

		// Setup breadcrumbs
		ForumCore::$forum_page['crumbs'][] = array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index']));
		ForumCore::$forum_page['crumbs'][] = array(ForumCore::$cur_forum['forum_name'], forum_link(ForumCore::$forum_url['forum'], array(ForumCore::$fid, sef_friendly(ForumCore::$cur_forum['forum_name']))));
		if ($action == 'single')
			ForumCore::$forum_page['crumbs'][] = array($subject, forum_link(ForumCore::$forum_url['topic'], array($topics, sef_friendly($subject))));
		else
			ForumCore::$forum_page['crumbs'][] = array(ForumCore::$lang['Moderate forum'], forum_link(ForumCore::$forum_url['moderate_forum'], ForumCore::$fid));
		ForumCore::$forum_page['crumbs'][] = ($action == 'single') ? ForumCore::$lang['Move topic'] : ForumCore::$lang['Move topics'];

		//Setup main heading
		ForumCore::$forum_page['main_title'] = end(ForumCore::$forum_page['crumbs']).' '.ForumCore::$lang['To new forum'];


		($hook = get_hook('mr_move_topics_output_start')) ? eval($hook) : null;

	?>
		<div class="main-head">
			<h2 class="hn"><span><?php echo end(ForumCore::$forum_page['crumbs']).' '.ForumCore::$lang['To new forum'] ?></span></h2>
		</div>
		<div class="main-content main-frm">
			<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo ForumCore::$forum_page['form_action'] ?>">
				<div class="hidden">
					<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['hidden_fields'])."\n" ?>
				</div>
	<?php ($hook = get_hook('mr_move_topics_pre_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Move topic'] ?></strong></legend>
	<?php ($hook = get_hook('mr_move_topics_pre_move_to_forum')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box select">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Move to'] ?></span></label><br />
							<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="move_to_forum">
	<?php

		ForumCore::$forum_page['cur_category'] = 0;
		foreach ($forum_list as ForumCore::$cur_forum)
		{
			($hook = get_hook('mr_move_topics_forum_loop_start')) ? eval($hook) : null;

			if (ForumCore::$cur_forum['cid'] != ForumCore::$forum_page['cur_category'])	// A new category since last iteration?
			{
				if (ForumCore::$forum_page['cur_category'])
					echo "\t\t\t\t".'</optgroup>'."\n";

				echo "\t\t\t\t".'<optgroup label="'.forum_htmlencode(ForumCore::$cur_forum['cat_name']).'">'."\n";
				ForumCore::$forum_page['cur_category'] = ForumCore::$cur_forum['cid'];
			}

			if (ForumCore::$cur_forum['fid'] != ForumCore::$fid)
				echo "\t\t\t\t".'<option value="'.ForumCore::$cur_forum['fid'].'">'.forum_htmlencode(ForumCore::$cur_forum['forum_name']).'</option>'."\n";

			($hook = get_hook('mr_move_topics_forum_loop_end')) ? eval($hook) : null;
		}

	?>
							</optgroup>
							</select></span>
						</div>
					</div>
	<?php ($hook = get_hook('mr_move_topics_pre_redirect_checkbox')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box checkbox">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo (++ForumCore::$forum_page['fld_count']) ?>" name="with_redirect" value="1"<?php if ($action == 'single') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ($action == 'single') ? ForumCore::$lang['Leave redirect'] : ForumCore::$lang['Leave redirects'] ?></label>
						</div>
					</div>
	<?php ($hook = get_hook('mr_move_topics_pre_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
	<?php ($hook = get_hook('mr_move_topics_fieldset_end')) ? eval($hook) : null; ?>
				<div class="frm-buttons">
					<span class="submit primary"><input type="submit" name="move_topics_to" value="<?php echo ForumCore::$lang['Move'] ?>" /></span>
					<span class="cancel"><input type="submit" name="cancel" value="<?php echo ForumCore::$lang['Cancel'] ?>" formnovalidate /></span>
				</div>
			</form>
		</div>
	<?php

		$forum_id = ForumCore::$fid;

		($hook = get_hook('mr_move_topics_end')) ? eval($hook) : null;

	});
}


// Merge topics
else if (isset($_POST['merge_topics']) || isset($_POST['merge_topics_comply']))
{
	($hook = get_hook('mr_merge_topics_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'dialogue');
	require FORUM_ROOT.'header.php';

	// SHORTCODE [pun_content]
	add_shortcode('pun_content', function ()
	{
		$forum_db = new DBLayer;

		$topics = isset($_POST['topics']) && !empty($_POST['topics']) ? $_POST['topics'] : array();
		$topics = array_map('intval', (is_array($topics) ? $topics : explode(',', $topics)));

		if (empty($topics))
			message(ForumCore::$lang['No topics selected']);

		if (count($topics) == 1)
			message(ForumCore::$lang['Merge error']);

		if (isset($_POST['merge_topics_comply']))
		{
			($hook = get_hook('mr_confirm_merge_topics_form_submitted')) ? eval($hook) : null;

			// Verify that the topic IDs are valid
			$query = array(
				'SELECT'	=> 'COUNT(t.id), MIN(t.id)',
				'FROM'		=> 'topics AS t',
				'WHERE'		=> 't.id IN('.implode(',', $topics).') AND t.moved_to IS NULL AND t.forum_id='.ForumCore::$fid
			);

			($hook = get_hook('mr_confirm_merge_topics_qr_verify_topic_ids')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			list($num_topics, $merge_to_tid) = $forum_db->fetch_row($result);
			if ($num_topics != count($topics))
				message(ForumCore::$lang['Bad request']);

			// Make any redirect topics point to our new, merged topic
			$query = array(
				'UPDATE'	=> 'topics',
				'SET'		=> 'moved_to='.$merge_to_tid,
				'WHERE'		=> 'moved_to IN('.implode(',', $topics).')'
			);

			// Should we create redirect topics?
			if (isset($_POST['with_redirect']))
				$query['WHERE'] .= ' OR (id IN('.implode(',', $topics).') AND id != '.$merge_to_tid.')';

			($hook = get_hook('mr_confirm_merge_topics_qr_fix_redirect_topics')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			// Merge the posts into the topic
			$query = array(
				'UPDATE'	=> 'posts',
				'SET'		=> 'topic_id='.$merge_to_tid,
				'WHERE'		=> 'topic_id IN('.implode(',', $topics).')'
			);

			($hook = get_hook('mr_confirm_merge_topics_qr_merge_posts')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			// Delete any subscriptions
			$query = array(
				'DELETE'	=> 'subscriptions',
				'WHERE'		=> 'topic_id IN('.implode(',', $topics).') AND topic_id != '.$merge_to_tid
			);

			($hook = get_hook('mr_confirm_merge_topics_qr_delete_subscriptions')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			if (!isset($_POST['with_redirect']))
			{
				// Delete the topics that have been merged
				$query = array(
					'DELETE'	=> 'topics',
					'WHERE'		=> 'id IN('.implode(',', $topics).') AND id != '.$merge_to_tid
				);

				($hook = get_hook('mr_confirm_merge_topics_qr_delete_merged_topics')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}

			// Synchronize the topic we merged to and the forum where the topics were merged
			sync_topic($merge_to_tid);
			sync_forum(ForumCore::$fid);

			//$forum_flash->add_info(ForumCore::$lang['Merge topics redirect']);

			($hook = get_hook('mr_confirm_merge_topics_pre_redirect')) ? eval($hook) : null;

			redirect(forum_link(ForumCore::$forum_url['forum'], array(ForumCore::$fid, sef_friendly(ForumCore::$cur_forum['forum_name']))), ForumCore::$lang['Merge topics redirect']);
		}

		// Setup form
		ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;
		ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['moderate_forum'], ForumCore::$fid);

		ForumCore::$forum_page['hidden_fields'] = array(
			'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token(ForumCore::$forum_page['form_action']).'" />',
			'topics'		=> '<input type="hidden" name="topics" value="'.implode(',', $topics).'" />'
		);

		// Setup breadcrumbs
		ForumCore::$forum_page['crumbs'] = array(
			array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
			array(ForumCore::$cur_forum['forum_name'], forum_link(ForumCore::$forum_url['forum'], array(ForumCore::$fid, sef_friendly(ForumCore::$cur_forum['forum_name'])))),
			array(ForumCore::$lang['Moderate forum'], forum_link(ForumCore::$forum_url['moderate_forum'], ForumCore::$fid)),
			ForumCore::$lang['Merge topics']
		);

		($hook = get_hook('mr_merge_topics_output_start')) ? eval($hook) : null;

	?>
		<div class="main-head">
			<h2 class="hn"><span><?php echo ForumCore::$lang['Confirm topic merge'] ?></span></h2>
		</div>
		<div class="main-content main-frm">
			<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo ForumCore::$forum_page['form_action'] ?>">
				<div class="hidden">
					<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['hidden_fields'])."\n" ?>
				</div>
	<?php ($hook = get_hook('mr_merge_topics_pre_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Merge topics'] ?></strong></legend>
	<?php ($hook = get_hook('mr_merge_topics_pre_redirect_checkbox')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box checkbox">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo (++ForumCore::$forum_page['fld_count']) ?>" name="with_redirect" value="1" /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Leave merge redirects'] ?></label>
						</div>
					</div>
	<?php ($hook = get_hook('mr_merge_topics_pre_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
	<?php ($hook = get_hook('mr_merge_topics_fieldset_end')) ? eval($hook) : null; ?>
				<div class="frm-buttons">
					<span class="submit primary"><input type="submit" name="merge_topics_comply" value="<?php echo ForumCore::$lang['Merge'] ?>" /></span>
					<span class="cancel"><input type="submit" name="cancel" value="<?php echo ForumCore::$lang['Cancel'] ?>" formnovalidate /></span>
				</div>
			</form>
		</div>
	<?php

		$forum_id = ForumCore::$fid;

		($hook = get_hook('mr_merge_topics_end')) ? eval($hook) : null;

	});
}


// Delete one or more topics
else if (isset($_REQUEST['delete_topics']) || isset($_POST['delete_topics_comply']))
{
	($hook = get_hook('mr_delete_topics_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'dialogue');
	require FORUM_ROOT.'header.php';

	// SHORTCODE [pun_content]
	add_shortcode('pun_content', function ()
	{
		$forum_db = new DBLayer;

		$topics = isset($_POST['topics']) && !empty($_POST['topics']) ? $_POST['topics'] : array();
		$topics = array_map('intval', (is_array($topics) ? $topics : explode(',', $topics)));

		if (empty($topics))
			message(ForumCore::$lang['No topics selected']);

		$multi = count($topics) > 1;
		if (isset($_POST['delete_topics_comply']))
		{
			if (!isset($_POST['req_confirm']))
				redirect(forum_link(ForumCore::$forum_url['forum'], array(ForumCore::$fid, sef_friendly(ForumCore::$cur_forum['forum_name']))), ForumCore::$lang['Cancel redirect']);

			($hook = get_hook('mr_confirm_delete_topics_form_submitted')) ? eval($hook) : null;

			// Verify that the topic IDs are valid
			$query = array(
				'SELECT'	=> 'COUNT(t.id)',
				'FROM'		=> 'topics AS t',
				'WHERE'		=> 't.id IN('.implode(',', $topics).') AND t.forum_id='.ForumCore::$fid
			);

			($hook = get_hook('mr_confirm_delete_topics_qr_verify_topic_ids')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			if ($forum_db->result($result) != count($topics))
				message(ForumCore::$lang['Bad request']);

			// Create an array of forum IDs that need to be synced
			$forum_ids = array(ForumCore::$fid);
			$query = array(
				'SELECT'	=> 't.forum_id',
				'FROM'		=> 'topics AS t',
				'WHERE'		=> 't.moved_to IN('.implode(',', $topics).')'
			);

			($hook = get_hook('mr_confirm_delete_topics_qr_get_forums_to_sync')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			while ($row = $forum_db->fetch_row($result))
				$forum_ids[] = $row[0];

			// Delete the topics and any redirect topics
			$query = array(
				'DELETE'	=> 'topics',
				'WHERE'		=> 'id IN('.implode(',', $topics).') OR moved_to IN('.implode(',', $topics).')'
			);

			($hook = get_hook('mr_confirm_delete_topics_qr_delete_topics')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			// Delete any subscriptions
			$query = array(
				'DELETE'	=> 'subscriptions',
				'WHERE'		=> 'topic_id IN('.implode(',', $topics).')'
			);

			($hook = get_hook('mr_confirm_delete_topics_qr_delete_subscriptions')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			// Create a list of the post ID's in the deleted topic and strip the search index
			$query = array(
				'SELECT'	=> 'p.id',
				'FROM'		=> 'posts AS p',
				'WHERE'		=> 'p.topic_id IN('.implode(',', $topics).')'
			);

			($hook = get_hook('mr_confirm_delete_topics_qr_get_deleted_posts')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			$post_ids = array();
			while ($row = $forum_db->fetch_row($result))
				$post_ids[] = $row[0];

			// Strip the search index provided we're not just deleting redirect topics
			if (!empty($post_ids))
			{
				if (!defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
					require FORUM_ROOT.'include/search_idx.php';

				strip_search_index($post_ids);
			}

			// Delete posts
			$query = array(
				'DELETE'	=> 'posts',
				'WHERE'		=> 'topic_id IN('.implode(',', $topics).')'
			);

			($hook = get_hook('mr_confirm_delete_topics_qr_delete_topic_posts')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			foreach ($forum_ids as $cur_forum_id)
				sync_forum($cur_forum_id);

			//$forum_flash->add_info($multi ? ForumCore::$lang['Delete topics redirect'] : ForumCore::$lang['Delete topic redirect']);

			($hook = get_hook('mr_confirm_delete_topics_pre_redirect')) ? eval($hook) : null;

			redirect(forum_link(ForumCore::$forum_url['forum'], array(ForumCore::$fid, sef_friendly(ForumCore::$cur_forum['forum_name']))), $multi ? ForumCore::$lang['Delete topics redirect'] : ForumCore::$lang['Delete topic redirect']);
		}


		// Setup form
		ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] =0;
		ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['moderate_forum'], ForumCore::$fid);

		ForumCore::$forum_page['hidden_fields'] = array(
			'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token(ForumCore::$forum_page['form_action']).'" />',
			'topics'		=> '<input type="hidden" name="topics" value="'.implode(',', $topics).'" />'
		);

		// Setup breadcrumbs
		ForumCore::$forum_page['crumbs'] = array(
			array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
			array(ForumCore::$cur_forum['forum_name'], forum_link(ForumCore::$forum_url['forum'], array(ForumCore::$fid, sef_friendly(ForumCore::$cur_forum['forum_name'])))),
			array(ForumCore::$lang['Moderate forum'], forum_link(ForumCore::$forum_url['moderate_forum'], ForumCore::$fid)),
			$multi ? ForumCore::$lang['Delete topics'] : ForumCore::$lang['Delete topic']
		);

		($hook = get_hook('mr_delete_topics_output_start')) ? eval($hook) : null;

	?>
		<div class="main-head">
			<h2 class="hn"><span><?php echo ForumCore::$lang['Confirm topic delete'] ?></span></h2>
		</div>
		<div class="main-content main-frm">
			<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo ForumCore::$forum_page['form_action'] ?>">
				<div class="hidden">
					<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['hidden_fields'])."\n" ?>
				</div>
	<?php ($hook = get_hook('mr_delete_topics_pre_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo $multi ? ForumCore::$lang['Delete topics'] : ForumCore::$lang['Delete topics'] ?></strong></legend>
	<?php ($hook = get_hook('mr_delete_topics_pre_confirm_checkbox')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box checkbox">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="req_confirm" value="1" checked="checked" /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Please confirm'] ?></span> <?php echo $multi ? ForumCore::$lang['Delete topics comply'] : ForumCore::$lang['Delete topic comply'] ?></label>
						</div>
					</div>
	<?php ($hook = get_hook('mr_delete_topics_pre_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
	<?php ($hook = get_hook('mr_delete_topics_fieldset_end')) ? eval($hook) : null; ?>
				<div class="frm-buttons">
					<span class="submit primary caution"><input type="submit" name="delete_topics_comply" value="<?php echo ForumCore::$lang['Delete'] ?>" /></span>
					<span class="cancel"><input type="submit" name="cancel" value="<?php echo ForumCore::$lang['Cancel'] ?>" formnovalidate /></span>
				</div>
			</form>
		</div>
	<?php

		$forum_id = ForumCore::$fid;

		($hook = get_hook('mr_delete_topics_end')) ? eval($hook) : null;

	});

}


// Open or close one or more topics
else if (isset($_REQUEST['open']) || isset($_REQUEST['close']))
{
	$action = (isset($_REQUEST['open'])) ? 0 : 1;

	($hook = get_hook('mr_open_close_topic_selected')) ? eval($hook) : null;

	// There could be an array of topic ID's in $_POST
	if (isset($_POST['open']) || isset($_POST['close']))
	{
		$topics = isset($_POST['topics']) && is_array($_POST['topics']) ? $_POST['topics'] : array();
		$topics = array_map('intval', $topics);

		if (empty($topics))
			message(ForumCore::$lang['No topics selected']);

		$query = array(
			'UPDATE'	=> 'topics',
			'SET'		=> 'closed='.$action,
			'WHERE'		=> 'id IN('.implode(',', $topics).') AND forum_id='.ForumCore::$fid
		);

		($hook = get_hook('mr_open_close_multi_topics_qr_open_close_topics')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		if (count($topics) == 1)
			ForumCore::$forum_page['redirect_msg'] = ($action) ? ForumCore::$lang['Close topic redirect'] : ForumCore::$lang['Open topic redirect'];
		else
			ForumCore::$forum_page['redirect_msg'] = ($action) ? ForumCore::$lang['Close topics redirect'] : ForumCore::$lang['Open topics redirect'];

		//$forum_flash->add_info(ForumCore::$forum_page['redirect_msg']);

		($hook = get_hook('mr_open_close_multi_topics_pre_redirect')) ? eval($hook) : null;

		redirect(forum_link(ForumCore::$forum_url['moderate_forum'], ForumCore::$fid), ForumCore::$forum_page['redirect_msg']);
	}
	// Or just one in $_GET
	else
	{
		$topic_id = ($action) ? intval($_GET['close']) : intval($_GET['open']);
		if ($topic_id < 1)
			message(ForumCore::$lang['Bad request']);

		// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
		// If it's in GET, we need to make sure it's valid.
		if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token(($action ? 'close' : 'open').$topic_id)))
			csrf_confirm_form();

		// Get the topic subject
		$query = array(
			'SELECT'	=> 't.subject',
			'FROM'		=> 'topics AS t',
			'WHERE'		=> 't.id='.$topic_id.' AND forum_id='.ForumCore::$fid
		);

		($hook = get_hook('mr_open_close_single_topic_qr_get_subject')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$subject = $forum_db->result($result);

		if (!$subject)
		{
			message(ForumCore::$lang['Bad request']);
		}

		$query = array(
			'UPDATE'	=> 'topics',
			'SET'		=> 'closed='.$action,
			'WHERE'		=> 'id='.$topic_id.' AND forum_id='.ForumCore::$fid
		);

		($hook = get_hook('mr_open_close_single_topic_qr_open_close_topic')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		ForumCore::$forum_page['redirect_msg'] = ($action) ? ForumCore::$lang['Close topic redirect'] : ForumCore::$lang['Open topic redirect'];

		//$forum_flash->add_info(ForumCore::$forum_page['redirect_msg']);

		($hook = get_hook('mr_open_close_single_topic_pre_redirect')) ? eval($hook) : null;

		redirect(forum_link(ForumCore::$forum_url['topic'], array($topic_id, sef_friendly($subject))), ForumCore::$forum_page['redirect_msg']);
	}
}


// Stick a topic
else if (isset($_GET['stick']))
{
	$stick = intval($_GET['stick']);
	if ($stick < 1)
		message(ForumCore::$lang['Bad request']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('stick'.$stick)))
		csrf_confirm_form();

	($hook = get_hook('mr_stick_topic_selected')) ? eval($hook) : null;

	// Get the topic subject
	$query = array(
		'SELECT'	=> 't.subject',
		'FROM'		=> 'topics AS t',
		'WHERE'		=> 't.id='.$stick.' AND forum_id='.ForumCore::$fid
	);

	($hook = get_hook('mr_stick_topic_qr_get_subject')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$subject = $forum_db->result($result);

	if (!$subject)
	{
		message(ForumCore::$lang['Bad request']);
	}

	$query = array(
		'UPDATE'	=> 'topics',
		'SET'		=> 'sticky=1',
		'WHERE'		=> 'id='.$stick.' AND forum_id='.ForumCore::$fid
	);

	($hook = get_hook('mr_stick_topic_qr_stick_topic')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	//$forum_flash->add_info(ForumCore::$lang['Stick topic redirect']);

	($hook = get_hook('mr_stick_topic_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link(ForumCore::$forum_url['topic'], array($stick, sef_friendly($subject))), ForumCore::$lang['Stick topic redirect']);
}


// Unstick a topic
else if (isset($_GET['unstick']))
{
	$unstick = intval($_GET['unstick']);
	if ($unstick < 1)
		message(ForumCore::$lang['Bad request']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('unstick'.$unstick)))
		csrf_confirm_form();

	($hook = get_hook('mr_unstick_topic_selected')) ? eval($hook) : null;

	// Get the topic subject
	$query = array(
		'SELECT'	=> 't.subject',
		'FROM'		=> 'topics AS t',
		'WHERE'		=> 't.id='.$unstick.' AND forum_id='.ForumCore::$fid
	);

	($hook = get_hook('mr_unstick_topic_qr_get_subject')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$subject = $forum_db->result($result);

	if (!$subject)
	{
		message(ForumCore::$lang['Bad request']);
	}

	$query = array(
		'UPDATE'	=> 'topics',
		'SET'		=> 'sticky=0',
		'WHERE'		=> 'id='.$unstick.' AND forum_id='.ForumCore::$fid
	);

	($hook = get_hook('mr_unstick_topic_qr_unstick_topic')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	//$forum_flash->add_info(ForumCore::$lang['Unstick topic redirect']);

	($hook = get_hook('mr_unstick_topic_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link(ForumCore::$forum_url['topic'], array($unstick, sef_friendly($subject))), ForumCore::$lang['Unstick topic redirect']);
}
else
{

	($hook = get_hook('mr_topic_actions_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'modforum');
	require FORUM_ROOT.'header.php';

	// SHORTCODE [pun_content]
	add_shortcode('pun_content', function ()
	{
		$forum_db = new DBLayer;

		($hook = get_hook('mr_new_action')) ? eval($hook) : null;

		// No specific forum moderation action was specified in the query string, so we'll display the moderate forum view

		// If forum is empty
		if (ForumCore::$cur_forum['num_topics'] == 0)
			message(ForumCore::$lang['Bad request']);

		// Load the viewforum.php language file
		ForumCore::add_lang('forum');

		// Determine the topic offset (based on $_GET['p'])
		ForumCore::$forum_page['num_pages'] = ceil(ForumCore::$cur_forum['num_topics'] / ForumUser::$forum_user['disp_topics']);

		ForumCore::$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > ForumCore::$forum_page['num_pages']) ? 1 : $_GET['p'];
		ForumCore::$forum_page['start_from'] = ForumUser::$forum_user['disp_topics'] * (ForumCore::$forum_page['page'] - 1);
		ForumCore::$forum_page['finish_at'] = min((ForumCore::$forum_page['start_from'] + ForumUser::$forum_user['disp_topics']), (ForumCore::$cur_forum['num_topics']));
		ForumCore::$forum_page['items_info'] = generate_items_info(ForumCore::$lang['Topics'], (ForumCore::$forum_page['start_from'] + 1), ForumCore::$cur_forum['num_topics']);

		// Select topics
		$query = array(
			'SELECT'	=> 't.id, t.poster, t.subject, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to',
			'FROM'		=> 'topics AS t',
			'WHERE'		=> 'forum_id='.ForumCore::$fid,
			'ORDER BY'	=> 't.sticky DESC, '.((ForumCore::$cur_forum['sort_by'] == '1') ? 't.posted' : 't.last_post').' DESC',
			'LIMIT'		=>	ForumCore::$forum_page['start_from'].', '.ForumUser::$forum_user['disp_topics']
		);

		// With "has posted" indication
		if (!ForumUser::$forum_user['is_guest'] && ForumCore::$forum_config['o_show_dot'] == '1')
		{
			$query['SELECT'] .= ', p.poster_id AS has_posted';
			$query['JOINS'][]	= array(
				'LEFT JOIN'		=> 'posts AS p',
				'ON'			=> '(p.poster_id='.ForumUser::$forum_user['id'].' AND p.topic_id=t.id)'
			);

			// Must have same columns as in prev SELECT
			$query['GROUP BY'] = 't.id, t.poster, t.subject, t.posted, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to, p.poster_id';

			($hook = get_hook('mr_qr_get_has_posted')) ? eval($hook) : null;
		}

		($hook = get_hook('mr_qr_get_topics')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Generate paging links
		ForumCore::$forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">'.ForumCore::$lang['Pages'].'</span> '.paginate(ForumCore::$forum_page['num_pages'], ForumCore::$forum_page['page'], ForumCore::$forum_url['moderate_forum'], ForumCore::$lang['Paging separator'], ForumCore::$fid).'</p>';

		// Navigation links for header and page numbering for title/meta description
		if (ForumCore::$forum_page['page'] < ForumCore::$forum_page['num_pages'])
		{
			ForumCore::$forum_page['nav']['last'] = '<link rel="last" href="'.forum_sublink(ForumCore::$forum_url['moderate_forum'], ForumCore::$forum_url['page'], ForumCore::$forum_page['num_pages'], ForumCore::$fid).'" title="'.ForumCore::$lang['Page'].' '.ForumCore::$forum_page['num_pages'].'" />';
			ForumCore::$forum_page['nav']['next'] = '<link rel="next" href="'.forum_sublink(ForumCore::$forum_url['moderate_forum'], ForumCore::$forum_url['page'], (ForumCore::$forum_page['page'] + 1), ForumCore::$fid).'" title="'.ForumCore::$lang['Page'].' '.(ForumCore::$forum_page['page'] + 1).'" />';
		}
		if (ForumCore::$forum_page['page'] > 1)
		{
			ForumCore::$forum_page['nav']['prev'] = '<link rel="prev" href="'.forum_sublink(ForumCore::$forum_url['moderate_forum'], ForumCore::$forum_url['page'], (ForumCore::$forum_page['page'] - 1), ForumCore::$fid).'" title="'.ForumCore::$lang['Page'].' '.(ForumCore::$forum_page['page'] - 1).'" />';
			ForumCore::$forum_page['nav']['first'] = '<link rel="first" href="'.forum_link(ForumCore::$forum_url['moderate_forum'], ForumCore::$fid).'" title="'.ForumCore::$lang['Page'].' 1" />';
		}

		// Setup form
		ForumCore::$forum_page['fld_count'] = 0;
		ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['moderate_forum'], ForumCore::$fid);

		// Setup breadcrumbs
		ForumCore::$forum_page['crumbs'] = array(
			array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
			array(ForumCore::$cur_forum['forum_name'], forum_link(ForumCore::$forum_url['forum'], array(ForumCore::$fid, sef_friendly(ForumCore::$cur_forum['forum_name'])))),
			sprintf(ForumCore::$lang['Moderate forum head'], forum_htmlencode(ForumCore::$cur_forum['forum_name']))
		);

		// Setup main heading
		if (ForumCore::$forum_page['num_pages'] > 1)
			ForumCore::$forum_page['main_head_pages'] = sprintf(ForumCore::$lang['Page info'], ForumCore::$forum_page['page'], ForumCore::$forum_page['num_pages']);

		ForumCore::$forum_page['main_head_options']['select_all'] = '<span '.(empty(ForumCore::$forum_page['main_head_options']) ? ' class="first-item"' : '').'><span class="select-all js_link" data-check-form="mr-topic-actions-form">'.ForumCore::$lang['Select all'].'</span></span>';
		ForumCore::$forum_page['main_foot_options']['select_all'] = '<span '.(empty(ForumCore::$forum_page['main_foot_options']) ? ' class="first-item"' : '').'><span class="select-all js_link" data-check-form="mr-topic-actions-form">'.ForumCore::$lang['Select all'].'</span></span>';

		ForumCore::$forum_page['item_header'] = array();
		ForumCore::$forum_page['item_header']['subject']['title'] = '<strong class="subject-title">'.ForumCore::$lang['Topics'].'</strong>';

		if (ForumCore::$forum_config['o_topic_views'] == '1')
			ForumCore::$forum_page['item_header']['info']['views'] = '<strong class="info-views">'.ForumCore::$lang['views'].'</strong>';

		ForumCore::$forum_page['item_header']['info']['replies'] = '<strong class="info-replies">'.ForumCore::$lang['replies'].'</strong>';
		ForumCore::$forum_page['item_header']['info']['lastpost'] = '<strong class="info-lastpost">'.ForumCore::$lang['last post'].'</strong>';

		($hook = get_hook('mr_topic_actions_output_start')) ? eval($hook) : null;

		?>
			<div class="main-head">
		<?php

			if (!empty(ForumCore::$forum_page['main_head_options']))
				echo "\n\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_head_options']).'</p>';

		?>
				<h2 class="hn"><span><?php echo ForumCore::$forum_page['items_info'] ?></span></h2>
			</div>
			<form id="mr-topic-actions-form" method="post" accept-charset="utf-8" action="<?php echo ForumCore::$forum_page['form_action'] ?>">
			<div class="main-subhead">
				<p class="item-summary<?php echo (ForumCore::$forum_config['o_topic_views'] == '1') ? ' forum-views' : ' forum-noview' ?>"><span><?php printf(ForumCore::$lang['Forum subtitle'], implode(' ', ForumCore::$forum_page['item_header']['subject']), implode(', ', ForumCore::$forum_page['item_header']['info'])) ?></span></p>
			</div>
			<div id="forum<?php echo ForumCore::$fid ?>" class="main-content main-forum<?php echo (ForumCore::$forum_config['o_topic_views'] == '1') ? ' forum-views' : ' forum-noview' ?>">
				<div class="hidden">
					<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(ForumCore::$forum_page['form_action']) ?>" />
				</div>
		<?php

			ForumCore::$forum_page['item_count'] = 0;

			while (ForumCore::$cur_topic = $forum_db->fetch_assoc($result))
			{
				($hook = get_hook('mr_topic_actions_row_loop_start')) ? eval($hook) : null;

				++ForumCore::$forum_page['item_count'];

				// Start from scratch
				ForumCore::$forum_page['item_subject'] = ForumCore::$forum_page['item_body'] = ForumCore::$forum_page['item_status'] = ForumCore::$forum_page['item_nav'] = ForumCore::$forum_page['item_title'] = ForumCore::$forum_page['item_title_status'] = array();

				if (ForumCore::$forum_config['o_censoring'] == '1')
					ForumCore::$cur_topic['subject'] = censor_words(ForumCore::$cur_topic['subject']);

				ForumCore::$forum_page['item_subject']['starter'] = '<span class="item-starter">'.sprintf(ForumCore::$lang['Topic starter'], forum_htmlencode(ForumCore::$cur_topic['poster'])).'</span>';

				if (ForumCore::$cur_topic['moved_to'] !== null)
				{
					ForumCore::$forum_page['item_status']['moved'] = 'moved';
					ForumCore::$forum_page['item_title']['link'] = '<span class="item-status"><em class="moved">'.sprintf(ForumCore::$lang['Item status'], ForumCore::$lang['Moved']).'</em></span> <a href="'.forum_link(ForumCore::$forum_url['topic'], array(ForumCore::$cur_topic['moved_to'], sef_friendly(ForumCore::$cur_topic['subject']))).'">'.forum_htmlencode(ForumCore::$cur_topic['subject']).'</a>';

					// Combine everything to produce the Topic heading
					ForumCore::$forum_page['item_body']['subject']['title'] = '<h3 class="hn"><span class="item-num">'.forum_number_format(ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']).'</span> <strong>'.ForumCore::$forum_page['item_title']['link'].'</strong></h3>';

					($hook = get_hook('mr_topic_actions_moved_row_pre_item_subject_merge')) ? eval($hook) : null;

					if (ForumCore::$forum_config['o_topic_views'] == '1')
						ForumCore::$forum_page['item_body']['info']['views'] = '<li class="info-views"><span class="label">'.ForumCore::$lang['No views info'].'</span></li>';

					ForumCore::$forum_page['item_body']['info']['replies'] = '<li class="info-replies"><span class="label">'.ForumCore::$lang['No replies info'].'</span></li>';
					ForumCore::$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.ForumCore::$lang['No lastpost info'].'</span></li>';
					ForumCore::$forum_page['item_body']['info']['select'] = '<li class="info-select"><input id="fld'.++ForumCore::$forum_page['fld_count'].'" type="checkbox" name="topics[]" value="'.ForumCore::$cur_topic['id'].'" /> <label for="fld'.ForumCore::$forum_page['fld_count'].'">'.sprintf(ForumCore::$lang['Select topic'], forum_htmlencode(ForumCore::$cur_topic['subject'])).'</label></li>';

					($hook = get_hook('mr_topic_actions_moved_row_pre_output')) ? eval($hook) : null;
				}
				else
				{
					ForumCore::$forum_page['ghost_topic'] = false;

					// First assemble the Topic heading

					// Should we display the dot or not? :)
					if (!ForumUser::$forum_user['is_guest'] && ForumCore::$forum_config['o_show_dot'] == '1' && ForumCore::$cur_topic['has_posted'] == ForumUser::$forum_user['id'])
					{
						ForumCore::$forum_page['item_title']['posted'] = '<span class="posted-mark">'.ForumCore::$lang['You posted indicator'].'</span>';
						ForumCore::$forum_page['item_status']['posted'] = 'posted';
					}

					if (ForumCore::$cur_topic['sticky'] == '1')
					{
						ForumCore::$forum_page['item_title_status']['sticky'] = '<em class="sticky">'.ForumCore::$lang['Sticky'].'</em>';
						ForumCore::$forum_page['item_status']['sticky'] = 'sticky';
					}

					if (ForumCore::$cur_topic['closed'] == '1')
					{
						ForumCore::$forum_page['item_title_status']['closed'] = '<em class="closed">'.ForumCore::$lang['Closed'].'</em>';
						ForumCore::$forum_page['item_status']['closed'] = 'closed';
					}

					($hook = get_hook('mr_topic_loop_normal_topic_pre_item_title_status_merge')) ? eval($hook) : null;

					if (!empty(ForumCore::$forum_page['item_title_status']))
						ForumCore::$forum_page['item_title']['status'] = '<span class="item-status">'.sprintf(ForumCore::$lang['Item status'], implode(', ', ForumCore::$forum_page['item_title_status'])).'</span>';

					ForumCore::$forum_page['item_title']['link'] = '<a href="'.forum_link(ForumCore::$forum_url['topic'], array(ForumCore::$cur_topic['id'], sef_friendly(ForumCore::$cur_topic['subject']))).'">'.forum_htmlencode(ForumCore::$cur_topic['subject']).'</a>';

					($hook = get_hook('mr_topic_loop_normal_topic_pre_item_title_merge')) ? eval($hook) : null;

					ForumCore::$forum_page['item_body']['subject']['title'] = '<h3 class="hn"><span class="item-num">'.forum_number_format(ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']).'</span> '.implode(' ', ForumCore::$forum_page['item_title']).'</h3>';


					if (empty(ForumCore::$forum_page['item_status']))
						ForumCore::$forum_page['item_status']['normal'] = 'normal';

					ForumCore::$forum_page['item_pages'] = ceil((ForumCore::$cur_topic['num_replies'] + 1) / ForumUser::$forum_user['disp_posts']);

					if (ForumCore::$forum_page['item_pages'] > 1)
						ForumCore::$forum_page['item_nav']['pages'] = '<span>'.ForumCore::$lang['Pages'].'&#160;</span>'.paginate(ForumCore::$forum_page['item_pages'], -1, ForumCore::$forum_url['topic'], ForumCore::$lang['Page separator'], array(ForumCore::$cur_topic['id'], sef_friendly(ForumCore::$cur_topic['subject'])));

					// Does this topic contain posts we haven't read? If so, tag it accordingly.
					if (!ForumUser::$forum_user['is_guest'] && ForumCore::$cur_topic['last_post'] > ForumUser::$forum_user['last_visit'] && (!isset($tracked_topics['topics'][ForumCore::$cur_topic['id']]) || $tracked_topics['topics'][ForumCore::$cur_topic['id']] < ForumCore::$cur_topic['last_post']) && (!isset($tracked_topics['forums'][ForumCore::$fid]) || $tracked_topics['forums'][ForumCore::$fid] < ForumCore::$cur_topic['last_post']))
					{
						ForumCore::$forum_page['item_nav']['new'] = '<em class="item-newposts"><a href="'.forum_link(ForumCore::$forum_url['topic_new_posts'], array(ForumCore::$cur_topic['id'], sef_friendly(ForumCore::$cur_topic['subject']))).'">'.ForumCore::$lang['New posts'].'</a></em>';
						ForumCore::$forum_page['item_status']['new'] = 'new';
					}

					($hook = get_hook('mr_topic_loop_normal_topic_pre_item_nav_merge')) ? eval($hook) : null;

					if (!empty(ForumCore::$forum_page['item_nav']))
						ForumCore::$forum_page['item_subject']['nav'] = '<span class="item-nav">'.sprintf(ForumCore::$lang['Topic navigation'], implode('&#160;&#160;', ForumCore::$forum_page['item_nav'])).'</span>';

					// Assemble the Topic subject

					ForumCore::$forum_page['item_body']['info']['replies'] = '<li class="info-replies"><strong>'.forum_number_format(ForumCore::$cur_topic['num_replies']).'</strong> <span class="label">'.((ForumCore::$cur_topic['num_replies'] == 1) ? ForumCore::$lang['Reply'] : ForumCore::$lang['Replies']).'</span></li>';

					if (ForumCore::$forum_config['o_topic_views'] == '1')
						ForumCore::$forum_page['item_body']['info']['views'] = '<li class="info-views"><strong>'.forum_number_format(ForumCore::$cur_topic['num_views']).'</strong> <span class="label">'.((ForumCore::$cur_topic['num_views'] == 1) ? ForumCore::$lang['View'] : ForumCore::$lang['Views']).'</span></li>';

					ForumCore::$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.ForumCore::$lang['Last post'].'</span> <strong><a href="'.forum_link(ForumCore::$forum_url['post'], ForumCore::$cur_topic['last_post_id']).'">'.format_time(ForumCore::$cur_topic['last_post']).'</a></strong> <cite>'.sprintf(ForumCore::$lang['by poster'], forum_htmlencode(ForumCore::$cur_topic['last_poster'])).'</cite></li>';
					ForumCore::$forum_page['item_body']['info']['select'] = '<li class="info-select"><input id="fld'.++ForumCore::$forum_page['fld_count'].'" type="checkbox" name="topics[]" value="'.ForumCore::$cur_topic['id'].'" /> <label for="fld'.ForumCore::$forum_page['fld_count'].'">'.sprintf(ForumCore::$lang['Select topic'], forum_htmlencode(ForumCore::$cur_topic['subject'])).'</label></li>';

					($hook = get_hook('mr_topic_actions_normal_row_pre_output')) ? eval($hook) : null;
				}

				ForumCore::$forum_page['item_body']['subject']['desc'] = '<p>'.implode(' ', ForumCore::$forum_page['item_subject']).'</p>';

				($hook = get_hook('mr_topic_actions_row_pre_item_status_merge')) ? eval($hook) : null;

				ForumCore::$forum_page['item_style'] = ((ForumCore::$forum_page['item_count'] % 2 != 0) ? ' odd' : ' even').((ForumCore::$forum_page['item_count'] == 1) ? ' main-first-item' : '').((!empty(ForumCore::$forum_page['item_status'])) ? ' '.implode(' ', ForumCore::$forum_page['item_status']) : '');

				($hook = get_hook('mr_topic_actions_row_pre_display')) ? eval($hook) : null;

		?>
					<div id="topic<?php echo ForumCore::$cur_topic['id'] ?>" class="main-item<?php echo ForumCore::$forum_page['item_style'] ?>">
						<span class="icon <?php echo implode(' ', ForumCore::$forum_page['item_status']) ?>"><!-- --></span>
						<div class="item-subject">
							<?php echo implode("\n\t\t\t\t\t", ForumCore::$forum_page['item_body']['subject'])."\n" ?>
						</div>
						<ul class="item-info">
							<?php echo implode("\n\t\t\t\t\t", ForumCore::$forum_page['item_body']['info'])."\n" ?>
						</ul>
					</div>
		<?php

			}

		?>
			</div>
		<?php

			($hook = get_hook('mr_topic_actions_post_topic_list')) ? eval($hook) : null;

			// Setup moderator control buttons
			ForumCore::$forum_page['mod_options'] = array(
				'mod_move'		=> '<span class="submit first-item"><input type="submit" name="move_topics" value="'.ForumCore::$lang['Move'].'" /></span>',
				'mod_delete'	=> '<span class="submit"><input type="submit" name="delete_topics" value="'.ForumCore::$lang['Delete'].'" /></span>',
				'mod_merge'		=> '<span class="submit"><input type="submit" name="merge_topics" value="'.ForumCore::$lang['Merge'].'" /></span>',
				'mod_open'		=> '<span class="submit"><input type="submit" name="open" value="'.ForumCore::$lang['Open'].'" /></span>',
				'mod_close'		=> '<span class="submit"><input type="submit" name="close" value="'.ForumCore::$lang['Close'].'" /></span>'
			);

			($hook = get_hook('mr_topic_actions_pre_mod_option_output')) ? eval($hook) : null;

		?>
			<div class="main-options mod-options gen-content">
				<p class="options"><?php echo implode(' ', ForumCore::$forum_page['mod_options']) ?></p>
			</div>
			</form>
			<div class="main-foot">
		<?php

			if (!empty(ForumCore::$forum_page['main_foot_options']))
				echo "\n\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_foot_options']).'</p>';

		?>
				<h2 class="hn"><span><?php echo ForumCore::$forum_page['items_info'] ?></span></h2>
			</div>

		<?php

		$forum_id = ForumCore::$fid;

		// Init JS helper for select-all
		//$forum_loader->add_js('PUNBB.common.addDOMReadyEvent(PUNBB.common.initToggleCheckboxes);', array('type' => 'inline'));

		($hook = get_hook('mr_end')) ? eval($hook) : null;

	});

}

require FORUM_ROOT.'footer.php';
