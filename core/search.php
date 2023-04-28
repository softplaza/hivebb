<?php
/**
 * Allows users to search the forum based on various criteria.
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

($hook = get_hook('se_start')) ? eval($hook) : null;

// Load the search.php language file
ForumCore::add_lang('search');

// Load the necessary search functions
require FORUM_ROOT.'include/search_functions.php';

if (ForumUser::$forum_user['g_read_board'] == '0')
	message(ForumCore::$lang['No view']);
else if (ForumUser::$forum_user['g_search'] == '0')
	message(ForumCore::$lang['No search permission']);

// If a search_id was supplied
if (isset($_GET['search_id']))
{
	$search_id = intval($_GET['search_id']);
	if ($search_id < 1)
		message(ForumCore::$lang['Bad request']);

	// Generate the query to grab the cached results
	$query = generate_cached_search_query($search_id, $show_as);

	$url_type = ForumCore::$forum_url['search_results'];
}
// We aren't just grabbing a cached search
else if (isset($_GET['action']))
{
	$action = $_GET['action'];

	// Validate action
	if (!validate_search_action($action))
		message(ForumCore::$lang['Bad request']);

	// If it's a regular search (keywords and/or author)
	if ($action == 'search')
	{
		$keywords = (isset($_GET['keywords']) && is_string($_GET['keywords'])) ? forum_trim($_GET['keywords']) : '';
		$author = (isset($_GET['author']) && is_string($_GET['author'])) ? forum_trim($_GET['author']) : '';
		$sort_dir = (isset($_GET['sort_dir'])) ? (($_GET['sort_dir'] == 'DESC') ? 'DESC' : 'ASC') : 'DESC';
		$show_as = (isset($_GET['show_as'])) ? $_GET['show_as'] : 'posts';
		$sort_by = (isset($_GET['sort_by'])) ? intval($_GET['sort_by']) : '';
		$search_in = (!isset($_GET['search_in']) || $_GET['search_in'] == 'all') ? 0 : (($_GET['search_in'] == 'message') ? 1 : -1);
		$forum = (isset($_GET['forum']) && is_array($_GET['forum'])) ? array_map('intval', $_GET['forum']) : array(-1);

		if (preg_match('#^[\*%]+$#', $keywords))
			$keywords = '';

		if (preg_match('#^[\*%]+$#', $author))
			$author = '';

		if (!$keywords && !$author)
			message(ForumCore::$lang['No terms']);

		// Create a cache of the results and redirect the user to the results
		create_search_cache($keywords, $author, $search_in, $forum, $show_as, $sort_by, $sort_dir);
	}
	// Its not a regular search, so its a quicksearch
	else
	{
		$value = null;
		// Get any additional variables for quicksearches
		if ($action == 'show_user_posts' || $action == 'show_user_topics' || $action == 'show_subscriptions' || $action == 'show_forum_subscriptions')
		{
			$value = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
			if ($value < 2)
				message(ForumCore::$lang['Bad request']);
		}
		else if ($action == 'show_recent')
			$value = (isset($_GET['value'])) ? intval($_GET['value']) : 86400;
		else if ($action == 'show_new')
			$value = (isset($_GET['forum'])) ? intval($_GET['forum']) : -1;

		($hook = get_hook('se_additional_quicksearch_variables')) ? eval($hook) : null;

		$search_id = '';

		// Show as
		if ($action == 'show_forum_subscriptions')
			$show_as = 'forums';
		else
			$show_as = 'topics';

		// Generate the query for the search
		$query = generate_action_search_query($action, $value, $search_id, $url_type, $show_as);
	}
}

($hook = get_hook('se_pre_search_query')) ? eval($hook) : null;

// We have the query to get the results, lets get them!
if (isset($query))
{
	// No results?
	if (!$query)
		no_search_results();

	// Work out the settings for pagination
	if (ForumCore::$show_as == 'posts')
		ForumCore::$forum_page['per_page'] = ForumUser::$forum_user['disp_posts'];
	else if (ForumCore::$show_as == 'topics')
		ForumCore::$forum_page['per_page'] = ForumUser::$forum_user['disp_topics'];
	else if (ForumCore::$show_as == 'forums')
		ForumCore::$forum_page['per_page'] = 0;	// Show all

	// We now have a query that will give us our results in $query, lets get the data!
	ForumCore::$num_hits = get_search_results($query);

	($hook = get_hook('se_post_results_fetched')) ? eval($hook) : null;

	// No search results?
	if (ForumCore::$num_hits == 0)
		no_search_results($action);

	//
	// Output the search results
	//

	// Setup breadcrumbs and results header and footer
	ForumCore::$forum_page['crumbs'][] = array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index']));
	$action = (isset($action)) ? $action : null;
	generate_search_crumbs($action);

	// Generate paging links
	if (ForumCore::$show_as == 'posts' || ForumCore::$show_as == 'topics')
		ForumCore::$forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">'.ForumCore::$lang['Pages'].'</span> '.paginate(ForumCore::$forum_page['num_pages'], ForumCore::$forum_page['page'], $url_type, ForumCore::$lang['Paging separator'], $search_id).'</p>';

	// Get topic/forum tracking data
	if (!ForumUser::$forum_user['is_guest'])
		$tracked_topics = get_tracked_topics();

	// Navigation links for header and page numbering for title/meta description
	if (ForumCore::$show_as == 'posts' || ForumCore::$show_as == 'topics')
	{
		if (ForumCore::$forum_page['page'] < ForumCore::$forum_page['num_pages'])
		{
			ForumCore::$forum_page['nav']['last'] = '<link rel="last" href="'.forum_sublink($url_type, ForumCore::$forum_url['page'], ForumCore::$forum_page['num_pages'], $search_id).'" title="'.ForumCore::$lang['Page'].' '.ForumCore::$forum_page['num_pages'].'" />';
			ForumCore::$forum_page['nav']['next'] = '<link rel="next" href="'.forum_sublink($url_type, ForumCore::$forum_url['page'], (ForumCore::$forum_page['page'] + 1), $search_id).'" title="'.ForumCore::$lang['Page'].' '.(ForumCore::$forum_page['page'] + 1).'" />';
		}
		if (ForumCore::$forum_page['page'] > 1)
		{
			ForumCore::$forum_page['nav']['prev'] = '<link rel="prev" href="'.forum_sublink($url_type, ForumCore::$forum_url['page'], (ForumCore::$forum_page['page'] - 1), $search_id).'" title="'.ForumCore::$lang['Page'].' '.(ForumCore::$forum_page['page'] - 1).'" />';
			ForumCore::$forum_page['nav']['first'] = '<link rel="first" href="'.forum_link($url_type, $search_id).'" title="'.ForumCore::$lang['Page'].' 1" />';
		}

		// Setup main heading
		if (ForumCore::$forum_page['num_pages'] > 1)
			ForumCore::$forum_page['main_head_pages'] = sprintf(ForumCore::$lang['Page info'], ForumCore::$forum_page['page'], ForumCore::$forum_page['num_pages']);
	}

	// Setup main options header
	ForumCore::$forum_page['main_title'] = ForumCore::$lang['Search options'];


	($hook = get_hook('se_results_pre_header_load')) ? eval($hook) : null;

	// Define page type
	if (ForumCore::$show_as == 'posts')
		define('FORUM_PAGE', 'searchposts');
	else if (ForumCore::$show_as == 'topics')
		define('FORUM_PAGE', 'searchtopics');
	else
		define('FORUM_PAGE', 'searchforums');

	require FORUM_ROOT.'header.php';

	// SHORTCODE [pun_content]
	add_shortcode('pun_content', function ()
	{
		($hook = get_hook('se_results_output_start')) ? eval($hook) : null;

		if (ForumCore::$show_as == 'topics')
		{
			// Load the forum.php language file
			//require FORUM_ROOT.'lang/'.$forum_user['language'].'/forum.php';
			ForumCore::add_lang('forum');

			ForumCore::$forum_page['item_header'] = array();
			ForumCore::$forum_page['item_header']['subject']['title'] = '<strong class="subject-title">'.ForumCore::$lang['Topics'].'</strong>';
			ForumCore::$forum_page['item_header']['info']['forum'] = '<strong class="info-forum">'.ForumCore::$lang['Forum'].'</strong>';
			ForumCore::$forum_page['item_header']['info']['replies'] = '<strong class="info-replies">'.ForumCore::$lang['replies'].'</strong>';
			ForumCore::$forum_page['item_header']['info']['lastpost'] = '<strong class="info-lastpost">'.ForumCore::$lang['last post'].'</strong>';

			($hook = get_hook('se_results_topics_pre_item_header_output')) ? eval($hook) : null;

?>

	<div class="main-head">
<?php

		if (!empty(ForumCore::$forum_page['main_head_options']))
			echo "\n\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_head_options']).'</p>';

?>
		<h2 class="hn"><span><?php echo ForumCore::$forum_page['items_info'] ?></span></h2>
	</div>
	<div class="main-subhead">
		<p class="item-summary forum-noview"><span><?php printf(ForumCore::$lang['Search subtitle'], implode(' ', ForumCore::$forum_page['item_header']['subject']), implode(', ', ForumCore::$forum_page['item_header']['info'])) ?></span></p>
	</div>
	<div class="main-content main-forum forum-forums">
<?php

		}
		else if (ForumCore::$show_as == 'posts')
		{
			// Load the topic.php language file
			//require FORUM_ROOT.'lang/'.$forum_user['language'].'/topic.php';
			ForumCore::add_lang('topic');

			// Load parser
			if (!defined('FORUM_PARSER_LOADED'))
				require FORUM_ROOT.'include/parser.php';
?>
	<div class="main-head">
<?php

		if (!empty(ForumCore::$forum_page['main_head_options']))
			echo "\n\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_head_options']).'</p>';

?>
		<h2 class="hn"><span><?php echo ForumCore::$forum_page['items_info'] ?></span></h2>
	</div>
	<div class="main-content main-topic">
<?php
		}
		else if (ForumCore::$show_as == 'forums')
		{
			// Load the forum.php language file
			//require FORUM_ROOT.'lang/'.$forum_user['language'].'/index.php';
			ForumCore::add_lang('index');

			ForumCore::$forum_page['cur_category'] = ForumCore::$forum_page['cat_count'] = ForumCore::$forum_page['item_count'] = 0;
		}

		ForumCore::$forum_page['item_count'] = 0;

		// Finally, lets loop through the results and output them
		foreach (ForumCore::$search_set as $cur_set)
		{
			($hook = get_hook('se_results_loop_start')) ? eval($hook) : null;

			++ForumCore::$forum_page['item_count'];

			if (ForumCore::$forum_config['o_censoring'] == '1' && isset($cur_set['subject']))
				$cur_set['subject'] = censor_words($cur_set['subject']);

			if (ForumCore::$show_as == 'posts')
			{
				// Generate the result heading
				ForumCore::$forum_page['post_ident'] = array();
				ForumCore::$forum_page['post_ident']['num'] = '<span class="post-num">'.forum_number_format(ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']).'</span>';
				ForumCore::$forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf((($cur_set['pid'] == $cur_set['first_post_id']) ? ForumCore::$lang['Topic byline'] : ForumCore::$lang['Reply byline']), '<strong>'.forum_htmlencode($cur_set['pposter']).'</strong>').'</span>';
				ForumCore::$forum_page['post_ident']['link'] = '<span class="post-link"><a class="permalink" rel="bookmark" title="'.ForumCore::$lang['Permalink post'].'" href="'.forum_link(ForumCore::$forum_url['post'], $cur_set['pid']).'">'.format_time($cur_set['pposted']).'</a></span>';

				($hook = get_hook('se_results_posts_row_pre_item_ident_merge')) ? eval($hook) : null;

				// Generate the topic title
				ForumCore::$forum_page['item_subject'] = '<a class="permalink" rel="bookmark" title="'.ForumCore::$lang['Permalink topic'].'" href="'.forum_link(ForumCore::$forum_url['topic'], array($cur_set['tid'], sef_friendly($cur_set['subject']))).'">'.sprintf((($cur_set['pid'] == $cur_set['first_post_id']) ? ForumCore::$lang['Topic title'] : ForumCore::$lang['Reply title']), forum_htmlencode($cur_set['subject'])).'</a> <small>'.sprintf(ForumCore::$lang['Search replies'], forum_number_format($cur_set['num_replies']), '<a href="'.forum_link(ForumCore::$forum_url['forum'], array($cur_set['forum_id'], sef_friendly($cur_set['forum_name']))).'">'.forum_htmlencode($cur_set['forum_name']).'</a>').'</small>';

				// Generate author identification
				ForumCore::$forum_page['user_ident'] = ($cur_set['poster_id'] > 1 && ForumUser::$forum_user['g_view_users'] == '1') ? '<strong class="username"><a title="'.sprintf(ForumCore::$lang['Go to profile'], forum_htmlencode($cur_set['pposter'])).'" href="'.forum_link(ForumCore::$forum_url['user'], $cur_set['poster_id']).'">'.forum_htmlencode($cur_set['pposter']).'</a></strong>' : '<strong class="username">'.forum_htmlencode($cur_set['pposter']).'</strong>';

				// Generate the post actions links
				ForumCore::$forum_page['post_actions'] = array();
				ForumCore::$forum_page['post_actions']['forum'] = '<span><a href="'.forum_link(ForumCore::$forum_url['forum'], array($cur_set['forum_id'], sef_friendly($cur_set['forum_name']))).'">'.ForumCore::$lang['Go to forum'].'<span>: '.forum_htmlencode($cur_set['forum_name']).'</span></a></span>';

				if ($cur_set['pid'] != $cur_set['first_post_id'])
					ForumCore::$forum_page['post_actions']['topic'] = '<span><a class="permalink" rel="bookmark" title="'.ForumCore::$lang['Permalink topic'].'" href="'.forum_link(ForumCore::$forum_url['topic'], array($cur_set['tid'], sef_friendly($cur_set['subject']))).'">'.ForumCore::$lang['Go to topic'].'<span>: '.forum_htmlencode($cur_set['subject']).'</span></a></span>';

				ForumCore::$forum_page['post_actions']['post'] = '<span><a class="permalink" rel="bookmark" title="'.ForumCore::$lang['Permalink post'].'" href="'.forum_link(ForumCore::$forum_url['post'], $cur_set['pid']).'">'.ForumCore::$lang['Go to post'].'<span> '.forum_number_format(ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']).'</span></a></span>';

				ForumCore::$forum_page['message'] = parse_message($cur_set['message'], $cur_set['hide_smilies']);

				// Give the post some class
				ForumCore::$forum_page['item_status'] = array(
					'post',
					((ForumCore::$forum_page['item_count'] % 2 != 0) ? 'odd' : 'even' )
				);

				if (ForumCore::$forum_page['item_count'] == 1)
					ForumCore::$forum_page['item_status']['firstpost'] = 'firstpost';

				if ((ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']) == ForumCore::$forum_page['finish_at'])
					ForumCore::$forum_page['item_status']['lastpost'] = 'lastpost';

				if ($cur_set['pid'] == $cur_set['first_post_id'])
					ForumCore::$forum_page['item_status']['topicpost'] = 'topicpost';


				($hook = get_hook('se_results_posts_row_pre_display')) ? eval($hook) : null;

?>
	<div class="<?php echo implode(' ', ForumCore::$forum_page['item_status']) ?> resultpost">
		<div class="posthead">
			<h3 class="hn post-ident"><?php echo implode(' ', ForumCore::$forum_page['post_ident']) ?></h3>
			<h4 class="hn post-title"><span><?php echo ForumCore::$forum_page['item_subject'] ?></span></h4>
		</div>
		<div class="postbody">
			<div class="post-entry">
				<div class="entry-content">
					<?php echo ForumCore::$forum_page['message'] ?>
				</div>
<?php ($hook = get_hook('se_results_posts_row_new_post_entry_data')) ? eval($hook) : null; ?>
			</div>
		</div>
		<div class="postfoot">
			<div class="post-options">
				<p class="post-actions"><?php echo implode(' ', ForumCore::$forum_page['post_actions']) ?></p>
			</div>
		</div>
	</div>
<?php

			}
			else if (ForumCore::$show_as == 'topics')
			{
				// Start from scratch
				ForumCore::$forum_page['item_subject'] = ForumCore::$forum_page['item_body'] = ForumCore::$forum_page['item_status'] = ForumCore::$forum_page['item_nav'] = ForumCore::$forum_page['item_title'] = ForumCore::$forum_page['item_title_status'] = array();

				// Assemble the Topic heading

				// Should we display the dot or not? :)
				if (!ForumUser::$forum_user['is_guest'] && ForumCore::$forum_config['o_show_dot'] == '1' && $cur_set['has_posted'] == ForumUser::$forum_user['id'])
				{
					ForumCore::$forum_page['item_title']['posted'] = '<span class="posted-mark">'.ForumCore::$lang['You posted indicator'].'</span>';
					ForumCore::$forum_page['item_status']['posted'] = 'posted';
				}

				if ($cur_set['sticky'] == '1')
				{
					ForumCore::$forum_page['item_title_status']['sticky'] = '<em class="sticky">'.ForumCore::$lang['Sticky'].'</em>';
					ForumCore::$forum_page['item_status']['sticky'] = 'sticky';
				}

				if ($cur_set['closed'] != '0')
				{
					ForumCore::$forum_page['item_title_status']['closed'] = '<em class="closed">'.ForumCore::$lang['Closed'].'</em>';
					ForumCore::$forum_page['item_status']['closed'] = 'closed';
				}

				($hook = get_hook('se_results_topics_row_pre_item_subject_status_merge')) ? eval($hook) : null;

				if (!empty(ForumCore::$forum_page['item_title_status']))
					ForumCore::$forum_page['item_title']['status'] = '<span class="item-status">'.sprintf(ForumCore::$lang['Item status'], implode(', ', ForumCore::$forum_page['item_title_status'])).'</span>';

				ForumCore::$forum_page['item_title']['link'] = '<a href="'.forum_link(ForumCore::$forum_url['topic'], array($cur_set['tid'], sef_friendly($cur_set['subject']))).'">'.forum_htmlencode($cur_set['subject']).'</a>';

				($hook = get_hook('se_results_topics_row_pre_item_title_merge')) ? eval($hook) : null;

				ForumCore::$forum_page['item_body']['subject']['title'] = '<h3 class="hn"><span class="item-num">'.forum_number_format(ForumCore::$forum_page['start_from'] + ForumCore::$forum_page['item_count']).'</span> '.implode(' ', ForumCore::$forum_page['item_title']).'</h3>';

				ForumCore::$forum_page['item_pages'] = ceil(($cur_set['num_replies'] + 1) / ForumUser::$forum_user['disp_posts']);

				if (ForumCore::$forum_page['item_pages'] > 1)
					ForumCore::$forum_page['item_nav']['pages'] = '<span>'.ForumCore::$lang['Pages'].'&#160;</span>'.paginate(ForumCore::$forum_page['item_pages'], -1, ForumCore::$forum_url['topic'], ForumCore::$lang['Page separator'], array($cur_set['tid'], sef_friendly($cur_set['subject'])));

				// Does this topic contain posts we haven't read? If so, tag it accordingly.
				if (!ForumUser::$forum_user['is_guest'] && $cur_set['last_post'] > ForumUser::$forum_user['last_visit'] && (!isset($tracked_topics['topics'][$cur_set['tid']]) || $tracked_topics['topics'][$cur_set['tid']] < $cur_set['last_post']) && (!isset($tracked_topics['forums'][$cur_set['forum_id']]) || $tracked_topics['forums'][$cur_set['forum_id']] < $cur_set['last_post']))
				{
					ForumCore::$forum_page['item_nav']['new'] = '<em class="item-newposts"><a href="'.forum_link(ForumCore::$forum_url['topic_new_posts'], array($cur_set['tid'], sef_friendly($cur_set['subject']))).'" title="'.ForumCore::$lang['New posts info'].'">'.ForumCore::$lang['New posts'].'</a></em>';
					ForumCore::$forum_page['item_status']['new'] = 'new';
				}

				($hook = get_hook('se_results_topics_row_pre_item_nav_merge')) ? eval($hook) : null;

				ForumCore::$forum_page['item_subject']['starter'] = '<span class="item-starter">'.sprintf(ForumCore::$lang['Topic starter'], forum_htmlencode($cur_set['poster'])).'</span>';

				if (!empty(ForumCore::$forum_page['item_nav']))
					ForumCore::$forum_page['item_subject']['nav'] = '<span class="item-nav">'.sprintf(ForumCore::$lang['Topic navigation'], implode('&#160;&#160;', ForumCore::$forum_page['item_nav'])).'</span>';

				($hook = get_hook('se_results_topics_row_pre_item_subject_merge')) ? eval($hook) : null;

				ForumCore::$forum_page['item_body']['subject']['desc'] = '<p>'.implode(' ', ForumCore::$forum_page['item_subject']).'</p>';

				if (empty(ForumCore::$forum_page['item_status']))
					ForumCore::$forum_page['item_status']['normal'] = 'normal';

				($hook = get_hook('se_results_topics_pre_item_status_merge')) ? eval($hook) : null;

				ForumCore::$forum_page['item_style'] = ((ForumCore::$forum_page['item_count'] % 2 != 0) ? ' odd' : ' even').((ForumCore::$forum_page['item_count'] == 1) ? ' main-first-item' : '').((!empty(ForumCore::$forum_page['item_status'])) ? ' '.implode(' ', ForumCore::$forum_page['item_status']) : '');

				ForumCore::$forum_page['item_body']['info']['forum'] = '<li class="info-forum"><span class="label">'.ForumCore::$lang['Posted in'].'</span><a href="'.forum_link(ForumCore::$forum_url['forum'], array($cur_set['forum_id'], sef_friendly($cur_set['forum_name']))).'">'.$cur_set['forum_name'].'</a></li>';
				ForumCore::$forum_page['item_body']['info']['replies'] = '<li class="info-replies"><strong>'.forum_number_format($cur_set['num_replies']).'</strong> <span class="label">'.(($cur_set['num_replies'] == 1) ? ForumCore::$lang['Reply'] : ForumCore::$lang['Replies']).'</span></li>';
				ForumCore::$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.ForumCore::$lang['Last post'].'</span> <strong><a href="'.forum_link(ForumCore::$forum_url['post'], $cur_set['last_post_id']).'">'.format_time($cur_set['last_post']).'</a></strong> <cite>'.sprintf(ForumCore::$lang['by poster'], forum_htmlencode($cur_set['last_poster'])).'</cite></li>';

				($hook = get_hook('se_results_topics_row_pre_display')) ? eval($hook) : null;

?>
		<div class="main-item<?php echo ForumCore::$forum_page['item_style'] ?>">
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
			else if (ForumCore::$show_as == 'forums')
			{
				if ($cur_set['cid'] != ForumCore::$forum_page['cur_category'])	// A new category since last iteration?
				{
					if (ForumCore::$forum_page['cur_category'] != 0)
						echo "\t".'</div>'."\n";

					++ForumCore::$forum_page['cat_count'];
					ForumCore::$forum_page['item_count'] = 1;

					ForumCore::$forum_page['item_header'] = array();
					ForumCore::$forum_page['item_header']['subject']['title'] = '<strong class="subject-title">'.$lang_index['Forums'].'</strong>';
					ForumCore::$forum_page['item_header']['info']['topics'] = '<strong class="info-topics">'.$lang_index['topics'].'</strong>';
					ForumCore::$forum_page['item_header']['info']['post'] = '<strong class="info-posts">'.$lang_index['posts'].'</strong>';
					ForumCore::$forum_page['item_header']['info']['lastpost'] = '<strong class="info-lastpost">'.$lang_index['last post'].'</strong>';

					($hook = get_hook('se_results_forums_row_pre_cat_head')) ? eval($hook) : null;

					ForumCore::$forum_page['cur_category'] = $cur_set['cid'];

?>
				<div class="main-head">
					<h2 class="hn"><span><?php echo forum_htmlencode($cur_set['cat_name']) ?></span></h2>
				</div>
				<div class="main-subhead">
					<p class="item-summary"><span><?php printf($lang_index['Category subtitle'], implode(' ', ForumCore::$forum_page['item_header']['subject']), implode(', ', ForumCore::$forum_page['item_header']['info'])) ?></span></p>
				</div>
				<div id="category<?php echo ForumCore::$forum_page['cat_count'] ?>" class="main-content main-category">
<?php
				}

				// Reset arrays and globals for each forum
				ForumCore::$forum_page['item_status'] = ForumCore::$forum_page['item_subject'] = ForumCore::$forum_page['item_body'] = ForumCore::$forum_page['item_title'] = array();

				// Is this a redirect forum?
				if ($cur_set['redirect_url'] != '')
				{
					ForumCore::$forum_page['item_body']['subject']['title'] = '<h3 class="hn"><a class="external" href="'.forum_htmlencode($cur_forum['redirect_url']).'" title="'.sprintf($lang_index['Link to'], forum_htmlencode($cur_forum['redirect_url'])).'"><span>'.forum_htmlencode($cur_set['forum_name']).'</span></a></h3>';
					ForumCore::$forum_page['item_status']['redirect'] = 'redirect';

					if ($cur_set['forum_desc'] != '')
						ForumCore::$forum_page['item_subject']['desc'] = $cur_set['forum_desc'];

					ForumCore::$forum_page['item_subject']['redirect'] = '<span>'.$lang_index['External forum'].'</span>';

					($hook = get_hook('se_results_forums_row_redirect_pre_item_subject_merge')) ? eval($hook) : null;

					if (!empty(ForumCore::$forum_page['item_subject']))
						ForumCore::$forum_page['item_body']['subject']['desc'] = '<p>'.implode(' ', ForumCore::$forum_page['item_subject']).'</p>';

					// Forum topic and post count
					ForumCore::$forum_page['item_body']['info']['topics'] = '<li class="info-topics"><span class="label">'.$lang_index['No topic info'].'</span></li>';
					ForumCore::$forum_page['item_body']['info']['posts'] = '<li class="info-posts"><span class="label">'.$lang_index['No post info'].'</span></li>';
					ForumCore::$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.$lang_index['No lastpost info'].'</span></li>';

					($hook = get_hook('se_results_forums_row_redirect_pre_display')) ? eval($hook) : null;
				}
				else
				{
					// Setup the title and link to the forum
					ForumCore::$forum_page['item_title']['title'] = '<a href="'.forum_link(ForumCore::$forum_url['forum'], array($cur_set['fid'], sef_friendly($cur_set['forum_name']))).'"><span>'.forum_htmlencode($cur_set['forum_name']).'</span></a>';

					($hook = get_hook('se_results_forums_row_redirect_pre_item_title_merge')) ? eval($hook) : null;

					ForumCore::$forum_page['item_body']['subject']['title'] = '<h3 class="hn">'.implode(' ', ForumCore::$forum_page['item_title']).'</h3>';

					// Setup the forum description and mod list
					if ($cur_set['forum_desc'] != '')
						ForumCore::$forum_page['item_subject']['desc'] = $cur_set['forum_desc'];

					($hook = get_hook('se_results_forums_row_normal_pre_item_subject_merge')) ? eval($hook) : null;

					if (!empty(ForumCore::$forum_page['item_subject']))
						ForumCore::$forum_page['item_body']['subject']['desc'] = '<p>'.implode(' ', ForumCore::$forum_page['item_subject']).'</p>';

					// Setup forum topics, post count and last post
					ForumCore::$forum_page['item_body']['info']['topics'] = '<li class="info-topics"><strong>'.forum_number_format($cur_set['num_topics']).'</strong> <span class="label">'.(($cur_set['num_topics'] == 1) ? $lang_index['topic'] : $lang_index['topics']).'</span></li>';
					ForumCore::$forum_page['item_body']['info']['posts'] = '<li class="info-posts"><strong>'.forum_number_format($cur_set['num_posts']).'</strong> <span class="label">'.(($cur_set['num_posts'] == 1) ? $lang_index['post'] : $lang_index['posts']).'</span></li>';

					if ($cur_set['last_post'] != '')
						ForumCore::$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.$lang_index['Last post'].'</span> <strong><a href="'.forum_link(ForumCore::$forum_url['post'], $cur_set['last_post_id']).'">'.format_time($cur_set['last_post']).'</a></strong> <cite>'.sprintf($lang_index['Last poster'], forum_htmlencode($cur_set['last_poster'])).'</cite></li>';
					else
						ForumCore::$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><strong>'.ForumCore::$lang['Never'].'</strong></li>';

					($hook = get_hook('se_results_forums_row_normal_pre_display')) ? eval($hook) : null;
				}

				// Generate classes for this forum depending on its status
				ForumCore::$forum_page['item_style'] = ((ForumCore::$forum_page['item_count'] % 2 != 0) ? ' odd' : ' even').((ForumCore::$forum_page['item_count'] == 1) ? ' main-first-item' : '').((!empty(ForumCore::$forum_page['item_status'])) ? ' '.implode(' ', ForumCore::$forum_page['item_status']) : '');

				($hook = get_hook('se_results_forums_row_pre_display')) ? eval($hook) : null;

?>
			<div id="forum<?php echo $cur_set['fid'] ?>" class="main-item<?php echo ForumCore::$forum_page['item_style'] ?>">
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

		($hook = get_hook('se_results_end')) ? eval($hook) : null;

	});

	require FORUM_ROOT.'footer.php';
}
else
{
	//
	// Display the search form
	//

	// Setup form information
	ForumCore::$forum_page['frm-info'] = array(
		'keywords'	=> '<li><span>'.ForumCore::$lang['Keywords info'].'</span></li>',
		'refine'	=> '<li><span>'.ForumCore::$lang['Refine info'].'</span></li>',
		'wildcard'	=> '<li><span>'.ForumCore::$lang['Wildcard info'].'</span></li>'
	);

	if (ForumCore::$forum_config['o_search_all_forums'] == '1' || ForumUser::$forum_user['is_admmod'])
		ForumCore::$forum_page['frm-info']['forums'] = '<li><span>'.ForumCore::$lang['Forum default info'].'</span></li>';
	else
		ForumCore::$forum_page['frm-info']['forums'] = '<li><span>'.ForumCore::$lang['Forum require info'].'</span></li>';

	// Setup sort by options
	ForumCore::$forum_page['frm-sort'] = array(
		'post_time'		=> '<option value="0">'.ForumCore::$lang['Sort by post time'].'</option>',
		'author'		=> '<option value="1">'.ForumCore::$lang['Sort by author'].'</option>',
		'subject'		=> '<option value="2">'.ForumCore::$lang['Sort by subject'].'</option>',
		'forum_name'	=> '<option value="3">'.ForumCore::$lang['Sort by forum'].'</option>'
	);

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		ForumCore::$lang['Search']
	);

	ForumCore::$advanced_search = isset($_GET['advanced']) ? true : false;

	// Show link for advanced form
	if (!ForumCore::$advanced_search)
	{
		ForumCore::$forum_page['main_head_options']['advanced_search'] = '<span'.(empty(ForumCore::$forum_page['main_head_options']) ? ' class="first-item"' : '').'><a href="'.forum_link(ForumCore::$forum_url['search_advanced']).'">'.ForumCore::$lang['Advanced search'].'</a></span>';
	}

	// Setup form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

	($hook = get_hook('se_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'search');
	require FORUM_ROOT.'header.php';

	// SHORTCODE [pun_content]
	add_shortcode('pun_content', function ()
	{
		$forum_db = new DBLayer;

		($hook = get_hook('se_main_output_start')) ? eval($hook) : null;

?>
	<div class="main-head">
<?php

		if (!empty(ForumCore::$forum_page['main_head_options']))
			echo "\n\t\t".'<p class="options">'.implode(' ', ForumCore::$forum_page['main_head_options']).'</p>';

?>
		<h2 class="hn"><span><?php echo ForumCore::$lang['Search heading'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
<?php if (ForumCore::$advanced_search): ?>
		<div class="ct-box info-box">
			<ul class="info-list">
				<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['frm-info'])."\n" ?>
			</ul>
		</div>
<?php endif; ?>
		<form id="afocus" class="frm-form" method="get" accept-charset="utf-8" action="">
			<div class="hidden">
				<input type="hidden" name="action" value="search" />
			</div>
<?php ($hook = get_hook('se_pre_criteria_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Search legend'] ?></strong></legend>
<?php ($hook = get_hook('se_pre_keywords')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Keyword search'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="keywords" size="40" maxlength="100" <?php echo (ForumCore::$advanced_search) ? '' : 'required' ?> /></span>
					</div>
				</div>
<?php ($hook = get_hook('se_pre_author')) ? eval($hook) : null; ?>
<?php if (ForumCore::$advanced_search): ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Author search'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" type="text" name="author" size="40" maxlength="25" /></span>
					</div>
				</div>
<?php ($hook = get_hook('se_pre_search_in')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Search in'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="search_in">
							<option value="all"><?php echo ForumCore::$lang['Message and subject'] ?></option>
							<option value="message"><?php echo ForumCore::$lang['Message only'] ?></option>
							<option value="topic"><?php echo ForumCore::$lang['Topic only'] ?></option>
						</select></span>
					</div>
				</div>
<?php endif; if ((!ForumCore::$advanced_search && (ForumCore::$forum_config['o_search_all_forums'] == '0' && !ForumUser::$forum_user['is_admmod'])) || ForumCore::$advanced_search): ?>
<?php ($hook = get_hook('se_pre_forum_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<legend><span><?php echo ForumCore::$lang['Forum search'] ?> <em><?php echo (ForumCore::$forum_config['o_search_all_forums'] == '1' || ForumUser::$forum_user['is_admmod']) ? ForumCore::$lang['Forum search default'] : ForumCore::$lang['Forum search require'] ?></em></span></legend>
<?php ($hook = get_hook('se_pre_forum_checklist')) ? eval($hook) : null; ?>
					<div class="mf-box">
						<div class="checklist">
<?php

		// Get the list of categories and forums
		$query = array(
			'SELECT'	=> 'c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.redirect_url',
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
			'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.redirect_url IS NULL',
			'ORDER BY'	=> 'c.disp_position, c.id, f.disp_position'
		);

		($hook = get_hook('se_qr_get_cats_and_forums')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$forums = array();
		while ($cur_forum = $forum_db->fetch_assoc($result))
		{
			$forums[] = $cur_forum;
		}

		if (!empty($forums))
		{
			$cur_category = 0;
			foreach ($forums as $cur_forum)
			{
				($hook = get_hook('se_forum_loop_start')) ? eval($hook) : null;

				if ($cur_forum['cid'] != $cur_category)	// A new category since last iteration?
				{
					if ($cur_category)
						echo "\t\t\t\t\t\t\t".'</fieldset>'."\n";

					echo "\t\t\t\t\t\t\t".'<fieldset>'."\n\t\t\t\t\t\t\t\t".'<legend><span>'.forum_htmlencode($cur_forum['cat_name']).':</span></legend>'."\n";
					$cur_category = $cur_forum['cid'];
				}

				echo "\t\t\t\t\t\t\t\t".'<div class="checklist-item"><span class="fld-input"><input type="checkbox" id="fld'.(++ForumCore::$forum_page['fld_count']).'" name="forum[]" value="'.$cur_forum['fid'].'" /></span> <label for="fld'.ForumCore::$forum_page['fld_count'].'">'.forum_htmlencode($cur_forum['forum_name']).'</label></div>'."\n";

				($hook = get_hook('se_forum_loop_end')) ? eval($hook) : null;
			}

			echo "\t\t\t\t\t\t\t".'</fieldset>'."\n";
		}

?>
						</div>
					</div>
<?php ($hook = get_hook('se_pre_forum_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php endif; ?>
<?php ($hook = get_hook('se_forum_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('se_criteria_fieldset_end')) ? eval($hook) : null; ?>
<?php ForumCore::$forum_page['item_count'] = 0; ?>
<?php ($hook = get_hook('se_pre_results_fieldset')) ? eval($hook) : null; ?>
<?php if (ForumCore::$advanced_search): ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Results legend'] ?></strong></legend>
<?php ($hook = get_hook('se_pre_sort_by')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Sort by'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="sort_by">
						<?php echo implode("\n\t\t\t\t\t\t", ForumCore::$forum_page['frm-sort'])."\n" ?>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('se_pre_sort_order_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<legend><span><?php echo ForumCore::$lang['Sort order'] ?></span></legend>
<?php ($hook = get_hook('se_pre_sort_order')) ? eval($hook) : null; ?>
					<div class="mf-box mf-yesno">
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="sort_dir" value="ASC" /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Ascending'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="sort_dir" value="DESC" checked="checked" /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Descending'] ?></label>
						</div>
					</div>
<?php ($hook = get_hook('se_pre_sort_order_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('se_pre_display_choices_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<legend><span><?php echo ForumCore::$lang['Display results'] ?></span></legend>
<?php ($hook = get_hook('se_pre_display_choices')) ? eval($hook) : null; ?>
					<div class="mf-box mf-yesno">
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="show_as" value="topics" /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Show as topics'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="show_as" value="posts" checked="checked" /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Show as posts'] ?></label>
						</div>
<?php ($hook = get_hook('se_new_display_choices')) ? eval($hook) : null; ?>
					</div>
<?php ($hook = get_hook('se_pre_display_choices_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('se_pre_results_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php endif; ($hook = get_hook('se_results_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="search" value="<?php echo ForumCore::$lang['Submit search'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

		($hook = get_hook('se_end')) ? eval($hook) : null;
	});

	require FORUM_ROOT.'footer.php';
}