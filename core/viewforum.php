<?php
/**
 * Lists the topics in the specified forum.
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

($hook = get_hook('vf_start')) ? eval($hook) : null;

if (ForumUser::$forum_user['g_read_board'] == '0')
	message(ForumCore::$lang['No view']);

// Load the viewforum.php language file
ForumCore::add_lang('forum');

ForumCore::$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (ForumCore::$id < 1)
	message(ForumCore::$lang['Bad request']);

$forum_db = new DBLayer;

// Fetch some info about the forum
$query = array(
	'SELECT'	=> 'f.forum_name, f.redirect_url, f.moderators, f.num_topics, f.sort_by, fp.post_topics, f.forum_desc',
	'FROM'		=> 'forums AS f',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'		=> 'forum_perms AS fp',
			'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.ForumUser::$forum_user['g_id'].')'
		)
	),
	'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.ForumCore::$id
);

if (!ForumUser::$forum_user['is_guest'] && ForumCore::$forum_config['o_subscriptions'] == '1')
{
	$query['SELECT'] .= ', fs.user_id AS is_subscribed';
	$query['JOINS'][] = array(
		'LEFT JOIN'	=> 'forum_subscriptions AS fs',
		'ON'		=> '(f.id=fs.forum_id AND fs.user_id='.ForumUser::$forum_user['id'].')'
	);
}

($hook = get_hook('vf_qr_get_forum_info')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
ForumCore::$cur_forum = $forum_db->fetch_assoc($result);

if (!ForumCore::$cur_forum)
	message(ForumCore::$lang['Bad request']);


($hook = get_hook('vf_modify_forum_info')) ? eval($hook) : null;

// Is this a redirect forum? In that case, redirect!
if (ForumCore::$cur_forum['redirect_url'] != '')
{
	($hook = get_hook('vf_redirect_forum_pre_redirect')) ? eval($hook) : null;

	header('Location: '.ForumCore::$cur_forum['redirect_url']);
	exit;
}

($hook = get_hook('vf_pre_header_load')) ? eval($hook) : null;

// Set HEAD title
ForumCore::$page_title = ForumCore::$cur_forum['forum_name'];
add_filter( 'pre_get_document_title', function(){
	return ForumCore::$page_title;
}, 999);

define('FORUM_ALLOW_INDEX', 1);

define('FORUM_PAGE', 'viewforum');
require FORUM_ROOT.'header.php';


// SHORTCODE [pun_content]
add_shortcode('pun_content', function ()
{
	$forum_db = new DBLayer;

	// Sort out who the moderators are and if we are currently a moderator (or an admin)
	$mods_array = (ForumCore::$cur_forum['moderators'] != '') ? unserialize(ForumCore::$cur_forum['moderators']) : array();
	ForumCore::$forum_page['is_admmod'] = (ForumUser::$forum_user['g_id'] == FORUM_ADMIN || (ForumUser::$forum_user['g_moderator'] == '1' && array_key_exists(ForumUser::$forum_user['username'], $mods_array))) ? true : false;

	// Sort out whether or not this user can post
	ForumUser::$forum_user['may_post'] = ((ForumCore::$cur_forum['post_topics'] == '' && ForumUser::$forum_user['g_post_topics'] == '1') || ForumCore::$cur_forum['post_topics'] == '1' || ForumCore::$forum_page['is_admmod']) ? true : false;

	// Get topic/forum tracking data
	if (!ForumUser::$forum_user['is_guest'])
		$tracked_topics = get_tracked_topics();

	// Determine the topic offset (based on $_GET['p'])
	ForumCore::$forum_page['num_pages'] = ceil(ForumCore::$cur_forum['num_topics'] / ForumUser::$forum_user['disp_topics']);
	ForumCore::$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > ForumCore::$forum_page['num_pages']) ? 1 : $_GET['p'];
	ForumCore::$forum_page['start_from'] = ForumUser::$forum_user['disp_topics'] * (ForumCore::$forum_page['page'] - 1);
	ForumCore::$forum_page['finish_at'] = min((ForumCore::$forum_page['start_from'] + ForumUser::$forum_user['disp_topics']), (ForumCore::$cur_forum['num_topics']));
	ForumCore::$forum_page['items_info'] = generate_items_info(ForumCore::$lang['Topics'], (ForumCore::$forum_page['start_from'] + 1), ForumCore::$cur_forum['num_topics']);

	($hook = get_hook('vf_modify_page_details')) ? eval($hook) : null;

	// Navigation links for header and page numbering for title/meta description
	if (ForumCore::$forum_page['page'] < ForumCore::$forum_page['num_pages'])
	{
		ForumCore::$forum_page['nav']['last'] = '<link rel="last" href="'.forum_sublink(ForumCore::$forum_url['forum'], ForumCore::$forum_url['page'], ForumCore::$forum_page['num_pages'], array(ForumCore::$id, sef_friendly(ForumCore::$cur_forum['forum_name']))).'" title="'.ForumCore::$lang['Page'].' '.ForumCore::$forum_page['num_pages'].'" />';
		ForumCore::$forum_page['nav']['next'] = '<link rel="next" href="'.forum_sublink(ForumCore::$forum_url['forum'], ForumCore::$forum_url['page'], (ForumCore::$forum_page['page'] + 1), array(ForumCore::$id, sef_friendly(ForumCore::$cur_forum['forum_name']))).'" title="'.ForumCore::$lang['Page'].' '.(ForumCore::$forum_page['page'] + 1).'" />';
	}
	if (ForumCore::$forum_page['page'] > 1)
	{
		ForumCore::$forum_page['nav']['prev'] = '<link rel="prev" href="'.forum_sublink(ForumCore::$forum_url['forum'], ForumCore::$forum_url['page'], (ForumCore::$forum_page['page'] - 1), array(ForumCore::$id, sef_friendly(ForumCore::$cur_forum['forum_name']))).'" title="'.ForumCore::$lang['Page'].' '.(ForumCore::$forum_page['page'] - 1).'" />';
		ForumCore::$forum_page['nav']['first'] = '<link rel="first" href="'.forum_link(ForumCore::$forum_url['forum'], array(ForumCore::$id, sef_friendly(ForumCore::$cur_forum['forum_name']))).'" title="'.ForumCore::$lang['Page'].' 1" />';
	}


	// 1. Retrieve the topics id
	$query = array(
		'SELECT'	=> 't.id',
		'FROM'		=> 'topics AS t',
		'WHERE'		=> 't.forum_id='.ForumCore::$id,
		'ORDER BY'	=> 't.sticky DESC, '.((ForumCore::$cur_forum['sort_by'] == '1') ? 't.posted' : 't.last_post').' DESC',
		'LIMIT'		=> ForumCore::$forum_page['start_from'].', '.ForumUser::$forum_user['disp_topics']
	);

	($hook = get_hook('vt_qr_get_topics_id')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$topics_id = $topics = array();
	while ($row = $forum_db->fetch_assoc($result)) {
		$topics_id[] = $row['id'];
	}

	// If there are topics id in this forum
	if (!empty($topics_id))
	{
		/*
		* Fetch list of topics
		* EXT DEVELOPERS
		* If you modify SELECT of this query - than add same columns in next query (has posted) in GROUP BY
		*/
		$query = array(
			'SELECT'	=> 't.id, t.poster, t.subject, t.posted, t.first_post_id, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to',
			'FROM'		=> 'topics AS t',
			'WHERE'		=> 't.id IN ('.implode(',', $topics_id).')',
			'ORDER BY'	=> 't.sticky DESC, '.((ForumCore::$cur_forum['sort_by'] == '1') ? 't.posted' : 't.last_post').' DESC',
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
			$query['GROUP BY'] = 't.id, t.poster, t.subject, t.posted, t.first_post_id, t.last_post, t.last_post_id, t.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to, p.poster_id';

			($hook = get_hook('vf_qr_get_has_posted')) ? eval($hook) : null;
		}

		($hook = get_hook('vf_qr_get_topics')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		while ($cur_topic = $forum_db->fetch_assoc($result))
		{
			$topics[] = $cur_topic;
		}
	}

	// Generate paging/posting links
	ForumCore::$forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">'.ForumCore::$lang['Pages'].'</span> '.paginate(ForumCore::$forum_page['num_pages'], ForumCore::$forum_page['page'], ForumCore::$forum_url['forum'], ForumCore::$lang['Paging separator'], array(ForumCore::$id, sef_friendly(ForumCore::$cur_forum['forum_name']))).'</p>';

	if (ForumUser::$forum_user['may_post'])
		ForumCore::$forum_page['page_post']['posting'] = '<p class="posting"><a class="newpost" href="'.forum_link(ForumCore::$forum_url['new_topic'], ForumCore::$id).'"><span>'.ForumCore::$lang['Post topic'].'</span></a></p>';
	else if (ForumUser::$forum_user['is_guest'])
		ForumCore::$forum_page['page_post']['posting'] = '<p class="posting">'.sprintf(ForumCore::$lang['Login to post'], '<a href="'.forum_link(ForumCore::$forum_url['login']).'">'.ForumCore::$lang['login'].'</a>', '<a href="'.forum_link(ForumCore::$forum_url['register']).'">'.ForumCore::$lang['register'].'</a>').'</p>';
	else
		ForumCore::$forum_page['page_post']['posting'] = '<p class="posting">'.ForumCore::$lang['No permission'].'</p>';

	// Setup main options
	ForumCore::$forum_page['main_head_options'] = ForumCore::$forum_page['main_foot_options'] = array();

	if (!empty($topics))
		ForumCore::$forum_page['main_head_options']['feed'] = '<span class="feed first-item"><a class="feed" href="'.forum_link(ForumCore::$forum_url['forum_rss'], ForumCore::$id).'">'.ForumCore::$lang['RSS forum feed'].'</a></span>';

	if (!ForumUser::$forum_user['is_guest'] && ForumCore::$forum_config['o_subscriptions'] == '1')
	{
		if (ForumCore::$cur_forum['is_subscribed'])
			ForumCore::$forum_page['main_head_options']['unsubscribe'] = '<span><a class="sub-option" href="'.forum_link(ForumCore::$forum_url['forum_unsubscribe'], array(ForumCore::$id, generate_form_token('forum_unsubscribe'.ForumCore::$id.ForumUser::$forum_user['id']))).'"><em>'.ForumCore::$lang['Unsubscribe'].'</em></a></span>';
		else
			ForumCore::$forum_page['main_head_options']['subscribe'] = '<span><a class="sub-option" href="'.forum_link(ForumCore::$forum_url['forum_subscribe'], array(ForumCore::$id, generate_form_token('forum_subscribe'.ForumCore::$id.ForumUser::$forum_user['id']))).'" title="'.ForumCore::$lang['Subscribe info'].'">'.ForumCore::$lang['Subscribe'].'</a></span>';
	}

	if (!ForumUser::$forum_user['is_guest'] && !empty($topics))
	{
		ForumCore::$forum_page['main_foot_options']['mark_read'] = '<span class="first-item"><a href="'.forum_link(ForumCore::$forum_url['mark_forum_read'], array(ForumCore::$id, generate_form_token('markforumread'.ForumCore::$id.ForumUser::$forum_user['id']))).'">'.ForumCore::$lang['Mark forum read'].'</a></span>';

		if (ForumCore::$forum_page['is_admmod'])
			ForumCore::$forum_page['main_foot_options']['moderate'] = '<span'.(empty(ForumCore::$forum_page['main_foot_options']) ? ' class="first-item"' : '').'><a href="'.forum_sublink(ForumCore::$forum_url['moderate_forum'], ForumCore::$forum_url['page'], ForumCore::$forum_page['page'], ForumCore::$id).'">'.ForumCore::$lang['Moderate forum'].'</a></span>';
	}

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		ForumCore::$cur_forum['forum_name']
	);

	// Setup main header
	ForumCore::$forum_page['main_title'] = '<a class="permalink" href="'.forum_link(ForumCore::$forum_url['forum'], array(ForumCore::$id, sef_friendly(ForumCore::$cur_forum['forum_name']))).'" rel="bookmark" title="'.ForumCore::$lang['Permalink forum'].'">'.forum_htmlencode(ForumCore::$cur_forum['forum_name']).'</a>';

	if (ForumCore::$forum_page['num_pages'] > 1)
		ForumCore::$forum_page['main_head_pages'] = sprintf(ForumCore::$lang['Page info'], ForumCore::$forum_page['page'], ForumCore::$forum_page['num_pages']);


	ForumCore::$forum_page['item_header'] = array();
	ForumCore::$forum_page['item_header']['subject']['title'] = '<strong class="subject-title">'.ForumCore::$lang['Topics'].'</strong>';
	ForumCore::$forum_page['item_header']['info']['replies'] = '<strong class="info-replies">'.ForumCore::$lang['replies'].'</strong>';

	if (ForumCore::$forum_config['o_topic_views'] == '1')
		ForumCore::$forum_page['item_header']['info']['views'] = '<strong class="info-views">'.ForumCore::$lang['views'].'</strong>';

	ForumCore::$forum_page['item_header']['info']['lastpost'] = '<strong class="info-lastpost">'.ForumCore::$lang['last post'].'</strong>';

	($hook = get_hook('vf_main_output_start')) ? eval($hook) : null;

	// If there are topics in this forum
	if (!empty($topics))
	{

?>
	<div id="brd-pagepost-top" class="main-pagepost gen-content">
		<?php echo implode("\n\t", ForumCore::$forum_page['page_post']) ?>
	</div>

	<div class="main-head">
<?php

		if (!empty(ForumCore::$forum_page['main_head_options']))
			echo "\n\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_head_options']).'</p>';

?>
		<h2 class="hn"><span><?php echo ForumCore::$forum_page['items_info'] ?></span></h2>
	</div>
	<div class="main-subhead">
		<p class="item-summary<?php echo (ForumCore::$forum_config['o_topic_views'] == '1') ? ' forum-views' : ' forum-noview' ?>"><span><?php printf(ForumCore::$lang['Forum subtitle'], implode(' ', ForumCore::$forum_page['item_header']['subject']), implode(', ', ForumCore::$forum_page['item_header']['info'])) ?></span></p>
	</div>
	<div id="forum<?php echo ForumCore::$id ?>" class="main-content main-forum<?php echo (ForumCore::$forum_config['o_topic_views'] == '1') ? ' forum-views' : ' forum-noview' ?>">
<?php

		($hook = get_hook('vf_pre_topic_loop_start')) ? eval($hook) : null;

		ForumCore::$forum_page['item_count'] = 0;

		foreach ($topics as $cur_topic)
		{
			($hook = get_hook('vf_topic_loop_start')) ? eval($hook) : null;

			++ForumCore::$forum_page['item_count'];

			// Start from scratch
			ForumCore::$forum_page['item_subject'] = ForumCore::$forum_page['item_body'] = ForumCore::$forum_page['item_status'] = ForumCore::$forum_page['item_nav'] = ForumCore::$forum_page['item_title'] = ForumCore::$forum_page['item_title_status'] = array();

			if (ForumCore::$forum_config['o_censoring'] == '1')
				$cur_topic['subject'] = censor_words($cur_topic['subject']);

			ForumCore::$forum_page['item_subject']['starter'] = '<span class="item-starter">'.sprintf(ForumCore::$lang['Topic starter'], forum_htmlencode($cur_topic['poster'])).'</span>';

			if ($cur_topic['moved_to'] !== null)
			{
				ForumCore::$forum_page['item_status']['moved'] = 'moved';
				ForumCore::$forum_page['item_title']['link'] = '<span class="item-status"><em class="moved">'.sprintf(ForumCore::$lang['Item status'], ForumCore::$lang['Moved']).'</em></span> <a href="'.forum_link(ForumCore::$forum_url['topic'], array($cur_topic['moved_to'], sef_friendly($cur_topic['subject']))).'">'.forum_htmlencode($cur_topic['subject']).'</a>';

				// Combine everything to produce the Topic heading
				ForumCore::$forum_page['item_body']['subject']['title'] = '<h3 class="hn"><span class="item-num">'.forum_number_format(ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']).'</span>'.ForumCore::$forum_page['item_title']['link'].'</h3>';

				($hook = get_hook('vf_topic_loop_moved_topic_pre_item_subject_merge')) ? eval($hook) : null;

				ForumCore::$forum_page['item_body']['info']['replies'] = '<li class="info-replies"><span class="label">'.ForumCore::$lang['No replies info'].'</span></li>';

				if (ForumCore::$forum_config['o_topic_views'] == '1')
					ForumCore::$forum_page['item_body']['info']['views'] = '<li class="info-views"><span class="label">'.ForumCore::$lang['No views info'].'</span></li>';

				ForumCore::$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.ForumCore::$lang['No lastpost info'].'</span></li>';
			}
			else
			{
				// Assemble the Topic heading

				// Should we display the dot or not? :)
				if (!ForumUser::$forum_user['is_guest'] && ForumCore::$forum_config['o_show_dot'] == '1' && $cur_topic['has_posted'] == ForumUser::$forum_user['id'])
				{
					ForumCore::$forum_page['item_title']['posted'] = '<span class="posted-mark">'.ForumCore::$lang['You posted indicator'].'</span>';
					ForumCore::$forum_page['item_status']['posted'] = 'posted';
				}

				if ($cur_topic['sticky'] == '1')
				{
					ForumCore::$forum_page['item_title_status']['sticky'] = '<em class="sticky">'.ForumCore::$lang['Sticky'].'</em>';
					ForumCore::$forum_page['item_status']['sticky'] = 'sticky';
				}

				if ($cur_topic['closed'] == '1')
				{
					ForumCore::$forum_page['item_title_status']['closed'] = '<em class="closed">'.ForumCore::$lang['Closed'].'</em>';
					ForumCore::$forum_page['item_status']['closed'] = 'closed';
				}

				($hook = get_hook('vf_topic_loop_normal_topic_pre_item_title_status_merge')) ? eval($hook) : null;

				if (!empty(ForumCore::$forum_page['item_title_status']))
					ForumCore::$forum_page['item_title']['status'] = '<span class="item-status">'.sprintf(ForumCore::$lang['Item status'], implode(', ', ForumCore::$forum_page['item_title_status'])).'</span>';

				ForumCore::$forum_page['item_title']['link'] = '<a href="'.forum_link(ForumCore::$forum_url['topic'], array($cur_topic['id'], sef_friendly($cur_topic['subject']))).'">'.forum_htmlencode($cur_topic['subject']).'</a>';

				($hook = get_hook('vf_topic_loop_normal_topic_pre_item_title_merge')) ? eval($hook) : null;

				ForumCore::$forum_page['item_body']['subject']['title'] = '<h3 class="hn"><span class="item-num">'.forum_number_format(ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']).'</span> '.implode(' ', ForumCore::$forum_page['item_title']).'</h3>';

				if (empty(ForumCore::$forum_page['item_status']))
					ForumCore::$forum_page['item_status']['normal'] = 'normal';

				ForumCore::$forum_page['item_pages'] = ceil(($cur_topic['num_replies'] + 1) / ForumUser::$forum_user['disp_posts']);

				if (ForumCore::$forum_page['item_pages'] > 1)
					ForumCore::$forum_page['item_nav']['pages'] = '<span>'.ForumCore::$lang['Pages'].'&#160;</span>'.paginate(ForumCore::$forum_page['item_pages'], -1, ForumCore::$forum_url['topic'], ForumCore::$lang['Page separator'], array($cur_topic['id'], sef_friendly($cur_topic['subject'])));

				// Does this topic contain posts we haven't read? If so, tag it accordingly.
				if (!ForumUser::$forum_user['is_guest'] && $cur_topic['last_post'] > ForumUser::$forum_user['last_visit'] && (!isset($tracked_topics['topics'][$cur_topic['id']]) || $tracked_topics['topics'][$cur_topic['id']] < $cur_topic['last_post']) && (!isset($tracked_topics['forums'][ForumCore::$id]) || $tracked_topics['forums'][ForumCore::$id] < $cur_topic['last_post']))
				{
					ForumCore::$forum_page['item_nav']['new'] = '<em class="item-newposts"><a href="'.forum_link(ForumCore::$forum_url['topic_new_posts'], array($cur_topic['id'], sef_friendly($cur_topic['subject']))).'">'.ForumCore::$lang['New posts'].'</a></em>';
					ForumCore::$forum_page['item_status']['new'] = 'new';
				}

				($hook = get_hook('vf_topic_loop_normal_topic_pre_item_nav_merge')) ? eval($hook) : null;

				if (!empty(ForumCore::$forum_page['item_nav']))
					ForumCore::$forum_page['item_subject']['nav'] = '<span class="item-nav">'.sprintf(ForumCore::$lang['Topic navigation'], implode('&#160;&#160;', ForumCore::$forum_page['item_nav'])).'</span>';

				// Assemble the Topic subject

				ForumCore::$forum_page['item_body']['info']['replies'] = '<li class="info-replies"><strong>'.forum_number_format($cur_topic['num_replies']).'</strong> <span class="label">'.(($cur_topic['num_replies'] == 1) ? ForumCore::$lang['reply'] : ForumCore::$lang['replies']).'</span></li>';

				if (ForumCore::$forum_config['o_topic_views'] == '1')
					ForumCore::$forum_page['item_body']['info']['views'] = '<li class="info-views"><strong>'.forum_number_format($cur_topic['num_views']).'</strong> <span class="label">'.(($cur_topic['num_views'] == 1) ? ForumCore::$lang['view'] : ForumCore::$lang['views']).'</span></li>';

				ForumCore::$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.ForumCore::$lang['Last post'].'</span> <strong><a href="'.forum_link(ForumCore::$forum_url['post'], $cur_topic['last_post_id']).'">'.format_time($cur_topic['last_post']).'</a></strong> <cite>'.sprintf(ForumCore::$lang['by poster'], forum_htmlencode($cur_topic['last_poster'])).'</cite></li>';
			}

			($hook = get_hook('vf_row_pre_item_subject_merge')) ? eval($hook) : null;

			ForumCore::$forum_page['item_body']['subject']['desc'] = '<p>'.implode(' ', ForumCore::$forum_page['item_subject']).'</p>';

			($hook = get_hook('vf_row_pre_item_status_merge')) ? eval($hook) : null;

			ForumCore::$forum_page['item_style'] = ((ForumCore::$forum_page['item_count'] % 2 != 0) ? ' odd' : ' even').((ForumCore::$forum_page['item_count'] == 1) ? ' main-first-item' : '').((!empty(ForumCore::$forum_page['item_status'])) ? ' '.implode(' ', ForumCore::$forum_page['item_status']) : '');

			($hook = get_hook('vf_row_pre_display')) ? eval($hook) : null;

?>
		<div id="topic<?php echo $cur_topic['id'] ?>" class="main-item<?php echo ForumCore::$forum_page['item_style'] ?>">
			<span class="icon <?php echo implode(' ', ForumCore::$forum_page['item_status']) ?>"><!-- --></span>
			<div class="item-subject">
				<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['item_body']['subject'])."\n" ?>
			</div>
			<ul class="item-info">
				<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['item_body']['info'])."\n" ?>
			</ul>
		</div>
<?php

		}

?>
	</div>
	<div class="main-foot">
<?php

		if (!empty(ForumCore::$forum_page['main_foot_options']))
			echo "\n\t\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_foot_options']).'</p>';

?>
		<h2 class="hn"><span><?php echo ForumCore::$forum_page['items_info'] ?></span></h2>
	</div>
<?php

	}
	// Else there are no topics in this forum
	else
	{
		ForumCore::$forum_page['item_body']['subject']['title'] = '<h3 class="hn">'.ForumCore::$lang['No topics'].'</h3>';
		ForumCore::$forum_page['item_body']['subject']['desc'] = '<p>'.ForumCore::$lang['First topic nag'].'</p>';

		($hook = get_hook('vf_no_results_row_pre_display')) ? eval($hook) : null;

?>
	<div id="brd-pagepost-top" class="main-pagepost gen-content">
		<?php echo implode("\n\t", ForumCore::$forum_page['page_post']) ?>
	</div>

	<div class="main-head">
<?php

		if (!empty(ForumCore::$forum_page['main_head_options']))
			echo "\n\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_head_options']).'</p>';
?>
		<h2 class="hn"><span><?php echo ForumCore::$lang['Empty forum'] ?></span></h2>
	</div>
	<div id="forum<?php echo ForumCore::$id ?>" class="main-content main-forum">
		<div class="main-item empty main-first-item">
			<span class="icon empty"><!-- --></span>
			<div class="item-subject">
				<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['item_body']['subject'])."\n" ?>
			</div>
		</div>
	</div>
	<div class="main-foot">
		<h2 class="hn"><span><?php echo ForumCore::$lang['Empty forum'] ?></span></h2>
	</div>
<?php

	}

	($hook = get_hook('vf_end')) ? eval($hook) : null;

	$forum_id = ForumCore::$id;
});

require FORUM_ROOT.'footer.php';
