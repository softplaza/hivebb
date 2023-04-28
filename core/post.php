<?php
/**
 * Adds a new post to the specified topic or a new topic to the specified forum.
 *
 * @copyright (C) 2023 HiveBB, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package HiveBB
 */
use \HiveBB\ForumCore;
use \HiveBB\ForumUser;
use \HiveBB\DBLayer;

defined( 'ABSPATH' ) OR die();

define('FORUM_SKIP_CSRF_CONFIRM', 1);

require FORUM_ROOT.'include/common.php';

($hook = get_hook('po_start')) ? eval($hook) : null;

if (ForumUser::$forum_user['g_read_board'] == '0')
	message(ForumCore::$lang['No view']);

// Load the post.php language file
ForumCore::add_lang('post');

ForumCore::$tid = isset($_GET['tid']) ? intval($_GET['tid']) : 0;
ForumCore::$fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
if (ForumCore::$tid < 1 && ForumCore::$fid < 1 || ForumCore::$tid > 0 && ForumCore::$fid > 0)
	message(ForumCore::$lang['Bad request']);

$forum_db = new DBLayer;

// Fetch some info about the topic and/or the forum
if (ForumCore::$tid)
{
	$query = array(
		'SELECT'	=> 'f.id, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.subject, t.closed, s.user_id AS is_subscribed',
		'FROM'		=> 'topics AS t',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'forums AS f',
				'ON'			=> 'f.id=t.forum_id'
			),
			array(
				'LEFT JOIN'		=> 'forum_perms AS fp',
				'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.ForumUser::$forum_user['g_id'].')'
			),
			array(
				'LEFT JOIN'		=> 'subscriptions AS s',
				'ON'			=> '(t.id=s.topic_id AND s.user_id='.ForumUser::$forum_user['id'].')'
			)
		),
		'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.ForumCore::$tid
	);

	($hook = get_hook('po_qr_get_topic_forum_info')) ? eval($hook) : null;
}
else
{
	$query = array(
		'SELECT'	=> 'f.id, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics',
		'FROM'		=> 'forums AS f',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'forum_perms AS fp',
				'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.ForumUser::$forum_user['g_id'].')'
			)
		),
		'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.ForumCore::$fid
	);

	($hook = get_hook('po_qr_get_forum_info')) ? eval($hook) : null;
}

$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
ForumCore::$cur_posting = $forum_db->fetch_assoc($result);

if (!ForumCore::$cur_posting)
	message(ForumCore::$lang['Bad request']);

ForumCore::$is_subscribed = ForumCore::$tid && ForumCore::$cur_posting['is_subscribed'];

// Is someone trying to post into a redirect forum?
if (ForumCore::$cur_posting['redirect_url'] != '')
	message(ForumCore::$lang['Bad request']);

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = (ForumCore::$cur_posting['moderators'] != '') ? unserialize(ForumCore::$cur_posting['moderators']) : array();
$forum_page['is_admmod'] = (ForumUser::$forum_user['g_id'] == FORUM_ADMIN || (ForumUser::$forum_user['g_moderator'] == '1' && array_key_exists(ForumUser::$forum_user['username'], $mods_array))) ? true : false;

($hook = get_hook('po_pre_permission_check')) ? eval($hook) : null;

// Do we have permission to post?
if (((ForumCore::$tid && ((ForumCore::$cur_posting['post_replies'] == '' && ForumUser::$forum_user['g_post_replies'] == '0') || ForumCore::$cur_posting['post_replies'] == '0')) ||
	(ForumCore::$fid && ((ForumCore::$cur_posting['post_topics'] == '' && ForumUser::$forum_user['g_post_topics'] == '0') || ForumCore::$cur_posting['post_topics'] == '0')) ||
	(isset(ForumCore::$cur_posting['closed']) && ForumCore::$cur_posting['closed'] == '1')) &&
	!$forum_page['is_admmod'])
	message(ForumCore::$lang['No permission']);

($hook = get_hook('po_posting_location_selected')) ? eval($hook) : null;

define('FORUM_PAGE', 'post');
require FORUM_ROOT.'header.php';

// SHORTCODE [pun_content]
add_shortcode('pun_content', function ()
{
	$forum_db = new DBLayer;

	// Are we quoting someone?
	if (ForumCore::$tid && isset($_GET['qid']))
	{
		$qid = intval($_GET['qid']);
		if ($qid < 1)
			message(ForumCore::$lang['Bad request']);

		// Get the quote and quote poster
		$query = array(
			'SELECT'	=> 'p.poster, p.message',
			'FROM'		=> 'posts AS p',
			'WHERE'		=> 'id='.$qid.' AND topic_id='.ForumCore::$tid
		);

		($hook = get_hook('po_qr_get_quote')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$quote_info = $forum_db->fetch_assoc($result);

		if (!$quote_info)
		{
			message(ForumCore::$lang['Bad request']);
		}

		($hook = get_hook('po_modify_quote_info')) ? eval($hook) : null;

		if (ForumCore::$forum_config['p_message_bbcode'] == '1')
		{
			// If username contains a square bracket, we add "" or '' around it (so we know when it starts and ends)
			if (strpos($quote_info['poster'], '[') !== false || strpos($quote_info['poster'], ']') !== false)
			{
				if (strpos($quote_info['poster'], '\'') !== false)
					$quote_info['poster'] = '"'.$quote_info['poster'].'"';
				else
					$quote_info['poster'] = '\''.$quote_info['poster'].'\'';
			}
			else
			{
				// Get the characters at the start and end of $q_poster
				$ends = utf8_substr($quote_info['poster'], 0, 1).utf8_substr($quote_info['poster'], -1, 1);

				// Deal with quoting "Username" or 'Username' (becomes '"Username"' or "'Username'")
				if ($ends == '\'\'')
					$quote_info['poster'] = '"'.$quote_info['poster'].'"';
				else if ($ends == '""')
					$quote_info['poster'] = '\''.$quote_info['poster'].'\'';
			}

			$forum_page['quote'] = '[quote='.$quote_info['poster'].']'.$quote_info['message'].'[/quote]'."\n";
		}
		else
			$forum_page['quote'] = '> '.$quote_info['poster'].' '.ForumCore::$lang['wrote'].':'."\n\n".'> '.$quote_info['message']."\n";
	}

	// Setup form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
	//$forum_page['form_action'] = (ForumCore::$tid ? forum_link(ForumCore::$forum_url['new_reply'], ForumCore::$tid) : forum_link(ForumCore::$forum_url['new_topic'], ForumCore::$fid));
	$forum_page['form_attributes'] = array();

	$forum_page['hidden_fields'] = array(
		'fid'		=> '<input type="hidden" name="fid" value="'.ForumCore::$fid.'" />',
		'tid'		=> '<input type="hidden" name="tid" value="'.ForumCore::$tid.'" />',
		'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
		'form_user'		=> '<input type="hidden" name="form_user" value="'.((!ForumUser::$forum_user['is_guest']) ? forum_htmlencode(ForumUser::$forum_user['username']) : 'Guest').'" />',
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token( admin_url('admin-post.php?action=pun_post') ).'" />'
	);

	// Setup help
	$forum_page['text_options'] = array();
	if (ForumCore::$forum_config['p_message_bbcode'] == '1')
		$forum_page['text_options']['bbcode'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link(ForumCore::$forum_url['help'], 'bbcode').'" title="'.sprintf(ForumCore::$lang['Help page'], ForumCore::$lang['BBCode']).'">'.ForumCore::$lang['BBCode'].'</a></span>';
	if (ForumCore::$forum_config['p_message_img_tag'] == '1')
		$forum_page['text_options']['img'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link(ForumCore::$forum_url['help'], 'img').'" title="'.sprintf(ForumCore::$lang['Help page'], ForumCore::$lang['Images']).'">'.ForumCore::$lang['Images'].'</a></span>';
	if (ForumCore::$forum_config['o_smilies'] == '1')
		$forum_page['text_options']['smilies'] = '<span'.(empty($forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link(ForumCore::$forum_url['help'], 'smilies').'" title="'.sprintf(ForumCore::$lang['Help page'], ForumCore::$lang['Smilies']).'">'.ForumCore::$lang['Smilies'].'</a></span>';

	// Setup breadcrumbs
	$forum_page['crumbs'][] = array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index']));
	$forum_page['crumbs'][] = array(ForumCore::$cur_posting['forum_name'], forum_link(ForumCore::$forum_url['forum'], array(ForumCore::$cur_posting['id'], sef_friendly(ForumCore::$cur_posting['forum_name']))));
	if (ForumCore::$tid)
		$forum_page['crumbs'][] = array(ForumCore::$cur_posting['subject'], forum_link(ForumCore::$forum_url['topic'], array(ForumCore::$tid, sef_friendly(ForumCore::$cur_posting['subject']))));
	$forum_page['crumbs'][] = ForumCore::$tid ? ForumCore::$lang['Post reply'] : ForumCore::$lang['Post new topic'];

	($hook = get_hook('po_pre_header_load')) ? eval($hook) : null;



	($hook = get_hook('po_main_output_start')) ? eval($hook) : null;

	?>
		<div class="main-head">
			<h2 class="hn"><span><?php echo ForumCore::$tid ? ForumCore::$lang['Post reply'] : ForumCore::$lang['Post new topic'] ?></span></h2>
		</div>
	<?php

	// If preview selected and there are no errors
	if (isset($_POST['preview']) && empty(ForumCore::$errors))
	{
		if (!defined('FORUM_PARSER_LOADED'))
			require FORUM_ROOT.'include/parser.php';

		$forum_page['preview_message'] = parse_message(forum_trim(ForumCore::$post_message), $hide_smilies);

		// Generate the post heading
		$forum_page['post_ident'] = array();
		$forum_page['post_ident']['num'] = '<span class="post-num">#</span>';
		$forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf(((ForumCore::$tid) ? ForumCore::$lang['Reply byline'] : ForumCore::$lang['Topic byline']), '<strong>'.forum_htmlencode(ForumUser::$forum_user['username']).'</strong>').'</span>';
		$forum_page['post_ident']['link'] = '<span class="post-link">'.format_time(time()).'</span>';

		($hook = get_hook('po_preview_pre_display')) ? eval($hook) : null;

	?>
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo ForumCore::$tid ? ForumCore::$lang['Preview reply'] : ForumCore::$lang['Preview new topic'] ?></span></h2>
		</div>
		<div id="post-preview" class="main-content main-frm">
			<div class="post singlepost">
				<div class="posthead">
					<h3 class="hn"><?php echo implode(' ', $forum_page['post_ident']) ?></h3>
	<?php ($hook = get_hook('po_preview_new_post_head_option')) ? eval($hook) : null; ?>
				</div>
				<div class="postbody">
					<div class="post-entry">
						<div class="entry-content">
							<?php echo $forum_page['preview_message']."\n" ?>
						</div>
	<?php ($hook = get_hook('po_preview_new_post_entry_data')) ? eval($hook) : null; ?>
					</div>
				</div>
			</div>
		</div>
	<?php

	}

	?>
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo (ForumCore::$tid) ? ForumCore::$lang['Compose your reply'] : ForumCore::$lang['Compose your topic'] ?></span></h2>
		</div>
		<div id="post-form" class="main-content main-frm">
	<?php

		if (!empty($forum_page['text_options']))
			echo "\t\t".'<p class="ct-options options">'.sprintf(ForumCore::$lang['You may use'], implode(' ', $forum_page['text_options'])).'</p>'."\n";

		// If there were any errors, show them
		if (!empty(ForumCore::$errors))
		{
			$forum_page['errors'] = array();
			foreach (ForumCore::$errors as $cur_error)
				$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

			($hook = get_hook('po_pre_post_errors')) ? eval($hook) : null;

	?>
			<div class="ct-box error-box">
				<h2 class="warn hn"><?php echo ForumCore::$lang['Post errors'] ?></h2>
				<ul class="error-list">
					<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
				</ul>
			</div>
	<?php

		}

	?>
			<div id="req-msg" class="req-warn ct-box error-box">
				<p class="important"><?php echo ForumCore::$lang['Required warn'] ?></p>
			</div>
			<!--<form id="afocus" class="frm-form frm-ctrl-submit" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>"<?php if (!empty($forum_page['form_attributes'])) echo ' '.implode(' ', $forum_page['form_attributes']) ?>>-->
			<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_post') ); ?>">
				<div class="hidden">

					<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
				</div>
	<?php

	if (ForumUser::$forum_user['is_guest'])
	{
		$forum_page['email_form_name'] = (ForumCore::$forum_config['p_force_guest_email'] == '1') ? 'req_email' : 'email';

		($hook = get_hook('po_pre_guest_info_fieldset')) ? eval($hook) : null;

	?>
				<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Guest post legend'] ?></strong></legend>
	<?php ($hook = get_hook('po_pre_guest_username')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
						<div class="sf-box text required">
							<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Guest name'] ?></span></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="req_username" value="<?php if (isset($_POST['req_username'])) echo forum_htmlencode($username); ?>" size="35" maxlength="25" /></span>
						</div>
					</div>
	<?php ($hook = get_hook('po_pre_guest_email')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
						<div class="sf-box text<?php if (ForumCore::$forum_config['p_force_guest_email'] == '1') echo ' required' ?>">
							<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Guest e-mail'] ?></span></label><br />
							<span class="fld-input"><input type="email" id="fld<?php echo $forum_page['fld_count'] ?>" name="<?php echo $forum_page['email_form_name'] ?>" value="<?php if (isset($_POST[$forum_page['email_form_name']])) echo forum_htmlencode($email); ?>" size="35" maxlength="80" <?php if (ForumCore::$forum_config['p_force_guest_email'] == '1') echo 'required' ?> /></span>
						</div>
					</div>
	<?php ($hook = get_hook('po_pre_guest_info_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
	<?php

		($hook = get_hook('po_guest_info_fieldset_end')) ? eval($hook) : null;

		// Reset counters
		$forum_page['group_count'] = $forum_page['item_count'] = 0;
	}

	($hook = get_hook('po_pre_req_info_fieldset')) ? eval($hook) : null;

	?>
				<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Required information'] ?></strong></legend>
	<?php

	if (ForumCore::$fid)
	{
		($hook = get_hook('po_pre_req_subject')) ? eval($hook) : null;

	?>
					<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
						<div class="sf-box text required longtext">
							<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Topic subject'] ?></span></label><br />
							<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="req_subject" value="<?php if (isset($_POST['req_subject'])) echo forum_htmlencode($subject); ?>" size="<?php echo FORUM_SUBJECT_MAXIMUM_LENGTH ?>" maxlength="<?php echo FORUM_SUBJECT_MAXIMUM_LENGTH ?>" required /></span>
						</div>
					</div>
	<?php

	}

	($hook = get_hook('po_pre_post_contents')) ? eval($hook) : null;

	?>
					<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
						<div class="txt-box textarea required">
							<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Write message'] ?></span></label>
							<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" name="req_message" rows="15" cols="95" required spellcheck="true"><?php echo isset($_POST['req_message']) ? forum_htmlencode(ForumCore::$post_message) : (isset($forum_page['quote']) ? forum_htmlencode($forum_page['quote']) : '') ?></textarea></span></div>
						</div>
					</div>
	<?php

	$forum_page['checkboxes'] = array();
	if (ForumCore::$forum_config['o_smilies'] == '1')
		$forum_page['checkboxes']['hide_smilies'] = '<div class="mf-item"><span class="fld-input"><input type="checkbox" id="fld'.(++$forum_page['fld_count']).'" name="hide_smilies" value="1"'.(isset($_POST['hide_smilies']) ? ' checked="checked"' : '').' /></span> <label for="fld'.$forum_page['fld_count'].'">'.ForumCore::$lang['Hide smilies'].'</label></div>';

	// Check/uncheck the checkbox for subscriptions depending on scenario
	if (!ForumUser::$forum_user['is_guest'] && ForumCore::$forum_config['o_subscriptions'] == '1')
	{
		$subscr_checked = false;

		// If it's a preview
		if (isset($_POST['preview']))
			$subscr_checked = isset($_POST['subscribe']) ? true : false;
		// If auto subscribed
		else if (ForumUser::$forum_user['auto_notify'])
			$subscr_checked = true;
		// If already subscribed to the topic
		else if (ForumCore::$is_subscribed)
			$subscr_checked = true;

		$forum_page['checkboxes']['subscribe'] = '<div class="mf-item"><span class="fld-input"><input type="checkbox" id="fld'.(++$forum_page['fld_count']).'" name="subscribe" value="1"'.($subscr_checked ? ' checked="checked"' : '').' /></span> <label for="fld'.$forum_page['fld_count'].'">'.(ForumCore::$is_subscribed ? ForumCore::$lang['Stay subscribed'] : ForumCore::$lang['Subscribe']).'</label></div>';
	}

	($hook = get_hook('po_pre_optional_fieldset')) ? eval($hook) : null;

	if (!empty($forum_page['checkboxes']))
	{

	?>
					<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
						<div class="mf-box checkbox">
							<?php echo implode("\n\t\t\t\t\t", $forum_page['checkboxes'])."\n" ?>
						</div>
	<?php ($hook = get_hook('po_pre_checkbox_fieldset_end')) ? eval($hook) : null; ?>
					</fieldset>
	<?php

	}

	($hook = get_hook('po_pre_req_info_fieldset_end')) ? eval($hook) : null;

	?>
				</fieldset>
	<?php

	($hook = get_hook('po_req_info_fieldset_end')) ? eval($hook) : null;

	?>
				<div class="frm-buttons">
					<span class="submit primary"><input type="submit" name="submit_button" value="<?php echo (ForumCore::$tid) ? ForumCore::$lang['Submit reply'] : ForumCore::$lang['Submit topic'] ?>" /></span>
					<span class="submit hidden"><input type="submit" name="preview" value="<?php echo (ForumCore::$tid) ? ForumCore::$lang['Preview reply'] : ForumCore::$lang['Preview topic'] ?>" /></span>
				</div>
			</form>
		</div>
	<?php

	($hook = get_hook('po_main_output_end')) ? eval($hook) : null;

	// Check if the topic review is to be displayed
	if (ForumCore::$tid && ForumCore::$forum_config['o_topic_review'] != '0')
	{
		if (!defined('FORUM_PARSER_LOADED'))
			require FORUM_ROOT.'include/parser.php';

		// Get the amount of posts in the topic
		$query = array(
			'SELECT'	=> 'count(p.id)',
			'FROM'		=> 'posts AS p',
			'WHERE'		=> 'topic_id='.ForumCore::$tid
		);

		($hook = get_hook('po_topic_review_qr_get_post_count')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$forum_page['total_post_count'] = $forum_db->result($result, 0);

		// Get posts to display in topic review
		$query = array(
			'SELECT'	=> 'p.id, p.poster, p.message, p.hide_smilies, p.posted',
			'FROM'		=> 'posts AS p',
			'WHERE'		=> 'topic_id='.ForumCore::$tid,
			'ORDER BY'	=> 'id DESC',
			'LIMIT'		=> ForumCore::$forum_config['o_topic_review']
		);

		($hook = get_hook('po_topic_review_qr_get_topic_review_posts')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$posts = array();
		while ($cur_post = $forum_db->fetch_assoc($result))
		{
			$posts[] = $cur_post;
		}

	?>
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo ForumCore::$lang['Topic review'] ?></span></h2>
		</div>
		<div id="topic-review" class="main-content main-frm">
	<?php

		$forum_page['item_count'] = 0;
		$forum_page['item_total'] = count($posts);

		foreach ($posts as $cur_post)
		{
			++$forum_page['item_count'];

			$forum_page['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

			// Generate the post heading
			$forum_page['post_ident'] = array();
			$forum_page['post_ident']['num'] = '<span class="post-num">'.forum_number_format($forum_page['total_post_count'] - $forum_page['item_count'] + 1).'</span>';
			$forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf(ForumCore::$lang['Post byline'], '<strong>'.forum_htmlencode($cur_post['poster']).'</strong>').'</span>';
			$forum_page['post_ident']['link'] = '<span class="post-link"><a class="permalink" rel="bookmark" title="'.ForumCore::$lang['Permalink post'].'" href="'.forum_link(ForumCore::$forum_url['post'], $cur_post['id']).'">'.format_time($cur_post['posted']).'</a></span>';

			($hook = get_hook('po_topic_review_row_pre_display')) ? eval($hook) : null;

	?>
			<div class="post<?php if ($forum_page['item_count'] == 1) echo ' firstpost'; ?><?php if ($forum_page['item_total'] == $forum_page['item_count']) echo ' lastpost'; ?>">
				<div class="posthead">
					<h3 class="hn post-ident"><?php echo implode(' ', $forum_page['post_ident']) ?></h3>
	<?php ($hook = get_hook('po_topic_review_new_post_head_option')) ? eval($hook) : null; ?>
				</div>
				<div class="postbody">
					<div class="post-entry">
						<div class="entry-content">
							<?php echo $forum_page['message']."\n" ?>
	<?php ($hook = get_hook('po_topic_review_new_post_entry_data')) ? eval($hook) : null; ?>
						</div>
					</div>
				</div>
			</div>
	<?php

		}

	?>
		</div>
	<?php

	}

	$forum_id = ForumCore::$cur_posting['id'];

	($hook = get_hook('po_end')) ? eval($hook) : null;
});

require FORUM_ROOT.'footer.php';
