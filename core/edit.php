<?php
/**
 * Edit post page.
 *
 * Modifies the contents of the specified post.
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

($hook = get_hook('ed_start')) ? eval($hook) : null;

if (ForumUser::$forum_user['g_read_board'] == '0')
	message(ForumCore::$lang['No view']);

// Load the post.php language file
ForumCore::add_lang('post');

($hook = get_hook('ed_pre_header_load')) ? eval($hook) : null;

define('FORUM_PAGE', 'postedit');
require FORUM_ROOT.'header.php';

// SHORTCODE [pun_content]
add_shortcode('pun_content', function ()
{
	$forum_db = new DBLayer;

	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	if ($id < 1)
		message(ForumCore::$lang['Bad request']);


	// Fetch some info about the post, the topic and the forum
	$query = array(
		'SELECT'	=> 'f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.id AS tid, t.subject, t.posted, t.first_post_id, t.closed, p.poster, p.poster_id, p.message, p.hide_smilies',
		'FROM'		=> 'posts AS p',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'topics AS t',
				'ON'			=> 't.id=p.topic_id'
			),
			array(
				'INNER JOIN'	=> 'forums AS f',
				'ON'			=> 'f.id=t.forum_id'
			),
			array(
				'LEFT JOIN'		=> 'forum_perms AS fp',
				'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.ForumUser::$forum_user['g_id'].')'
			)
		),
		'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND p.id='.$id
	);

	($hook = get_hook('ed_qr_get_post_info')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$cur_post = $forum_db->fetch_assoc($result);

	if (!$cur_post)
		message(ForumCore::$lang['Bad request']);

	// Sort out who the moderators are and if we are currently a moderator (or an admin)
	$mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
	ForumCore::$forum_page['is_admmod'] = (ForumUser::$forum_user['g_id'] == FORUM_ADMIN || (ForumUser::$forum_user['g_moderator'] == '1' && array_key_exists(ForumUser::$forum_user['username'], $mods_array))) ? true : false;

	($hook = get_hook('ed_pre_permission_check')) ? eval($hook) : null;

	// Do we have permission to edit this post?
	if ((ForumUser::$forum_user['g_edit_posts'] == '0' ||
		$cur_post['poster_id'] != ForumUser::$forum_user['id'] ||
		$cur_post['closed'] == '1') &&
		!ForumCore::$forum_page['is_admmod'])
		message(ForumCore::$lang['No permission']);


	$can_edit_subject = $id == $cur_post['first_post_id'];

	($hook = get_hook('ed_post_selected')) ? eval($hook) : null;


	// Start with a clean slate
	$errors = array();

	if (isset($_POST['form_sent']))
	{
		($hook = get_hook('ed_form_submitted')) ? eval($hook) : null;

		// If it is a topic it must contain a subject
		if ($can_edit_subject)
		{
			$subject = forum_trim($_POST['req_subject']);

			if ($subject == '')
				$errors[] = ForumCore::$lang['No subject'];
			else if (utf8_strlen($subject) > FORUM_SUBJECT_MAXIMUM_LENGTH)
				$errors[] = sprintf(ForumCore::$lang['Too long subject'], FORUM_SUBJECT_MAXIMUM_LENGTH);
			else if (ForumCore::$forum_config['p_subject_all_caps'] == '0' && check_is_all_caps($subject) && !ForumCore::$forum_page['is_admmod'])
				$subject = utf8_ucwords(utf8_strtolower($subject));
		}

		// Clean up message from POST
		$message = forum_linebreaks(forum_trim($_POST['req_message']));

		if (strlen($message) > FORUM_MAX_POSTSIZE_BYTES)
			$errors[] = sprintf(ForumCore::$lang['Too long message'], forum_number_format(strlen($message)), forum_number_format(FORUM_MAX_POSTSIZE_BYTES));
		else if (ForumCore::$forum_config['p_message_all_caps'] == '0' && check_is_all_caps($message) && !ForumCore::$forum_page['is_admmod'])
			$message = utf8_ucwords(utf8_strtolower($message));

		// Validate BBCode syntax
		if (ForumCore::$forum_config['p_message_bbcode'] == '1' || ForumCore::$forum_config['o_make_links'] == '1')
		{
			if (!defined('FORUM_PARSER_LOADED'))
				require FORUM_ROOT.'include/parser.php';

			$message = preparse_bbcode($message, $errors);
		}

		if ($message == '')
			$errors[] = ForumCore::$lang['No message'];

		$hide_smilies = isset($_POST['hide_smilies']) ? 1 : 0;

		($hook = get_hook('ed_end_validation')) ? eval($hook) : null;

		// Did everything go according to plan?
		if (empty($errors) && !isset($_POST['preview']))
		{
			($hook = get_hook('ed_pre_post_edited')) ? eval($hook) : null;

			if (!defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
				require FORUM_ROOT.'include/search_idx.php';

			if ($can_edit_subject)
			{
				// Update the topic and any redirect topics
				$query = array(
					'UPDATE'	=> 'topics',
					'SET'		=> 'subject=\''.$forum_db->escape($subject).'\'',
					'WHERE'		=> 'id='.$cur_post['tid'].' OR moved_to='.$cur_post['tid']
				);

				($hook = get_hook('ed_qr_update_subject')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				// We changed the subject, so we need to take that into account when we update the search words
				update_search_index('edit', $id, $message, $subject);
			}
			else
				update_search_index('edit', $id, $message);

			// Update the post
			$query = array(
				'UPDATE'	=> 'posts',
				'SET'		=> 'message=\''.$forum_db->escape($message).'\', hide_smilies=\''.$hide_smilies.'\'',
				'WHERE'		=> 'id='.$id
			);

			if (!isset($_POST['silent']) || !ForumCore::$forum_page['is_admmod'])
				$query['SET'] .= ', edited='.time().', edited_by=\''.$forum_db->escape(ForumUser::$forum_user['username']).'\'';

			($hook = get_hook('ed_qr_update_post')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			($hook = get_hook('ed_pre_redirect')) ? eval($hook) : null;

			redirect(forum_link(ForumCore::$forum_url['post'], $id), ForumCore::$lang['Edit redirect']);
		}
	}

	// Setup error messages
	if (!empty($errors))
	{
		ForumCore::$forum_page['errors'] = array();

		foreach ($errors as $cur_error)
			ForumCore::$forum_page['errors'][] = '<li><span>'.$cur_error.'</span></li>';
	}

	// Setup form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;
	ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['edit'], $id);
	ForumCore::$forum_page['form_attributes'] = array();

	ForumCore::$forum_page['hidden_fields'] = array(
		'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token(ForumCore::$forum_page['form_action']).'" />'
	);

	// Setup help
	ForumCore::$forum_page['main_head_options'] = array();
	if (ForumCore::$forum_config['p_message_bbcode'] == '1')
		ForumCore::$forum_page['text_options']['bbcode'] = '<span'.(empty(ForumCore::$forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link(ForumCore::$forum_url['help'], 'bbcode').'" title="'.sprintf(ForumCore::$lang['Help page'], ForumCore::$lang['BBCode']).'">'.ForumCore::$lang['BBCode'].'</a></span>';
	if (ForumCore::$forum_config['p_message_img_tag'] == '1')
		ForumCore::$forum_page['text_options']['img'] = '<span'.(empty(ForumCore::$forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link(ForumCore::$forum_url['help'], 'img').'" title="'.sprintf(ForumCore::$lang['Help page'], ForumCore::$lang['Images']).'">'.ForumCore::$lang['Images'].'</a></span>';
	if (ForumCore::$forum_config['o_smilies'] == '1')
		ForumCore::$forum_page['text_options']['smilies'] = '<span'.(empty(ForumCore::$forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link(ForumCore::$forum_url['help'], 'smilies').'" title="'.sprintf(ForumCore::$lang['Help page'], ForumCore::$lang['Smilies']).'">'.ForumCore::$lang['Smilies'].'</a></span>';


	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array($cur_post['forum_name'], forum_link(ForumCore::$forum_url['forum'], array($cur_post['fid'], sef_friendly($cur_post['forum_name'])))),
		array($cur_post['subject'], forum_link(ForumCore::$forum_url['topic'], array($cur_post['tid'], sef_friendly($cur_post['subject'])))),
		(($id == $cur_post['first_post_id']) ? ForumCore::$lang['Edit topic'] : ForumCore::$lang['Edit reply'])
	);

	($hook = get_hook('ed_main_output_start')) ? eval($hook) : null;

	?>
		<div class="main-head">
			<h2 class="hn"><span><?php echo ($id == $cur_post['first_post_id']) ? ForumCore::$lang['Edit topic'] : ForumCore::$lang['Edit reply'] ?></span></h2>
		</div>
	<?php

	// If preview selected and there are no errors
	if (isset($_POST['preview']) && empty(ForumCore::$forum_page['errors']))
	{
		if (!defined('FORUM_PARSER_LOADED'))
			require FORUM_ROOT.'include/parser.php';

		// Generate the post heading
		ForumCore::$forum_page['post_ident'] = array();
		ForumCore::$forum_page['post_ident']['num'] = '<span class="post-num">#</span>';
		ForumCore::$forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf((($id == $cur_post['first_post_id']) ? ForumCore::$lang['Topic byline'] : ForumCore::$lang['Reply byline']), '<strong>'.forum_htmlencode($cur_post['poster']).'</strong>').'</span>';
		ForumCore::$forum_page['post_ident']['link'] = '<span class="post-link">'.format_time(time()).'</span>';

		ForumCore::$forum_page['preview_message'] = parse_message($message, $hide_smilies);

		($hook = get_hook('ed_preview_pre_display')) ? eval($hook) : null;

	?>
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo $id == $cur_post['first_post_id'] ? ForumCore::$lang['Preview edited topic'] : ForumCore::$lang['Preview edited reply'] ?></span></h2>
		</div>
		<div id="post-preview" class="main-content main-frm">
			<div class="post singlepost">
				<div class="posthead">
					<h3 class="hn"><?php echo implode(' ', ForumCore::$forum_page['post_ident']) ?></h3>
	<?php ($hook = get_hook('ed_preview_new_post_head_option')) ? eval($hook) : null; ?>
				</div>
				<div class="postbody">
					<div class="post-entry">
						<div class="entry-content">
							<?php echo ForumCore::$forum_page['preview_message']."\n" ?>
						</div>
	<?php ($hook = get_hook('ed_preview_new_post_entry_data')) ? eval($hook) : null; ?>
					</div>
				</div>
			</div>
		</div>
	<?php

	}

	?>
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo ($id != $cur_post['first_post_id']) ? ForumCore::$lang['Compose edited reply'] : ForumCore::$lang['Compose edited topic'] ?></span></h2>
		</div>
		<div id="post-form" class="main-content main-frm">
	<?php

		if (!empty(ForumCore::$forum_page['text_options']))
			echo "\t\t".'<p class="ct-options options">'.sprintf(ForumCore::$lang['You may use'], implode(' ', ForumCore::$forum_page['text_options'])).'</p>'."\n";

	// If there were any errors, show them
	if (isset(ForumCore::$forum_page['errors']))
	{

	?>
			<div class="ct-box error-box">
				<h2 class="warn hn"><span><?php echo ForumCore::$lang['Post errors'] ?></span></h2>
				<ul class="error-list">
					<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['errors'])."\n" ?>
				</ul>
			</div>
	<?php

	}

	?>
			<div id="req-msg" class="req-warn ct-box error-box">
				<p class="important"><?php echo ForumCore::$lang['Required warn'] ?></p>
			</div>
			<form id="afocus" class="frm-form frm-ctrl-submit" method="post" accept-charset="utf-8" action="<?php echo ForumCore::$forum_page['form_action'] ?>"<?php if (!empty(ForumCore::$forum_page['form_attributes'])) echo ' '.implode(' ', ForumCore::$forum_page['form_attributes']) ?>>
				<div class="hidden">
					<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['hidden_fields'])."\n" ?>
				</div>
	<?php ($hook = get_hook('ed_pre_main_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Edit post legend'] ?></strong></legend>
	<?php ($hook = get_hook('ed_pre_subject')) ? eval($hook) : null; ?>
	<?php if ($can_edit_subject): ?>				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text required">
							<label for="fld<?php echo ++ ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Topic subject'] ?></span></label><br />
							<span class="fld-input"><input id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" type="text" name="req_subject" size="<?php echo FORUM_SUBJECT_MAXIMUM_LENGTH ?>" maxlength="<?php echo FORUM_SUBJECT_MAXIMUM_LENGTH ?>" value="<?php echo forum_htmlencode(isset($_POST['req_subject']) ? $_POST['req_subject'] : $cur_post['subject']) ?>" required /></span>
						</div>
					</div>
	<?php endif; ($hook = get_hook('ed_pre_message_box')) ? eval($hook) : null; ?>				<div class="txt-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="txt-box textarea required">
							<label for="fld<?php echo ++ ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Write message'] ?></span></label>
							<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="req_message" rows="15" cols="95" required spellcheck="true"><?php echo forum_htmlencode(isset($_POST['req_message']) ? $message : $cur_post['message']) ?></textarea></span></div>
						</div>
					</div>
	<?php

	ForumCore::$forum_page['checkboxes'] = array();
	if (ForumCore::$forum_config['o_smilies'] == '1')
	{
		if (isset($_POST['hide_smilies']) || $cur_post['hide_smilies'] == '1')
			ForumCore::$forum_page['checkboxes']['hide_smilies'] = '<div class="mf-item"><span class="fld-input"><input type="checkbox" id="fld'.(++ForumCore::$forum_page['fld_count']).'" name="hide_smilies" value="1" checked="checked" /></span> <label for="fld'.ForumCore::$forum_page['fld_count'].'">'.ForumCore::$lang['Hide smilies'].'</label></div>';
		else
			ForumCore::$forum_page['checkboxes']['hide_smilies'] = '<div class="mf-item"><span class="fld-input"><input type="checkbox" id="fld'.(++ForumCore::$forum_page['fld_count']).'" name="hide_smilies" value="1" /></span> <label for="fld'.ForumCore::$forum_page['fld_count'].'">'.ForumCore::$lang['Hide smilies'].'</label></div>';
	}

	if (ForumCore::$forum_page['is_admmod'])
	{
		if ((isset($_POST['form_sent']) && isset($_POST['silent'])) || !isset($_POST['form_sent']))
			ForumCore::$forum_page['checkboxes']['silent'] = '<div class="mf-item"><span class="fld-input"><input type="checkbox" id="fld'.(++ForumCore::$forum_page['fld_count']).'" name="silent" value="1" checked="checked" /></span> <label for="fld'.ForumCore::$forum_page['fld_count'].'">'.ForumCore::$lang['Silent edit'].'</label></div>';
		else
			ForumCore::$forum_page['checkboxes']['silent'] = '<div class="mf-item"><span class="fld-input"><input type="checkbox" id="fld'.(++ForumCore::$forum_page['fld_count']).'" name="silent" value="1" /></span> <label for="fld'.ForumCore::$forum_page['fld_count'].'">'.ForumCore::$lang['Silent edit'].'</label></div>';
	}

	($hook = get_hook('ed_pre_checkbox_display')) ? eval($hook) : null;

	if (!empty(ForumCore::$forum_page['checkboxes']))
	{

	?>
					<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="mf-box checkbox">
							<?php echo implode("\n\t\t\t\t\t", ForumCore::$forum_page['checkboxes'])."\n" ?>
						</div>
	<?php ($hook = get_hook('ed_pre_checkbox_fieldset_end')) ? eval($hook) : null; ?>
					</fieldset>
	<?php

	}

	($hook = get_hook('ed_pre_main_fieldset_end')) ? eval($hook) : null;

	?>
				</fieldset>
	<?php

	($hook = get_hook('ed_main_fieldset_end')) ? eval($hook) : null;

	?>
				<div class="frm-buttons">
					<span class="submit primary"><input type="submit" name="submit_button" value="<?php echo ($id != $cur_post['first_post_id']) ? ForumCore::$lang['Submit reply'] : ForumCore::$lang['Submit topic'] ?>" /></span>
					<span class="submit"><input type="submit" name="preview" value="<?php echo ($id != $cur_post['first_post_id']) ? ForumCore::$lang['Preview reply'] : ForumCore::$lang['Preview topic'] ?>" /></span>
				</div>
			</form>
		</div>
	<?php

	$forum_id = $cur_post['fid'];

	($hook = get_hook('ed_end')) ? eval($hook) : null;
});

require FORUM_ROOT.'footer.php';
