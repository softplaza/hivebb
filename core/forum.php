<?php
/**
 * Displays a list of the categories/forums that the current user can see, along with some statistics.
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

($hook = get_hook('in_start')) ? eval($hook) : null;

if (ForumUser::$forum_user['g_read_board'] == '0')
	message(ForumCore::$lang['No view']);

// Load the index.php language file
//require FORUM_ROOT.'lang/'.ForumUser::$forum_user['language'].'/index.php';
ForumCore::add_lang('index');

// Setup main heading
ForumCore::$forum_page['main_title'] = forum_htmlencode(ForumCore::$forum_config['o_board_title']);

($hook = get_hook('in_pre_header_load')) ? eval($hook) : null;

define('FORUM_ALLOW_INDEX', 1);
define('FORUM_PAGE', 'index');
require FORUM_ROOT.'header.php';

($hook = get_hook('in_main_output_start')) ? eval($hook) : null;

// SHORTCODE [pun_content]
add_shortcode('pun_content', function ()
{
	$forum_db = new DBLayer;

	// Get list of forums and topics with new posts since last visit
	if (!ForumUser::$forum_user['is_guest'])
	{
		$query = array(
			'SELECT'	=> 't.forum_id, t.id, t.last_post',
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
			'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.last_post>'.ForumUser::$forum_user['last_visit'].' AND t.moved_to IS NULL'
		);

		($hook = get_hook('in_qr_get_new_topics')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$new_topics = array();
		while ($cur_topic = $forum_db->fetch_assoc($result))
			$new_topics[$cur_topic['forum_id']][$cur_topic['id']] = $cur_topic['last_post'];

		$tracked_topics = get_tracked_topics();
	}

	// Print the categories and forums
	$query = array(
		'SELECT'	=> 'c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.forum_desc, f.redirect_url, f.moderators, f.num_topics, f.num_posts, f.last_post, f.last_post_id, f.last_poster',
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
		'WHERE'		=> 'fp.read_forum IS NULL OR fp.read_forum=1',
		'ORDER BY'	=> 'c.disp_position, c.id, f.disp_position'
	);
	($hook = get_hook('in_qr_get_cats_and_forums')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	ForumCore::$forum_page['cur_category'] = ForumCore::$forum_page['cat_count'] = ForumCore::$forum_page['item_count'] = 0;

	while ($cur_forum = $forum_db->fetch_assoc($result))
	{
		($hook = get_hook('in_forum_loop_start')) ? eval($hook) : null;

		++ForumCore::$forum_page['item_count'];

		if ($cur_forum['cid'] != ForumCore::$forum_page['cur_category'])	// A new category since last iteration?
		{
			if (ForumCore::$forum_page['cur_category'] != 0)
				echo "\t".'</div>'."\n";

			++ForumCore::$forum_page['cat_count'];
			ForumCore::$forum_page['item_count'] = 1;

			ForumCore::$forum_page['item_header'] = array();
			ForumCore::$forum_page['item_header']['subject']['title'] = '<strong class="subject-title">'.ForumCore::$lang['Forums'].'</strong>';
			ForumCore::$forum_page['item_header']['info']['topics'] = '<strong class="info-topics">'.ForumCore::$lang['topics'].'</strong>';
			ForumCore::$forum_page['item_header']['info']['post'] = '<strong class="info-posts">'.ForumCore::$lang['posts'].'</strong>';
			ForumCore::$forum_page['item_header']['info']['lastpost'] = '<strong class="info-lastpost">'.ForumCore::$lang['last post'].'</strong>';

			($hook = get_hook('in_forum_pre_cat_head')) ? eval($hook) : null;

			ForumCore::$forum_page['cur_category'] = $cur_forum['cid'];

?>	<div class="main-head">
		<h2 class="hn"><span><?php echo forum_htmlencode($cur_forum['cat_name']) ?></span></h2>
	</div>
	<div class="main-subhead">
		<p class="item-summary"><span><?php printf(ForumCore::$lang['Category subtitle'], implode(' ', ForumCore::$forum_page['item_header']['subject']), implode(', ', ForumCore::$forum_page['item_header']['info'])) ?></span></p>
	</div>
	<div id="category<?php echo ForumCore::$forum_page['cat_count'] ?>" class="main-content main-category">
<?php

		}

		// Reset arrays and globals for each forum
		ForumCore::$forum_page['item_status'] = ForumCore::$forum_page['item_subject'] = ForumCore::$forum_page['item_body'] = ForumCore::$forum_page['item_title'] = array();

		// Is this a redirect forum?
		if ($cur_forum['redirect_url'] != '')
		{
			ForumCore::$forum_page['item_body']['subject']['title'] = '<h3 class="hn"><a class="external" href="'.forum_htmlencode($cur_forum['redirect_url']).'" title="'.sprintf(ForumCore::$lang['Link to'], forum_htmlencode($cur_forum['redirect_url'])).'"><span>'.forum_htmlencode($cur_forum['forum_name']).'</span></a></h3>';
			ForumCore::$forum_page['item_status']['redirect'] = 'redirect';

			if ($cur_forum['forum_desc'] != '')
				ForumCore::$forum_page['item_subject']['desc'] = $cur_forum['forum_desc'];

			ForumCore::$forum_page['item_subject']['redirect'] = '<span>'.ForumCore::$lang['External forum'].'</span>';

			($hook = get_hook('in_redirect_row_pre_item_subject_merge')) ? eval($hook) : null;

			if (!empty(ForumCore::$forum_page['item_subject']))
				ForumCore::$forum_page['item_body']['subject']['desc'] = '<p>'.implode(' ', ForumCore::$forum_page['item_subject']).'</p>';

			// Forum topic and post count
			ForumCore::$forum_page['item_body']['info']['topics'] = '<li class="info-topics"><span class="label">'.ForumCore::$lang['No topic info'].'</span></li>';
			ForumCore::$forum_page['item_body']['info']['posts'] = '<li class="info-posts"><span class="label">'.ForumCore::$lang['No post info'].'</span></li>';
			ForumCore::$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.ForumCore::$lang['No lastpost info'].'</span></li>';

			($hook = get_hook('in_redirect_row_pre_display')) ? eval($hook) : null;
		}
		else
		{
			// Setup the title and link to the forum
			ForumCore::$forum_page['item_title']['title'] = '<a href="'.forum_link(ForumCore::$forum_url['forum'], array($cur_forum['fid'], sef_friendly($cur_forum['forum_name']))).'"><span>'.forum_htmlencode($cur_forum['forum_name']).'</span></a>';

			// Are there new posts since our last visit?
			if (!ForumUser::$forum_user['is_guest'] && $cur_forum['last_post'] > ForumUser::$forum_user['last_visit'] && (empty($tracked_topics['forums'][$cur_forum['fid']]) || $cur_forum['last_post'] > $tracked_topics['forums'][$cur_forum['fid']]))
			{
				// There are new posts in this forum, but have we read all of them already?
				foreach ($new_topics[$cur_forum['fid']] as $check_topic_id => $check_last_post)
				{
					if ((empty($tracked_topics['topics'][$check_topic_id]) || $tracked_topics['topics'][$check_topic_id] < $check_last_post) && (empty($tracked_topics['forums'][$cur_forum['fid']]) || $tracked_topics['forums'][$cur_forum['fid']] < $check_last_post))
					{
						ForumCore::$forum_page['item_status']['new'] = 'new';
						ForumCore::$forum_page['item_title']['status'] = '<small>'.sprintf(ForumCore::$lang['Forum has new'], '<a href="'.forum_link(ForumCore::$forum_url['search_new_results'], $cur_forum['fid']).'" title="'.ForumCore::$lang['New posts title'].'">'.ForumCore::$lang['Forum new posts'].'</a>').'</small>';

						break;
					}
				}
			}

			($hook = get_hook('in_normal_row_pre_item_title_merge')) ? eval($hook) : null;

			ForumCore::$forum_page['item_body']['subject']['title'] = '<h3 class="hn">'.implode(' ', ForumCore::$forum_page['item_title']).'</h3>';


			// Setup the forum description and mod list
			if ($cur_forum['forum_desc'] != '')
				ForumCore::$forum_page['item_subject']['desc'] = $cur_forum['forum_desc'];

			if (ForumCore::$forum_config['o_show_moderators'] == '1' && $cur_forum['moderators'] != '')
			{
				ForumCore::$forum_page['mods_array'] = unserialize($cur_forum['moderators']);
				ForumCore::$forum_page['item_mods'] = array();

				foreach (ForumCore::$forum_page['mods_array'] as $mod_username => $mod_id)
					ForumCore::$forum_page['item_mods'][] = (ForumUser::$forum_user['g_view_users'] == '1') ? '<a href="'.forum_link(ForumCore::$forum_url['user'], $mod_id).'">'.forum_htmlencode($mod_username).'</a>' : forum_htmlencode($mod_username);

				($hook = get_hook('in_row_modify_modlist')) ? eval($hook) : null;

				ForumCore::$forum_page['item_subject']['modlist'] = '<span class="modlist">'.sprintf(ForumCore::$lang['Moderated by'], implode(', ', ForumCore::$forum_page['item_mods'])).'</span>';
			}

			($hook = get_hook('in_normal_row_pre_item_subject_merge')) ? eval($hook) : null;

			if (!empty(ForumCore::$forum_page['item_subject']))
				ForumCore::$forum_page['item_body']['subject']['desc'] = '<p>'.implode(' ', ForumCore::$forum_page['item_subject']).'</p>';


			// Setup forum topics, post count and last post
			ForumCore::$forum_page['item_body']['info']['topics'] = '<li class="info-topics"><strong>'.forum_number_format($cur_forum['num_topics']).'</strong> <span class="label">'.(($cur_forum['num_topics'] == 1) ? ForumCore::$lang['topic'] : ForumCore::$lang['topics']).'</span></li>';
			ForumCore::$forum_page['item_body']['info']['posts'] = '<li class="info-posts"><strong>'.forum_number_format($cur_forum['num_posts']).'</strong> <span class="label">'.(($cur_forum['num_posts'] == 1) ? ForumCore::$lang['post'] : ForumCore::$lang['posts']).'</span></li>';

			if ($cur_forum['last_post'] != '')
				ForumCore::$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.ForumCore::$lang['Last post'].'</span> <strong><a href="'.forum_link(ForumCore::$forum_url['post'], $cur_forum['last_post_id']).'">'.format_time($cur_forum['last_post']).'</a></strong> <cite>'.sprintf(ForumCore::$lang['Last poster'], forum_htmlencode($cur_forum['last_poster'])).'</cite></li>';
			else
				ForumCore::$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><strong>'.ForumCore::$lang['Never'].'</strong></li>';

			($hook = get_hook('in_normal_row_pre_display')) ? eval($hook) : null;
		}

		// Generate classes for this forum depending on its status
		ForumCore::$forum_page['item_style'] = ((ForumCore::$forum_page['item_count'] % 2 != 0) ? ' odd' : ' even').((ForumCore::$forum_page['item_count'] == 1) ? ' main-first-item' : '').((!empty(ForumCore::$forum_page['item_status'])) ? ' '.implode(' ', ForumCore::$forum_page['item_status']) : '');

		($hook = get_hook('in_row_pre_display')) ? eval($hook) : null;

?>		<div id="forum<?php echo $cur_forum['fid'] ?>" class="main-item<?php echo ForumCore::$forum_page['item_style'] ?>">
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
	// Did we output any categories and forums?
	if (ForumCore::$forum_page['cur_category'] > 0)
	{

	?>	</div>
	<?php

	}
	else
	{

?>	<div class="main-head">
		<h2 class="hn"><span><?php echo ForumCore::$lang['Forum message']?></span></h2>
	</div>
	<div class="main-content main-message">
		<p><?php echo ForumCore::$lang['Empty board'] ?></p>
	</div>
<?php

	}

	($hook = get_hook('in_end')) ? eval($hook) : null;


	($hook = get_hook('in_info_output_start')) ? eval($hook) : null;

	if (file_exists(FORUM_CACHE_DIR.'cache_stats.php'))
		include FORUM_CACHE_DIR.'cache_stats.php';

	// Regenerate cache only if the cache is more than 30 minutes old
	if (!defined('FORUM_STATS_LOADED') || $forum_stats['cached'] < (time() - 1800))
	{
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_stats_cache();
		require FORUM_CACHE_DIR.'cache_stats.php';
	}

	$stats_list['no_of_users'] = '<li class="st-users"><span>'.sprintf(ForumCore::$lang['No of users'], '<strong>'.forum_number_format($forum_stats['total_users']).'</strong>').'</span></li>';
	$stats_list['newest_user'] = '<li class="st-users"><span>'.sprintf(ForumCore::$lang['Newest user'], '<strong>'.(ForumUser::$forum_user['g_view_users'] == '1' ? '<a href="'.forum_link(ForumCore::$forum_url['user'], $forum_stats['last_user']['id']).'">'.forum_htmlencode($forum_stats['last_user']['username']).'</a>' : forum_htmlencode($forum_stats['last_user']['username'])).'</strong>').'</span></li>';
	$stats_list['no_of_topics'] = '<li class="st-activity"><span>'.sprintf(ForumCore::$lang['No of topics'], '<strong>'.forum_number_format($forum_stats['total_topics']).'</strong>').'</span></li>';
	$stats_list['no_of_posts'] = '<li class="st-activity"><span>'.sprintf(ForumCore::$lang['No of posts'], '<strong>'.forum_number_format($forum_stats['total_posts']).'</strong>').'</span></li>';

	($hook = get_hook('in_stats_pre_info_output')) ? eval($hook) : null;

?>
<div id="brd-stats" class="gen-content">
	<h2 class="hn"><span><?php echo ForumCore::$lang['Statistics'] ?></span></h2>
	<ul>
		<?php echo implode("\n\t\t", $stats_list)."\n" ?>
	</ul>
</div>
<?php

	($hook = get_hook('in_stats_end')) ? eval($hook) : null;
	($hook = get_hook('in_users_online_start')) ? eval($hook) : null;

	if (ForumCore::$forum_config['o_users_online'] == '1')
	{
		// Fetch users online info and generate strings for output
		$query = array(
			'SELECT'	=> 'o.user_id, o.ident',
			'FROM'		=> 'online AS o',
			'WHERE'		=> 'o.idle=0',
			'ORDER BY'	=> 'o.ident'
		);

		($hook = get_hook('in_users_online_qr_get_online_info')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		ForumCore::$forum_page['num_guests'] = ForumCore::$forum_page['num_users'] = 0;
		$users = array();

		while ($forum_user_online = $forum_db->fetch_assoc($result))
		{
			($hook = get_hook('in_users_online_add_online_user_loop')) ? eval($hook) : null;

			if ($forum_user_online['user_id'] > 1)
			{
				$users[] = (ForumUser::$forum_user['g_view_users'] == '1') ? '<a href="'.forum_link(ForumCore::$forum_url['user'], $forum_user_online['user_id']).'">'.forum_htmlencode($forum_user_online['ident']).'</a>' : forum_htmlencode($forum_user_online['ident']);
				++ForumCore::$forum_page['num_users'];
			}
			else
				++ForumCore::$forum_page['num_guests'];
		}

		ForumCore::$forum_page['online_info'] = array();
		ForumCore::$forum_page['online_info']['guests'] = (ForumCore::$forum_page['num_guests'] == 0) ? ForumCore::$lang['Guests none'] : sprintf(((ForumCore::$forum_page['num_guests'] == 1) ? ForumCore::$lang['Guests single'] : ForumCore::$lang['Guests plural']), forum_number_format(ForumCore::$forum_page['num_guests']));
		ForumCore::$forum_page['online_info']['users'] = (ForumCore::$forum_page['num_users'] == 0) ? ForumCore::$lang['Users none'] : sprintf(((ForumCore::$forum_page['num_users'] == 1) ? ForumCore::$lang['Users single'] : ForumCore::$lang['Users plural']), forum_number_format(ForumCore::$forum_page['num_users']));

		($hook = get_hook('in_users_online_pre_online_info_output')) ? eval($hook) : null;
?>
<div id="brd-online" class="gen-content">
	<h3 class="hn"><span><?php printf(ForumCore::$lang['Currently online'], implode(ForumCore::$lang['Online stats separator'], ForumCore::$forum_page['online_info'])) ?></span></h3>
<?php if (!empty($users)): ?>
	<p><?php echo implode(ForumCore::$lang['Online list separator'], $users) ?></p>
<?php endif; ($hook = get_hook('in_new_online_data')) ? eval($hook) : null; ?>
</div>
<?php

		($hook = get_hook('in_users_online_end')) ? eval($hook) : null;
	}

	($hook = get_hook('in_info_end')) ? eval($hook) : null;

});

require FORUM_ROOT.'footer.php';
