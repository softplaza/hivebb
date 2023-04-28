<?php
/**
 * Lists the posts in the specified topic.
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

($hook = get_hook('vt_start')) ? eval($hook) : null;

if (ForumUser::$forum_user['g_read_board'] == '0')
	message(ForumCore::$lang['No view']);

$forum_db = new DBLayer;

// Load the viewtopic.php language file
//require FORUM_ROOT.'lang/'.ForumUser::$forum_user['language'].'/topic.php';
ForumCore::add_lang('topic');

$action = isset($_GET['action']) ? $_GET['action'] : null;
ForumCore::$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
if (ForumCore::$id < 1 && $pid < 1)
	message(ForumCore::$lang['Bad request']);


// If a post ID is specified we determine topic ID and page number so we can redirect to the correct message
if ($pid)
{
	$query = array(
		'SELECT'	=> 'p.topic_id, p.posted',
		'FROM'		=> 'posts AS p',
		'WHERE'		=> 'p.id='.$pid
	);

	($hook = get_hook('vt_qr_get_post_info')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$topic_info = $forum_db->fetch_assoc($result);

	if (!$topic_info)
	{
		message(ForumCore::$lang['Bad request']);
	}

	ForumCore::$id = $topic_info['topic_id'];

	// Determine on what page the post is located (depending on ForumUser::$forum_user['disp_posts'])
	$query = array(
		'SELECT'	=> 'COUNT(p.id)',
		'FROM'		=> 'posts AS p',
		'WHERE'		=> 'p.topic_id='.$topic_info['topic_id'].' AND p.posted<'.$topic_info['posted']
	);

	($hook = get_hook('vt_qr_get_post_page')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$num_posts = $forum_db->result($result) + 1;

	$_GET['p'] = ceil($num_posts / ForumUser::$forum_user['disp_posts']);
}

// If action=new, we redirect to the first new post (if any)
else if ($action == 'new')
{
	if (!ForumUser::$forum_user['is_guest'])
	{
		// We need to check if this topic has been viewed recently by the user
		$tracked_topics = get_tracked_topics();
		$last_viewed = isset($tracked_topics['topics'][ForumCore::$id]) ? $tracked_topics['topics'][ForumCore::$id] : ForumUser::$forum_user['last_visit'];

		($hook = get_hook('vt_find_new_post')) ? eval($hook) : null;

		$query = array(
			'SELECT'	=> 'MIN(p.id)',
			'FROM'		=> 'posts AS p',
			'WHERE'		=> 'p.topic_id='.ForumCore::$id.' AND p.posted>'.$last_viewed
		);
		($hook = get_hook('vt_qr_get_first_new_post')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$first_new_post_id = $forum_db->result($result);

		if ($first_new_post_id)
		{
			header('Location: '.str_replace('&amp;', '&', forum_link(ForumCore::$forum_url['post'], $first_new_post_id)));
			exit;
		}
	}

	//header('Location: '.str_replace('&amp;', '&', forum_link(ForumCore::$forum_url['topic_last_post'], ForumCore::$id)));
	//exit;
	redirect(forum_link(ForumCore::$forum_url['topic_last_post'], ForumCore::$id), '');
}

// If action=last, we redirect to the last post
else if ($action == 'last')
{
	$query = array(
		'SELECT'	=> 't.last_post_id',
		'FROM'		=> 'topics AS t',
		'WHERE'		=> 't.id='.ForumCore::$id
	);
	($hook = get_hook('vt_qr_get_last_post')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$last_post_id = $forum_db->result($result);

	if ($last_post_id)
	{
		//header('Location: '.str_replace('&amp;', '&', forum_link(ForumCore::$forum_url['post'], $last_post_id)));
		//exit;
		redirect(forum_link(ForumCore::$forum_url['post'], $last_post_id), '');
	}
}

// Fetch some info about the topic
$query = array(
	'SELECT'	=> 't.subject, t.first_post_id, t.closed, t.num_replies, t.sticky, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies',
	'FROM'		=> 'topics AS t',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'forums AS f',
			'ON'			=> 'f.id=t.forum_id'
		),
		array(
			'LEFT JOIN'		=> 'forum_perms AS fp',
			'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.ForumUser::$forum_user['g_id'].')'
		)
	),
	'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.ForumCore::$id.' AND t.moved_to IS NULL'
);

if (!ForumUser::$forum_user['is_guest'] && ForumCore::$forum_config['o_subscriptions'] == '1')
{
	$query['SELECT'] .= ', s.user_id AS is_subscribed';
	$query['JOINS'][] = array(
		'LEFT JOIN'	=> 'subscriptions AS s',
		'ON'		=> '(t.id=s.topic_id AND s.user_id='.ForumUser::$forum_user['id'].')'
	);
}

($hook = get_hook('vt_qr_get_topic_info')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
ForumCore::$cur_topic = $forum_db->fetch_assoc($result);

if (!ForumCore::$cur_topic)
{
	message(ForumCore::$lang['Bad request']);
}

($hook = get_hook('vt_modify_topic_info')) ? eval($hook) : null;

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = (ForumCore::$cur_topic['moderators'] != '') ? unserialize(ForumCore::$cur_topic['moderators']) : array();
ForumCore::$forum_page['is_admmod'] = (ForumUser::$forum_user['g_id'] == FORUM_ADMIN || (ForumUser::$forum_user['g_moderator'] == '1' && array_key_exists(ForumUser::$forum_user['username'], $mods_array))) ? true : false;

// Can we or can we not post replies?
if (ForumCore::$cur_topic['closed'] == '0' || ForumCore::$forum_page['is_admmod'])
	ForumUser::$forum_user['may_post'] = ((ForumCore::$cur_topic['post_replies'] == '' && ForumUser::$forum_user['g_post_replies'] == '1') || ForumCore::$cur_topic['post_replies'] == '1' || ForumCore::$forum_page['is_admmod']) ? true : false;
else
	ForumUser::$forum_user['may_post'] = false;

// Add/update this topic in our list of tracked topics
if (!ForumUser::$forum_user['is_guest'])
{
	$tracked_topics = get_tracked_topics();
	$tracked_topics['topics'][ForumCore::$id] = time();
	set_tracked_topics($tracked_topics);
}

// Determine the post offset (based on $_GET['p'])
ForumCore::$forum_page['num_pages'] = ceil((ForumCore::$cur_topic['num_replies'] + 1) / ForumUser::$forum_user['disp_posts']);
ForumCore::$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > ForumCore::$forum_page['num_pages']) ? 1 : $_GET['p'];
ForumCore::$forum_page['start_from'] = ForumUser::$forum_user['disp_posts'] * (ForumCore::$forum_page['page'] - 1);
ForumCore::$forum_page['finish_at'] = min((ForumCore::$forum_page['start_from'] + ForumUser::$forum_user['disp_posts']), (ForumCore::$cur_topic['num_replies'] + 1));
ForumCore::$forum_page['items_info'] = generate_items_info(ForumCore::$lang['Posts'], (ForumCore::$forum_page['start_from'] + 1), (ForumCore::$cur_topic['num_replies'] + 1));

($hook = get_hook('vt_modify_page_details')) ? eval($hook) : null;

if (ForumCore::$forum_config['o_censoring'] == '1')
	ForumCore::$cur_topic['subject'] = censor_words(ForumCore::$cur_topic['subject']);

// Navigation links for header and page numbering for title/meta description
if (ForumCore::$forum_page['page'] < ForumCore::$forum_page['num_pages'])
{
	ForumCore::$forum_page['nav']['last'] = '<link rel="last" href="'.forum_sublink(ForumCore::$forum_url['topic'], ForumCore::$forum_url['page'], ForumCore::$forum_page['num_pages'], array(ForumCore::$id, sef_friendly(ForumCore::$cur_topic['subject']))).'" title="'.ForumCore::$lang['Page'].' '.ForumCore::$forum_page['num_pages'].'" />';
	ForumCore::$forum_page['nav']['next'] = '<link rel="next" href="'.forum_sublink(ForumCore::$forum_url['topic'], ForumCore::$forum_url['page'], (ForumCore::$forum_page['page'] + 1), array(ForumCore::$id, sef_friendly(ForumCore::$cur_topic['subject']))).'" title="'.ForumCore::$lang['Page'].' '.(ForumCore::$forum_page['page'] + 1).'" />';
}
if (ForumCore::$forum_page['page'] > 1)
{
	ForumCore::$forum_page['nav']['prev'] = '<link rel="prev" href="'.forum_sublink(ForumCore::$forum_url['topic'], ForumCore::$forum_url['page'], (ForumCore::$forum_page['page'] - 1), array(ForumCore::$id, sef_friendly(ForumCore::$cur_topic['subject']))).'" title="'.ForumCore::$lang['Page'].' '.(ForumCore::$forum_page['page'] - 1).'" />';
	ForumCore::$forum_page['nav']['first'] = '<link rel="first" href="'.forum_link(ForumCore::$forum_url['topic'], array(ForumCore::$id, sef_friendly(ForumCore::$cur_topic['subject']))).'" title="'.ForumCore::$lang['Page'].' 1" />';
}
ForumCore::$forum_page['nav']['canonical'] = '<link rel="canonical" href="'.forum_sublink(ForumCore::$forum_url['topic'], ForumCore::$forum_url['page'], ForumCore::$forum_page['page'], array(ForumCore::$id, sef_friendly(ForumCore::$cur_topic['subject']))).'" title="'.ForumCore::$lang['Page'].' '.ForumCore::$forum_page['page'].'" />';

// Generate paging and posting links
ForumCore::$forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">'.ForumCore::$lang['Pages'].'</span> '.paginate(ForumCore::$forum_page['num_pages'], ForumCore::$forum_page['page'], ForumCore::$forum_url['topic'], ForumCore::$lang['Paging separator'], array(ForumCore::$id, sef_friendly(ForumCore::$cur_topic['subject']))).'</p>';

if (ForumUser::$forum_user['may_post'])
	ForumCore::$forum_page['page_post']['posting'] = '<p class="posting"><a class="newpost" href="'.forum_link(ForumCore::$forum_url['new_reply'], ForumCore::$id).'"><span>'.ForumCore::$lang['Post reply'].'</span></a></p>';
else if (ForumUser::$forum_user['is_guest'])
	ForumCore::$forum_page['page_post']['posting'] = '<p class="posting">'.sprintf(ForumCore::$lang['Login to post'], '<a href="'.forum_link(ForumCore::$forum_url['login']).'">'.ForumCore::$lang['login'].'</a>', '<a href="'.forum_link(ForumCore::$forum_url['register']).'">'.ForumCore::$lang['register'].'</a>').'</p>';
else if (ForumCore::$cur_topic['closed'] == '1')
	ForumCore::$forum_page['page_post']['posting'] = '<p class="posting">'.ForumCore::$lang['Topic closed info'].'</p>';
else
	ForumCore::$forum_page['page_post']['posting'] = '<p class="posting">'.ForumCore::$lang['No permission'].'</p>';

// Setup main options
ForumCore::$forum_page['main_title'] = ForumCore::$lang['Topic options'];
ForumCore::$forum_page['main_head_options'] = array(
	'rss' => '<span class="feed first-item"><a class="feed" href="'.forum_link(ForumCore::$forum_url['topic_rss'], ForumCore::$id).'">'.ForumCore::$lang['RSS topic feed'].'</a></span>'
);

if (!ForumUser::$forum_user['is_guest'] && ForumCore::$forum_config['o_subscriptions'] == '1')
{
	if (ForumCore::$cur_topic['is_subscribed'])
		ForumCore::$forum_page['main_head_options']['unsubscribe'] = '<span><a class="sub-option" href="'.forum_link(ForumCore::$forum_url['unsubscribe'], array(ForumCore::$id, generate_form_token('unsubscribe'.ForumCore::$id.ForumUser::$forum_user['id']))).'"><em>'.ForumCore::$lang['Unsubscribe'].'</em></a></span>';
	else
		ForumCore::$forum_page['main_head_options']['subscribe'] = '<span><a class="sub-option" href="'.forum_link(ForumCore::$forum_url['subscribe'], array(ForumCore::$id, generate_form_token('subscribe'.ForumCore::$id.ForumUser::$forum_user['id']))).'" title="'.ForumCore::$lang['Subscribe info'].'">'.ForumCore::$lang['Subscribe'].'</a></span>';
}

if (ForumCore::$forum_page['is_admmod'])
{
	ForumCore::$forum_page['main_foot_options'] = array(
		'move' => '<span class="first-item"><a class="mod-option" href="'.forum_link(ForumCore::$forum_url['move'], array(ForumCore::$cur_topic['forum_id'], ForumCore::$id)).'">'.ForumCore::$lang['Move'].'</a></span>',
		'delete' => '<span><a class="mod-option" href="'.forum_link(ForumCore::$forum_url['delete'], ForumCore::$cur_topic['first_post_id']).'">'.ForumCore::$lang['Delete topic'].'</a></span>',
		'close' => ((ForumCore::$cur_topic['closed'] == '1') ? '<span><a class="mod-option" href="'.forum_link(ForumCore::$forum_url['open'], array(ForumCore::$cur_topic['forum_id'], ForumCore::$id, generate_form_token('open'.ForumCore::$id))).'">'.ForumCore::$lang['Open'].'</a></span>' : '<span><a class="mod-option" href="'.forum_link(ForumCore::$forum_url['close'], array(ForumCore::$cur_topic['forum_id'], ForumCore::$id, generate_form_token('close'.ForumCore::$id))).'">'.ForumCore::$lang['Close'].'</a></span>'),
		'sticky' => ((ForumCore::$cur_topic['sticky'] == '1') ? '<span><a class="mod-option" href="'.forum_link(ForumCore::$forum_url['unstick'], array(ForumCore::$cur_topic['forum_id'], ForumCore::$id, generate_form_token('unstick'.ForumCore::$id))).'">'.ForumCore::$lang['Unstick'].'</a></span>' : '<span><a class="mod-option" href="'.forum_link(ForumCore::$forum_url['stick'], array(ForumCore::$cur_topic['forum_id'], ForumCore::$id, generate_form_token('stick'.ForumCore::$id))).'">'.ForumCore::$lang['Stick'].'</a></span>')
	);

	if (ForumCore::$cur_topic['num_replies'] != 0)
		ForumCore::$forum_page['main_foot_options']['moderate_topic'] = '<span><a class="mod-option" href="'.forum_sublink(ForumCore::$forum_url['moderate_topic'], ForumCore::$forum_url['page'], ForumCore::$forum_page['page'], array(ForumCore::$cur_topic['forum_id'], ForumCore::$id)).'">'.ForumCore::$lang['Moderate topic'].'</a></span>';
}

// Setup breadcrumbs
ForumCore::$forum_page['crumbs'] = array(
	array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
	array(ForumCore::$cur_topic['forum_name'], forum_link(ForumCore::$forum_url['forum'], array(ForumCore::$cur_topic['forum_id'], sef_friendly(ForumCore::$cur_topic['forum_name'])))),
	ForumCore::$cur_topic['subject']
);

// Setup main heading
ForumCore::$forum_page['main_title'] = ((ForumCore::$cur_topic['closed'] == '1') ? ForumCore::$lang['Topic closed'].' ' : '').'<a class="permalink" href="'.forum_link(ForumCore::$forum_url['topic'], array(ForumCore::$id, sef_friendly(ForumCore::$cur_topic['subject']))).'" rel="bookmark" title="'.ForumCore::$lang['Permalink topic'].'">'.forum_htmlencode(ForumCore::$cur_topic['subject']).'</a>';

if (ForumCore::$forum_page['num_pages'] > 1)
	ForumCore::$forum_page['main_head_pages'] = sprintf(ForumCore::$lang['Page info'], ForumCore::$forum_page['page'], ForumCore::$forum_page['num_pages']);

// Set HEAD title
ForumCore::$page_title = ForumCore::$cur_topic['subject'];
add_filter( 'pre_get_document_title', function(){
	return ForumCore::$page_title;
}, 999);

($hook = get_hook('vt_pre_header_load')) ? eval($hook) : null;

// Allow indexing if this is a permalink
if (!$pid)
	define('FORUM_ALLOW_INDEX', 1);

define('FORUM_PAGE', 'viewtopic');
require FORUM_ROOT.'header.php';

($hook = get_hook('vt_main_output_start')) ? eval($hook) : null;

// SHORTCODE [pun_content]
add_shortcode('pun_content', function ()
{
	$forum_db = new DBLayer;
?>

	<div id="brd-pagepost-top" class="main-pagepost gen-content">
		<?php echo implode("\n\t", ForumCore::$forum_page['page_post']) ?>
	</div>

	<div class="main-head">
<?php

	if (!empty(ForumCore::$forum_page['main_head_options']))
		echo "\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_head_options']).'</p>'."\n";

?>
		<h2 class="hn"><span><?php echo ForumCore::$forum_page['items_info'] ?></span></h2>
	</div>
	<div id="forum<?php echo ForumCore::$cur_topic['forum_id'] ?>" class="main-content main-topic">
<?php

	if (!defined('FORUM_PARSER_LOADED'))
		require FORUM_ROOT.'include/parser.php';

	ForumCore::$forum_page['item_count'] = 0;	// Keep track of post numbers

	// 1. Retrieve the posts ids
	$query = array(
		'SELECT'	=> 'p.id',
		'FROM'		=> 'posts AS p',
		'WHERE'		=> 'p.topic_id='.ForumCore::$id,
		'ORDER BY'	=> 'p.id',
		'LIMIT'		=> ForumCore::$forum_page['start_from'].','.ForumUser::$forum_user['disp_posts']
	);
	($hook = get_hook('vt_qr_get_posts_id')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$posts_id = array();
	while ($row = $forum_db->fetch_assoc($result)) {
		$posts_id[] = $row['id'];
	}

	if (!empty($posts_id))
	{
		// 2. Retrieve the posts (and their respective poster/online status) by known id`s
		$query = array(
			'SELECT'	=> 'u.email, u.title, u.url, u.location, u.signature, u.email_setting, u.num_posts, u.registered, u.admin_note, u.avatar, u.avatar_width, u.avatar_height, p.id, p.poster AS username, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, g.g_id, g.g_user_title, o.user_id AS is_online',
			'FROM'		=> 'posts AS p',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'users AS u',
					'ON'			=> 'u.id=p.poster_id'
				),
				array(
					'INNER JOIN'	=> 'groups AS g',
					'ON'			=> 'g.g_id=u.group_id'
				),
				array(
					'LEFT JOIN'		=> 'online AS o',
					'ON'			=> '(o.user_id=u.id AND o.user_id!=1 AND o.idle=0)'
				),
			),
			'WHERE'		=> 'p.id IN ('.implode(',', $posts_id).')',
			'ORDER BY'	=> 'p.id'
		);

		($hook = get_hook('vt_qr_get_posts')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$user_data_cache = array();
		while ($cur_post = $forum_db->fetch_assoc($result))
		{
			($hook = get_hook('vt_post_loop_start')) ? eval($hook) : null;

			++ForumCore::$forum_page['item_count'];

			ForumCore::$forum_page['post_ident'] = array();
			ForumCore::$forum_page['author_ident'] = array();
			ForumCore::$forum_page['author_info'] = array();
			ForumCore::$forum_page['post_options'] = array();
			ForumCore::$forum_page['post_contacts'] = array();
			ForumCore::$forum_page['post_actions'] = array();
			ForumCore::$forum_page['message'] = array();

			// Generate the post heading
			ForumCore::$forum_page['post_ident']['num'] = '<span class="post-num">'.forum_number_format(ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']).'</span>';

/*
			if ($cur_post['poster_id'] > 1)
				ForumCore::$forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf((($cur_post['id'] == ForumCore::$cur_topic['first_post_id']) ? ForumCore::$lang['Topic byline'] : ForumCore::$lang['Reply byline']), ((ForumUser::$forum_user['g_view_users'] == '1') ? '<a title="'.sprintf(ForumCore::$lang['Go to profile'], forum_htmlencode($cur_post['username'])).'" href="'.forum_link(ForumCore::$forum_url['user'], $cur_post['poster_id']).'">'.forum_htmlencode($cur_post['username']).'</a>' : '<strong>'.forum_htmlencode($cur_post['username']).'</strong>')).'</span>';
			else
				ForumCore::$forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf((($cur_post['id'] == ForumCore::$cur_topic['first_post_id']) ? ForumCore::$lang['Topic byline'] : ForumCore::$lang['Reply byline']), '<strong>'.forum_htmlencode($cur_post['username']).'</strong>').'</span>';
*/

			ForumCore::$forum_page['post_ident']['link'] = '<span class="post-link"><a class="permalink" rel="bookmark" title="'.ForumCore::$lang['Permalink post'].'" href="'.forum_link(ForumCore::$forum_url['post'], $cur_post['id']).'">'.format_time($cur_post['posted']).'</a></span>';

			if ($cur_post['edited'] != '')
				ForumCore::$forum_page['post_ident']['edited'] = '<span class="post-edit">'.sprintf(ForumCore::$lang['Last edited'], forum_htmlencode($cur_post['edited_by']), format_time($cur_post['edited'])).'</span>';


			($hook = get_hook('vt_row_pre_post_ident_merge')) ? eval($hook) : null;

			if (isset($user_data_cache[$cur_post['poster_id']]['author_ident']))
				ForumCore::$forum_page['author_ident'] = $user_data_cache[$cur_post['poster_id']]['author_ident'];
			else
			{
				// Generate author identification
				if ($cur_post['poster_id'] > 1)
				{
					if (ForumCore::$forum_config['o_avatars'] == '1' && ForumUser::$forum_user['show_avatars'] != '0')
					{
						ForumCore::$forum_page['avatar_markup'] = generate_avatar_markup($cur_post['poster_id'], $cur_post['avatar'], $cur_post['avatar_width'], $cur_post['avatar_height'], $cur_post['username']);

						if (!empty(ForumCore::$forum_page['avatar_markup']))
							ForumCore::$forum_page['author_ident']['avatar'] = '<li class="useravatar">'.ForumCore::$forum_page['avatar_markup'].'</li>';
					}

					ForumCore::$forum_page['author_ident']['username'] = '<li class="username">'.((ForumUser::$forum_user['g_view_users'] == '1') ? '<a title="'.sprintf(ForumCore::$lang['Go to profile'], forum_htmlencode($cur_post['username'])).'" href="'.forum_link(ForumCore::$forum_url['user'], $cur_post['poster_id']).'">'.forum_htmlencode($cur_post['username']).'</a>' : '<strong>'.forum_htmlencode($cur_post['username']).'</strong>').'</li>';
					ForumCore::$forum_page['author_ident']['usertitle'] = '<li class="usertitle"><span>'.get_title($cur_post).'</span></li>';

					if ($cur_post['is_online'] == $cur_post['poster_id'])
						ForumCore::$forum_page['author_ident']['status'] = '<li class="userstatus"><span>'.ForumCore::$lang['Online'].'</span></li>';
					else
						ForumCore::$forum_page['author_ident']['status'] = '<li class="userstatus"><span>'.ForumCore::$lang['Offline'].'</span></li>';
				}
				else
				{
					ForumCore::$forum_page['author_ident']['username'] = '<li class="username"><strong>'.forum_htmlencode($cur_post['username']).'</strong></li>';
					ForumCore::$forum_page['author_ident']['usertitle'] = '<li class="usertitle"><span>'.get_title($cur_post).'</span></li>';
				}
			}

			if (isset($user_data_cache[$cur_post['poster_id']]['author_info']))
				ForumCore::$forum_page['author_info'] = $user_data_cache[$cur_post['poster_id']]['author_info'];
			else
			{
				// Generate author information
				if ($cur_post['poster_id'] > 1)
				{
					if (ForumCore::$forum_config['o_show_user_info'] == '1')
					{
						if ($cur_post['location'] != '')
						{
							if (ForumCore::$forum_config['o_censoring'] == '1')
								$cur_post['location'] = censor_words($cur_post['location']);

							ForumCore::$forum_page['author_info']['from'] = '<li><span>'.ForumCore::$lang['From'].' <strong>'.forum_htmlencode($cur_post['location']).'</strong></span></li>';
						}

						ForumCore::$forum_page['author_info']['registered'] = '<li><span>'.ForumCore::$lang['Registered'].' <strong>'.format_time($cur_post['registered'], 1).'</strong></span></li>';

						if (ForumCore::$forum_config['o_show_post_count'] == '1' || ForumUser::$forum_user['is_admmod'])
							ForumCore::$forum_page['author_info']['posts'] = '<li><span>'.ForumCore::$lang['Posts info'].' <strong>'.forum_number_format($cur_post['num_posts']).'</strong></span></li>';
					}

					if (ForumUser::$forum_user['is_admmod'])
					{
						if ($cur_post['admin_note'] != '')
							ForumCore::$forum_page['author_info']['note'] = '<li><span>'.ForumCore::$lang['Note'].' <strong>'.forum_htmlencode($cur_post['admin_note']).'</strong></span></li>';
					}
				}
			}

			// Generate IP information for moderators/administrators
			if (ForumUser::$forum_user['is_admmod'])
				ForumCore::$forum_page['author_info']['ip'] = '<li><span>'.ForumCore::$lang['IP'].' <a href="'.forum_link(ForumCore::$forum_url['get_host'], $cur_post['id']).'">'.$cur_post['poster_ip'].'</a></span></li>';

			// Generate author contact details
			if (ForumCore::$forum_config['o_show_user_info'] == '1')
			{
				if (isset($user_data_cache[$cur_post['poster_id']]['post_contacts']))
					ForumCore::$forum_page['post_contacts'] = $user_data_cache[$cur_post['poster_id']]['post_contacts'];
				else
				{
					if ($cur_post['poster_id'] > 1)
					{
						if ($cur_post['url'] != '')
							ForumCore::$forum_page['post_contacts']['url'] = '<span class="user-url'.(empty(ForumCore::$forum_page['post_contacts']) ? ' first-item' : '').'"><a class="external" href="'.forum_htmlencode((ForumCore::$forum_config['o_censoring'] == '1') ? censor_words($cur_post['url']) : $cur_post['url']).'">'.sprintf(ForumCore::$lang['Visit website'], '<span>'.sprintf(ForumCore::$lang['User possessive'], forum_htmlencode($cur_post['username'])).'</span>').'</a></span>';
						if ((($cur_post['email_setting'] == '0' && !ForumUser::$forum_user['is_guest']) || ForumUser::$forum_user['is_admmod']) && ForumUser::$forum_user['g_send_email'] == '1')
							ForumCore::$forum_page['post_contacts']['email'] = '<span class="user-email'.(empty(ForumCore::$forum_page['post_contacts']) ? ' first-item' : '').'"><a href="mailto:'.forum_htmlencode($cur_post['email']).'">'.ForumCore::$lang['E-mail'].'<span>&#160;'.forum_htmlencode($cur_post['username']).'</span></a></span>';
						else if ($cur_post['email_setting'] == '1' && !ForumUser::$forum_user['is_guest'] && ForumUser::$forum_user['g_send_email'] == '1')
							ForumCore::$forum_page['post_contacts']['email'] = '<span class="user-email'.(empty(ForumCore::$forum_page['post_contacts']) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['email'], $cur_post['poster_id']).'">'.ForumCore::$lang['E-mail'].'<span>&#160;'.forum_htmlencode($cur_post['username']).'</span></a></span>';
					}
					else
					{
						if ($cur_post['poster_email'] != '' && ForumUser::$forum_user['is_admmod'] && ForumUser::$forum_user['g_send_email'] == '1')
							ForumCore::$forum_page['post_contacts']['email'] = '<span class="user-email'.(empty(ForumCore::$forum_page['post_contacts']) ? ' first-item' : '').'"><a href="mailto:'.forum_htmlencode($cur_post['poster_email']).'">'.ForumCore::$lang['E-mail'].'<span>&#160;'.forum_htmlencode($cur_post['username']).'</span></a></span>';
					}
				}

				($hook = get_hook('vt_row_pre_post_contacts_merge')) ? eval($hook) : null;

				if (!empty(ForumCore::$forum_page['post_contacts']))
					ForumCore::$forum_page['post_options']['contacts'] = '<p class="post-contacts">'.implode(' ', ForumCore::$forum_page['post_contacts']).'</p>';
			}

			// Generate the post options links
			if (!ForumUser::$forum_user['is_guest'])
			{
				ForumCore::$forum_page['post_actions']['report'] = '<span class="report-post'.(empty(ForumCore::$forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['report'], $cur_post['id']).'">'.ForumCore::$lang['Report'].'<span> '.ForumCore::$lang['Post'].' '.forum_number_format(ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']).'</span></a></span>';

				if (!ForumCore::$forum_page['is_admmod'])
				{
					if (ForumCore::$cur_topic['closed'] == '0')
					{
						if ($cur_post['poster_id'] == ForumUser::$forum_user['id'])
						{
							if ((ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']) == 1 && ForumUser::$forum_user['g_delete_topics'] == '1')
								ForumCore::$forum_page['post_actions']['delete'] = '<span class="delete-topic'.(empty(ForumCore::$forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['delete'], ForumCore::$cur_topic['first_post_id']).'">'.ForumCore::$lang['Delete topic'].'</a></span>';
							if ((ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']) > 1 && ForumUser::$forum_user['g_delete_posts'] == '1')
								ForumCore::$forum_page['post_actions']['delete'] = '<span class="delete-post'.(empty(ForumCore::$forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['delete'], $cur_post['id']).'">'.ForumCore::$lang['Delete'].'<span> '.ForumCore::$lang['Post'].' '.forum_number_format(ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']).'</span></a></span>';
							if (ForumUser::$forum_user['g_edit_posts'] == '1')
								ForumCore::$forum_page['post_actions']['edit'] = '<span class="edit-post'.(empty(ForumCore::$forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['edit'], $cur_post['id']).'">'.ForumCore::$lang['Edit'].'<span> '.ForumCore::$lang['Post'].' '.forum_number_format(ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']).'</span></a></span>';
						}

						if ((ForumCore::$cur_topic['post_replies'] == '' && ForumUser::$forum_user['g_post_replies'] == '1') || ForumCore::$cur_topic['post_replies'] == '1')
							ForumCore::$forum_page['post_actions']['quote'] = '<span class="quote-post'.(empty(ForumCore::$forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['quote'], array(ForumCore::$id, $cur_post['id'])).'">'.ForumCore::$lang['Quote'].'<span> '.ForumCore::$lang['Post'].' '.forum_number_format(ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']).'</span></a></span>';
					}
				}
				else
				{
					if ((ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']) == 1)
						ForumCore::$forum_page['post_actions']['delete'] = '<span class="delete-topic'.(empty(ForumCore::$forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['delete'], ForumCore::$cur_topic['first_post_id']).'">'.ForumCore::$lang['Delete topic'].'</a></span>';
					else
						ForumCore::$forum_page['post_actions']['delete'] = '<span class="delete-post'.(empty(ForumCore::$forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['delete'], $cur_post['id']).'">'.ForumCore::$lang['Delete'].'<span> '.ForumCore::$lang['Post'].' '.forum_number_format(ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']).'</span></a></span>';

					ForumCore::$forum_page['post_actions']['edit'] = '<span class="edit-post'.(empty(ForumCore::$forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['edit'], $cur_post['id']).'">'.ForumCore::$lang['Edit'].'<span> '.ForumCore::$lang['Post'].' '.forum_number_format(ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']).'</span></a></span>';
					ForumCore::$forum_page['post_actions']['quote'] = '<span class="quote-post'.(empty(ForumCore::$forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['quote'], array(ForumCore::$id, $cur_post['id'])).'">'.ForumCore::$lang['Quote'].'<span> '.ForumCore::$lang['Post'].' '.forum_number_format(ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']).'</span></a></span>';
				}
			}
			else
			{
				if (ForumCore::$cur_topic['closed'] == '0')
				{
					if ((ForumCore::$cur_topic['post_replies'] == '' && ForumUser::$forum_user['g_post_replies'] == '1') || ForumCore::$cur_topic['post_replies'] == '1')
						ForumCore::$forum_page['post_actions']['quote'] = '<span class="report-post'.(empty(ForumCore::$forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['quote'], array(ForumCore::$id, $cur_post['id'])).'">'.ForumCore::$lang['Quote'].'<span> '.ForumCore::$lang['Post'].' '.forum_number_format(ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']).'</span></a></span>';
				}
			}

			($hook = get_hook('vt_row_pre_post_actions_merge')) ? eval($hook) : null;

			if (!empty(ForumCore::$forum_page['post_actions']))
				ForumCore::$forum_page['post_options']['actions'] = '<p class="post-actions">'.implode(' ', ForumCore::$forum_page['post_actions']).'</p>';

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

			// Do signature parsing/caching
			if ($cur_post['signature'] != '' && ForumUser::$forum_user['show_sig'] != '0' && ForumCore::$forum_config['o_signatures'] == '1')
			{
				if (!isset($signature_cache[$cur_post['poster_id']]))
					$signature_cache[$cur_post['poster_id']] = parse_signature($cur_post['signature']);

				ForumCore::$forum_page['message']['signature'] = '<div class="sig-content"><span class="sig-line"><!-- --></span>'.$signature_cache[$cur_post['poster_id']].'</div>';
			}

			($hook = get_hook('vt_row_pre_display')) ? eval($hook) : null;

			// Do user data caching for the post
			if ($cur_post['poster_id'] > 1 && !isset($user_data_cache[$cur_post['poster_id']]))
			{
				$user_data_cache[$cur_post['poster_id']] = array(
					'author_ident'	=> ForumCore::$forum_page['author_ident'],
					'author_info'	=> ForumCore::$forum_page['author_info'],
					'post_contacts'	=> ForumCore::$forum_page['post_contacts']
				);

				($hook = get_hook('vt_row_add_user_data_cache')) ? eval($hook) : null;
			}

?>
		<div class="<?php echo implode(' ', ForumCore::$forum_page['item_status']) ?>">
			<div id="p<?php echo $cur_post['id'] ?>" class="posthead">
				<h3 class="hn post-ident"><?php echo implode(' ', ForumCore::$forum_page['post_ident']) ?></h3>
			</div>
			<div class="postbody<?php if ($cur_post['is_online'] == $cur_post['poster_id']) echo ' online'; ?>">
				<div class="post-author">
					<ul class="author-ident">
						<?php echo implode("\n\t\t\t\t\t\t", ForumCore::$forum_page['author_ident'])."\n" ?>
					</ul>
					<ul class="author-info">
						<?php echo implode("\n\t\t\t\t\t\t", ForumCore::$forum_page['author_info'])."\n" ?>
					</ul>
				</div>
				<div class="post-entry">
					<h4 id="pc<?php echo $cur_post['id'] ?>" class="entry-title hn"><?php echo ForumCore::$forum_page['item_subject'] ?></h4>
					<div class="entry-content">
						<?php echo implode("\n\t\t\t\t\t\t", ForumCore::$forum_page['message'])."\n" ?>
					</div>
<?php ($hook = get_hook('vt_row_new_post_entry_data')) ? eval($hook) : null; ?>
				</div>
			</div>
<?php if (!empty(ForumCore::$forum_page['post_options'])): ?>
			<div class="postfoot">
				<div class="post-options">
					<?php echo implode("\n\t\t\t\t\t", ForumCore::$forum_page['post_options'])."\n" ?>
				</div>
			</div>
<?php endif; ?>
		</div>
<?php

		}
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

	($hook = get_hook('vt_end')) ? eval($hook) : null;

	// Display quick post if enabled
	if (ForumCore::$forum_config['o_quickpost'] == '1' &&
		!ForumUser::$forum_user['is_guest'] &&
		(ForumCore::$cur_topic['post_replies'] == '1' || (ForumCore::$cur_topic['post_replies'] == '' && ForumUser::$forum_user['g_post_replies'] == '1')) &&
		(ForumCore::$cur_topic['closed'] == '0' || ForumCore::$forum_page['is_admmod']))
	{

	($hook = get_hook('vt_qpost_output_start')) ? eval($hook) : null;

	// Setup form
	ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['new_reply'], ForumCore::$id);
	ForumCore::$forum_page['form_attributes'] = array();

	ForumCore::$forum_page['hidden_fields'] = array(
		//'fid'		=> '<input type="hidden" name="fid" value="'.ForumCore::$cur_topic['forum_id'].'" />',
		'tid'		=> '<input type="hidden" name="tid" value="'.ForumCore::$id.'" />',
		'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
		'form_user'		=> '<input type="hidden" name="form_user" value="'.((!ForumUser::$forum_user['is_guest']) ? forum_htmlencode(ForumUser::$forum_user['username']) : 'Guest').'" />',
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token(admin_url('admin-post.php?action=pun_post')).'" />'
	);

	if (!ForumUser::$forum_user['is_guest'] && ForumCore::$forum_config['o_subscriptions'] == '1' && (ForumUser::$forum_user['auto_notify'] == '1' || ForumCore::$cur_topic['is_subscribed']))
		ForumCore::$forum_page['hidden_fields']['subscribe'] = '<input type="hidden" name="subscribe" value="1" />';

	// Setup help
	ForumCore::$forum_page['main_head_options'] = array();
	if (ForumCore::$forum_config['p_message_bbcode'] == '1')
		ForumCore::$forum_page['text_options']['bbcode'] = '<span'.(empty(ForumCore::$forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link(ForumCore::$forum_url['help'], 'bbcode').'" title="'.sprintf(ForumCore::$lang['Help page'], ForumCore::$lang['BBCode']).'">'.ForumCore::$lang['BBCode'].'</a></span>';
	if (ForumCore::$forum_config['p_message_img_tag'] == '1')
		ForumCore::$forum_page['text_options']['img'] = '<span'.(empty(ForumCore::$forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link(ForumCore::$forum_url['help'], 'img').'" title="'.sprintf(ForumCore::$lang['Help page'], ForumCore::$lang['Images']).'">'.ForumCore::$lang['Images'].'</a></span>';
	if (ForumCore::$forum_config['o_smilies'] == '1')
		ForumCore::$forum_page['text_options']['smilies'] = '<span'.(empty(ForumCore::$forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link(ForumCore::$forum_url['help'], 'smilies').'" title="'.sprintf(ForumCore::$lang['Help page'], ForumCore::$lang['Smilies']).'">'.ForumCore::$lang['Smilies'].'</a></span>';

	($hook = get_hook('vt_quickpost_pre_display')) ? eval($hook) : null;

?>
<div class="main-subhead">
	<h2 class="hn"><span><?php echo ForumCore::$lang['Quick post'] ?></span></h2>
</div>
<div id="brd-qpost" class="main-content main-frm">
<?php if (!empty(ForumCore::$forum_page['text_options'])) echo "\t".'<p class="content-options options">'.sprintf(ForumCore::$lang['You may use'], implode(' ', ForumCore::$forum_page['text_options'])).'</p>'."\n" ?>
	<div id="req-msg" class="req-warn ct-box error-box">
		<p class="important"><?php echo ForumCore::$lang['Required warn'] ?></p>
	</div>

	<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_post') ); ?>">
		<div class="hidden">
			<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['hidden_fields'])."\n" ?>
		</div>
<?php ($hook = get_hook('vt_quickpost_pre_fieldset')) ? eval($hook) : null; ?>
		<fieldset class="frm-group group1">
			<legend class="group-legend"><strong><?php echo ForumCore::$lang['Write message legend'] ?></strong></legend>
<?php ($hook = get_hook('vt_quickpost_pre_message_box')) ? eval($hook) : null; ?>
			<div class="txt-set set1">
				<div class="txt-box textarea required">
					<label for="fld1"><span><?php echo ForumCore::$lang['Write message'] ?></span></label>
					<div class="txt-input"><span class="fld-input"><textarea id="fld1" name="req_message" rows="7" cols="95" required spellcheck="true" ></textarea></span></div>
				</div>
			</div>
<?php ($hook = get_hook('vt_quickpost_pre_fieldset_end')) ? eval($hook) : null; ?>
		</fieldset>
<?php ($hook = get_hook('vt_quickpost_fieldset_end')) ? eval($hook) : null; ?>
		<div class="frm-buttons">
			<span class="submit primary"><input type="submit" name="submit_button" value="<?php echo ForumCore::$lang['Submit'] ?>" /></span>
			<span class="submit hidden"><input type="submit" name="preview" value="<?php echo ForumCore::$lang['Preview'] ?>" /></span>
		</div>
	</form>
</div>
<?php

		($hook = get_hook('vt_quickpost_end')) ? eval($hook) : null;

	}
});

// Increment "num_views" for topic
if (ForumCore::$forum_config['o_topic_views'] == '1')
{
	$query = array(
		'UPDATE'	=> 'topics',
		'SET'		=> 'num_views=num_views+1',
		'WHERE'		=> 'id='.ForumCore::$id,
	);

	($hook = get_hook('vt_qr_increment_num_views')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);
}

$forum_id = ForumCore::$cur_topic['forum_id'];

require FORUM_ROOT.'footer.php';
