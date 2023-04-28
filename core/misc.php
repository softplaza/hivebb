<?php
/**
 * Provides various features for forum users (ie: display rules, send emails through the forum, mark a forum as read, etc).
 *
 * @copyright (C) 2023 HiveBB, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package HiveBB
 */

use \HiveBB\ForumCore;
use \HiveBB\ForumUser;
use \HiveBB\DBLayer;

defined( 'ABSPATH' ) OR die();

if (isset($_GET['action']))
	define('FORUM_QUIET_VISIT', 1);

require FORUM_ROOT.'include/common.php';

($hook = get_hook('mi_start')) ? eval($hook) : null;

// Load the misc.php language file
ForumCore::add_lang('misc');

$forum_db = new DBLayer;

$action = isset($_GET['action']) ? $_GET['action'] : null;

// Show the forum rules?
if ($action == 'rules')
{
	if (ForumCore::$forum_config['o_rules'] == '0' || (ForumUser::$forum_user['is_guest'] && ForumUser::$forum_user['g_read_board'] == '0' && ForumCore::$forum_config['o_regs_allow'] == '0'))
		message(ForumCore::$lang['Bad request']);

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		ForumCore::$lang['Rules']
	);

	($hook = get_hook('mi_rules_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'rules');
	require FORUM_ROOT.'header.php';

	// SHORTCODE [pun_content]
	add_shortcode('pun_content', function ()
	{
		($hook = get_hook('mi_rules_output_start')) ? eval($hook) : null;

?>
	<div class="main-head">
		<h2 class="hn"><span><?php echo ForumCore::$lang['Rules'] ?></span></h2>
	</div>

	<div class="main-content main-frm">
		<div id="rules-content" class="ct-box user-box">
			<?php echo ForumCore::$forum_config['o_rules_message']."\n" ?>
		</div>
	</div>
<?php

		($hook = get_hook('mi_rules_end')) ? eval($hook) : null;

	});

	require FORUM_ROOT.'footer.php';
}


// Mark all topics/posts as read?
else if ($action == 'markread')
{
	if (ForumUser::$forum_user['is_guest'])
		message(ForumCore::$lang['No permission']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('markread'.ForumUser::$forum_user['id'])))
		csrf_confirm_form();

	($hook = get_hook('mi_markread_selected')) ? eval($hook) : null;

	$query = array(
		'UPDATE'	=> 'users',
		'SET'		=> 'last_visit='.ForumUser::$forum_user['logged'],
		'WHERE'		=> 'id='.ForumUser::$forum_user['id']
	);

	($hook = get_hook('mi_markread_qr_update_last_visit')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	// Reset tracked topics
	set_tracked_topics(null);

	//$forum_flash->add_info(ForumCore::$lang['Mark read redirect']);

	($hook = get_hook('mi_markread_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link(ForumCore::$forum_url['index']), ForumCore::$lang['Mark read redirect']);
}


// Mark the topics/posts in a forum as read?
else if ($action == 'markforumread')
{
	if (ForumUser::$forum_user['is_guest'])
		message(ForumCore::$lang['No permission']);

	$fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
	if ($fid < 1)
		message(ForumCore::$lang['Bad request']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('markforumread'.$fid.ForumUser::$forum_user['id'])))
		csrf_confirm_form();

	($hook = get_hook('mi_markforumread_selected')) ? eval($hook) : null;

	// Fetch some info about the forum
	$query = array(
		'SELECT'	=> 'f.forum_name',
		'FROM'		=> 'forums AS f',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'forum_perms AS fp',
				'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.ForumUser::$forum_user['g_id'].')'
			)
		),
		'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$fid
	);

	($hook = get_hook('mi_markforumread_qr_get_forum_info')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$forum_name = $forum_db->result($result);

	if (!$forum_name)
	{
		message(ForumCore::$lang['Bad request']);
	}

	$tracked_topics = get_tracked_topics();
	$tracked_topics['forums'][$fid] = time();
	set_tracked_topics($tracked_topics);

	//$forum_flash->add_info(ForumCore::$lang['Mark forum read redirect']);

	($hook = get_hook('mi_markforumread_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link(ForumCore::$forum_url['forum'], array($fid, sef_friendly($forum_name))), ForumCore::$lang['Mark forum read redirect']);
}

// OpenSearch plugin?
else if ($action == 'opensearch')
{
	// Send XML/no cache headers
	header('Content-Type: text/xml; charset=utf-8');
	header('Expires: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');

	echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
	echo '<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/" xmlns:moz="http://www.mozilla.org/2006/browser/search/">'."\n";
	echo "\t".'<ShortName>'.forum_htmlencode(ForumCore::$forum_config['o_board_title']).'</ShortName>'."\n";
	echo "\t".'<Description>'.forum_htmlencode(ForumCore::$forum_config['o_board_desc']).'</Description>'."\n";
	echo "\t".'<InputEncoding>utf-8</InputEncoding>'."\n";
	echo "\t".'<OutputEncoding>utf-8</OutputEncoding>'."\n";
	echo "\t".'<Image width="16" height="16" type="image/x-icon">'.$base_url.'/favicon.ico</Image>'."\n";
	echo "\t".'<Url type="text/html" method="get" template="'.$base_url.'/search.php?action=search&amp;source=opensearch&amp;keywords={searchTerms}"/>'."\n";
	echo "\t".'<Url type="application/opensearchdescription+xml" rel="self" template="'.forum_link(ForumCore::$forum_url['opensearch']).'"/>'."\n";
	echo "\t".'<Contact>'.forum_htmlencode(ForumCore::$forum_config['o_admin_email']).'</Contact>'."\n";

	if (ForumCore::$forum_config['o_show_version'] == '1')
		echo "\t".'<Attribution>HiveBB '.ForumCore::$forum_config['o_cur_version'].'</Attribution>'."\n";
	else
		echo "\t".'<Attribution>HiveBB</Attribution>'."\n";

	echo "\t".'<moz:SearchForm>'.forum_link(ForumCore::$forum_url['search']).'</moz:SearchForm>'."\n";
	echo '</OpenSearchDescription>'."\n";

	exit;
}


// Send form e-mail?
else if (isset($_GET['email']))
{
	if (ForumUser::$forum_user['is_guest'] || ForumUser::$forum_user['g_send_email'] == '0')
		message(ForumCore::$lang['No permission']);

	$recipient_id = intval($_GET['email']);

	if ($recipient_id < 2)
		message(ForumCore::$lang['Bad request']);

	($hook = get_hook('mi_email_selected')) ? eval($hook) : null;

	// User pressed the cancel button
	if (isset($_POST['cancel']))
		redirect(forum_htmlencode($_POST['redirect_url']), ForumCore::$lang['Cancel redirect']);

	$query = array(
		'SELECT'	=> 'u.username, u.email, u.email_setting',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.id='.$recipient_id
	);

	($hook = get_hook('mi_email_qr_get_form_email_data')) ? eval($hook) : null;

	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$recipient_info = $forum_db->fetch_assoc($result);

	if (!$recipient_info)
	{
		message(ForumCore::$lang['Bad request']);
	}

	if ($recipient_info['email_setting'] == 2 && !ForumUser::$forum_user['is_admmod'])
		message(ForumCore::$lang['Form e-mail disabled']);

	if ($recipient_info['email'] == '')
		message(ForumCore::$lang['Bad request']);

	if (isset($_POST['form_sent']))
	{
		($hook = get_hook('mi_email_form_submitted')) ? eval($hook) : null;

		// Clean up message and subject from POST
		$subject = forum_trim($_POST['req_subject']);
		$message = forum_trim($_POST['req_message']);

		if ($subject == '')
			ForumCore::$errors[] = ForumCore::$lang['No e-mail subject'];
		else if (utf8_strlen($subject) > FORUM_SUBJECT_MAXIMUM_LENGTH)
	     	ForumCore::$errors[] = sprintf(ForumCore::$lang['Too long e-mail subject'], FORUM_SUBJECT_MAXIMUM_LENGTH);

		if ($message == '')
			ForumCore::$errors[] = ForumCore::$lang['No e-mail message'];
		else if (strlen($message) > FORUM_MAX_POSTSIZE_BYTES)
			ForumCore::$errors[] = sprintf(ForumCore::$lang['Too long e-mail message'],
				forum_number_format(strlen($message)), forum_number_format(FORUM_MAX_POSTSIZE_BYTES));

		if (ForumUser::$forum_user['last_email_sent'] != '' && (time() - ForumUser::$forum_user['last_email_sent']) < ForumUser::$forum_user['g_email_flood'] && (time() - ForumUser::$forum_user['last_email_sent']) >= 0)
			ForumCore::$errors[] = sprintf(ForumCore::$lang['Email flood'], ForumUser::$forum_user['g_email_flood']);

		($hook = get_hook('mi_email_end_validation')) ? eval($hook) : null;

		// Did everything go according to plan?
		if (empty(ForumCore::$errors))
		{
			// Load the "form e-mail" template
			$mail_tpl = forum_trim(file_get_contents(FORUM_ROOT.'lang/'.ForumUser::$forum_user['language'].'/mail_templates/form_email.tpl'));

			// The first row contains the subject
			$first_crlf = strpos($mail_tpl, "\n");
			$mail_subject = forum_trim(substr($mail_tpl, 8, $first_crlf-8));
			$mail_message = forum_trim(substr($mail_tpl, $first_crlf));

			$mail_subject = str_replace('<mail_subject>', $subject, $mail_subject);
			$mail_message = str_replace('<sender>', ForumUser::$forum_user['username'], $mail_message);
			$mail_message = str_replace('<board_title>', ForumCore::$forum_config['o_board_title'], $mail_message);
			$mail_message = str_replace('<mail_message>', $message, $mail_message);
			$mail_message = str_replace('<board_mailer>', sprintf(ForumCore::$lang['Forum mailer'], ForumCore::$forum_config['o_board_title']), $mail_message);

			($hook = get_hook('mi_email_new_replace_data')) ? eval($hook) : null;

			if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
				require FORUM_ROOT.'include/email.php';

			forum_mail($recipient_info['email'], $mail_subject, $mail_message, ForumUser::$forum_user['email'], ForumUser::$forum_user['username']);

			// Set the user's last_email_sent time
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'last_email_sent='.time(),
				'WHERE'		=> 'id='.ForumUser::$forum_user['id'],
			);

			($hook = get_hook('mi_email_qr_update_last_email_sent')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			//$forum_flash->add_info(ForumCore::$lang['E-mail sent redirect']);

			($hook = get_hook('mi_email_pre_redirect')) ? eval($hook) : null;

			redirect(forum_htmlencode($_POST['redirect_url']), ForumCore::$lang['E-mail sent redirect']);
		}
	}

	// Setup form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;
	ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['email'], $recipient_id);

	ForumCore::$forum_page['hidden_fields'] = array(
		'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
		'redirect_url'	=> '<input type="hidden" name="redirect_url" value="'.forum_htmlencode(ForumUser::$forum_user['prev_url']).'" />',
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token(ForumCore::$forum_page['form_action']).'" />'
	);

	// Setup main heading
	ForumCore::$forum_page['main_head'] = sprintf(ForumCore::$lang['Send forum e-mail'], forum_htmlencode($recipient_info['username']));

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		sprintf(ForumCore::$lang['Send forum e-mail'], forum_htmlencode($recipient_info['username']))
	);

	($hook = get_hook('mi_email_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'formemail');
	require FORUM_ROOT.'header.php';

	// SHORTCODE [pun_content]
	add_shortcode('pun_content', function ()
	{

		($hook = get_hook('mi_email_output_start')) ? eval($hook) : null;

?>
	<div class="main-head">
		<h2 class="hn"><span><?php echo ForumCore::$forum_page['main_head'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div class="ct-box warn-box">
			<p class="important"><?php echo ForumCore::$lang['E-mail disclosure note'] ?></p>
		</div>
<?php

		// If there were any errors, show them
		if (!empty(ForumCore::$errors))
		{
			ForumCore::$forum_page['errors'] = array();
			foreach (ForumCore::$errors as $cur_error)
				ForumCore::$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

			($hook = get_hook('mi_pre_email_errors')) ? eval($hook) : null;

?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo ForumCore::$lang['Form e-mail errors'] ?></h2>
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
		<form id="afocus" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo ForumCore::$forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['hidden_fields'])."\n" ?>
			</div>
<?php ($hook = get_hook('mi_email_pre_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Write e-mail'] ?></strong></legend>
<?php ($hook = get_hook('mi_email_pre_subject')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text required longtext">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['E-mail subject'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="req_subject" value="<?php echo(isset($_POST['req_subject']) ? forum_htmlencode($_POST['req_subject']) : '') ?>" size="<?php echo FORUM_SUBJECT_MAXIMUM_LENGTH ?>" maxlength="<?php echo FORUM_SUBJECT_MAXIMUM_LENGTH ?>" required /></span>
					</div>
				</div>
<?php ($hook = get_hook('mi_email_pre_message_contents')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="txt-box textarea required">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['E-mail message'] ?></span></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="req_message" rows="10" cols="95" required><?php echo(isset($_POST['req_message']) ? forum_htmlencode($_POST['req_message']) : '') ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = get_hook('mi_email_pre_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('mi_email_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="submit" value="<?php echo ForumCore::$lang['Submit'] ?>" /></span>
				<span class="cancel"><input type="submit" name="cancel" value="<?php echo ForumCore::$lang['Cancel'] ?>" formnovalidate /></span>
			</div>
		</form>
	</div>
<?php

		($hook = get_hook('mi_email_end')) ? eval($hook) : null;
	});

	require FORUM_ROOT.'footer.php';
}


// Report a post?
else if (isset($_GET['report']))
{
	if (ForumUser::$forum_user['is_guest'])
		message(ForumCore::$lang['No permission']);

	$post_id = intval($_GET['report']);
	if ($post_id < 1)
		message(ForumCore::$lang['Bad request']);


	($hook = get_hook('mi_report_selected')) ? eval($hook) : null;

	// User pressed the cancel button
	if (isset($_POST['cancel']))
		redirect(forum_link(ForumCore::$forum_url['post'], $post_id), ForumCore::$lang['Cancel redirect']);


	if (isset($_POST['form_sent']))
	{
		($hook = get_hook('mi_report_form_submitted')) ? eval($hook) : null;

		// Start with a clean slate
		ForumCore::$errors = array();

		// Flood protection
		if (ForumUser::$forum_user['last_email_sent'] != '' && (time() - ForumUser::$forum_user['last_email_sent']) < ForumUser::$forum_user['g_email_flood'] && (time() - ForumUser::$forum_user['last_email_sent']) >= 0)
			message(sprintf(ForumCore::$lang['Report flood'], ForumUser::$forum_user['g_email_flood']));

		// Clean up reason from POST
		$reason = forum_linebreaks(forum_trim($_POST['req_reason']));
		if ($reason == '')
			message(ForumCore::$lang['No reason']);

		if (strlen($reason) > FORUM_MAX_POSTSIZE_BYTES)
		{
			ForumCore::$errors[] = sprintf(ForumCore::$lang['Too long reason'], forum_number_format(strlen($reason)), forum_number_format(FORUM_MAX_POSTSIZE_BYTES));
		}

		if (empty(ForumCore::$errors)) {
			// Get some info about the topic we're reporting
			$query = array(
				'SELECT'	=> 't.id, t.subject, t.forum_id',
				'FROM'		=> 'posts AS p',
				'JOINS'		=> array(
					array(
						'INNER JOIN'	=> 'topics AS t',
						'ON'			=> 't.id=p.topic_id'
					)
				),
				'WHERE'		=> 'p.id='.$post_id
			);

			($hook = get_hook('mi_report_qr_get_topic_data')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			$topic_info = $forum_db->fetch_assoc($result);

			if (!$topic_info)
			{
				message(ForumCore::$lang['Bad request']);
			}

			($hook = get_hook('mi_report_pre_reports_sent')) ? eval($hook) : null;

			// Should we use the internal report handling?
			if (ForumCore::$forum_config['o_report_method'] == 0 || ForumCore::$forum_config['o_report_method'] == 2)
			{
				$query = array(
					'INSERT'	=> 'post_id, topic_id, forum_id, reported_by, created, message',
					'INTO'		=> 'reports',
					'VALUES'	=> $post_id.', '.$topic_info['id'].', '.$topic_info['forum_id'].', '.ForumUser::$forum_user['id'].', '.time().', \''.$forum_db->escape($reason).'\''
				);

				($hook = get_hook('mi_report_add_report')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}

			// Should we e-mail the report?
			if (ForumCore::$forum_config['o_report_method'] == 1 || ForumCore::$forum_config['o_report_method'] == 2)
			{
				// We send it to the complete mailing-list in one swoop
				if (ForumCore::$forum_config['o_mailing_list'] != '')
				{
					$mail_subject = 'Report('.$topic_info['forum_id'].') - \''.$topic_info['subject'].'\'';
					$mail_message = 'User \''.ForumUser::$forum_user['username'].'\' has reported the following message:'."\n".forum_link(ForumCore::$forum_url['post'], $post_id)."\n\n".'Reason:'."\n".$reason;

					if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
						require FORUM_ROOT.'include/email.php';

					($hook = get_hook('mi_report_modify_message')) ? eval($hook) : null;

					forum_mail(ForumCore::$forum_config['o_mailing_list'], $mail_subject, $mail_message);
				}
			}

			// Set last_email_sent time to prevent flooding
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'last_email_sent='.time(),
				'WHERE'		=> 'id='.ForumUser::$forum_user['id']
			);

			($hook = get_hook('mi_report_qr_update_last_email_sent')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			//$forum_flash->add_info(ForumCore::$lang['Report redirect']);

			($hook = get_hook('mi_report_pre_redirect')) ? eval($hook) : null;

			redirect(forum_link(ForumCore::$forum_url['post'], $post_id), ForumCore::$lang['Report redirect']);
		}
	}

	// Setup form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;
	ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['report'], $post_id);

	ForumCore::$forum_page['hidden_fields'] = array(
		'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token(ForumCore::$forum_page['form_action']).'" />'
	);

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		ForumCore::$lang['Report post']
	);

	// Setup main heading
	ForumCore::$forum_page['main_head'] = end(ForumCore::$forum_page['crumbs']);

	($hook = get_hook('mi_report_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'report');
	require FORUM_ROOT.'header.php';

	// SHORTCODE [pun_content]
	add_shortcode('pun_content', function ()
	{
		$forum_db = new DBLayer;

		($hook = get_hook('mi_report_output_start')) ? eval($hook) : null;

?>
	<div class="main-head">
		<h2 class="hn"><span><?php echo ForumCore::$forum_page['main_head'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div id="req-msg" class="req-warn ct-box error-box">
			<p class="important"><?php echo ForumCore::$lang['Required warn'] ?></p>
		</div>
<?php
			// If there were any errors, show them
			if (!empty(ForumCore::$errors)) {
				ForumCore::$forum_page['errors'] = array();
				foreach (ForumCore::$errors as $cur_error) {
					ForumCore::$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';
				}

				($hook = get_hook('mi_pre_report_errors')) ? eval($hook) : null;
?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo ForumCore::$lang['Report errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php
			}
?>
		<form id="afocus" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo ForumCore::$forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['hidden_fields'])."\n" ?>
			</div>
<?php ($hook = get_hook('mi_report_pre_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Required information'] ?></strong></legend>
<?php ($hook = get_hook('mi_report_pre_reason')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="txt-box textarea required">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Reason'] ?></span> <small><?php echo ForumCore::$lang['Reason help'] ?></small></label><br />
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="req_reason" rows="5" cols="60" required></textarea></span></div>
					</div>
				</div>
<?php ($hook = get_hook('mi_report_pre_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('mi_report_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary caution"><input type="submit" name="submit" value="<?php echo ForumCore::$lang['Submit'] ?>" /></span>
				<span class="cancel"><input type="submit" name="cancel" value="<?php echo ForumCore::$lang['Cancel'] ?>" formnovalidate /></span>
			</div>
		</form>
	</div>
<?php

		($hook = get_hook('mi_report_end')) ? eval($hook) : null;

	});

	require FORUM_ROOT.'footer.php';
}


// Subscribe to a topic?
else if (isset($_GET['subscribe']))
{
	if (ForumUser::$forum_user['is_guest'] || ForumCore::$forum_config['o_subscriptions'] != '1')
		message(ForumCore::$lang['No permission']);

	$topic_id = intval($_GET['subscribe']);
	if ($topic_id < 1)
		message(ForumCore::$lang['Bad request']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('subscribe'.$topic_id.ForumUser::$forum_user['id'])))
		csrf_confirm_form();

	($hook = get_hook('mi_subscribe_selected')) ? eval($hook) : null;

	// Make sure the user can view the topic
	$query = array(
		'SELECT'	=> 'subject',
		'FROM'		=> 'topics AS t',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'forum_perms AS fp',
				'ON'			=> '(fp.forum_id=t.forum_id AND fp.group_id='.ForumUser::$forum_user['g_id'].')'
			)
		),
		'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$topic_id.' AND t.moved_to IS NULL'
	);
	($hook = get_hook('mi_subscribe_qr_topic_exists')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$subject = $forum_db->result($result);

	if (!$subject)
	{
		message(ForumCore::$lang['Bad request']);
	}

	$query = array(
		'SELECT'	=> 'COUNT(s.user_id)',
		'FROM'		=> 'subscriptions AS s',
		'WHERE'		=> 'user_id='.ForumUser::$forum_user['id'].' AND topic_id='.$topic_id
	);

	($hook = get_hook('mi_subscribe_qr_check_subscribed')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if ($forum_db->result($result) > 0)
	{
		message(ForumCore::$lang['Already subscribed']);
	}

	$query = array(
		'INSERT'	=> 'user_id, topic_id',
		'INTO'		=> 'subscriptions',
		'VALUES'	=> ForumUser::$forum_user['id'].' ,'.$topic_id
	);

	($hook = get_hook('mi_subscribe_add_subscription')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	//$forum_flash->add_info(ForumCore::$lang['Subscribe redirect']);

	($hook = get_hook('mi_subscribe_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link(ForumCore::$forum_url['topic'], array($topic_id, sef_friendly($subject))), ForumCore::$lang['Subscribe redirect']);
}


// Unsubscribe from a topic?
else if (isset($_GET['unsubscribe']))
{
	if (ForumUser::$forum_user['is_guest'] || ForumCore::$forum_config['o_subscriptions'] != '1')
		message(ForumCore::$lang['No permission']);

	$topic_id = intval($_GET['unsubscribe']);
	if ($topic_id < 1)
		message(ForumCore::$lang['Bad request']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('unsubscribe'.$topic_id.ForumUser::$forum_user['id'])))
		csrf_confirm_form();

	($hook = get_hook('mi_unsubscribe_selected')) ? eval($hook) : null;

	$query = array(
		'SELECT'	=> 't.subject',
		'FROM'		=> 'topics AS t',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'subscriptions AS s',
				'ON'			=> 's.user_id='.ForumUser::$forum_user['id'].' AND s.topic_id=t.id'
			)
		),
		'WHERE'		=> 't.id='.$topic_id
	);

	($hook = get_hook('mi_unsubscribe_qr_check_subscribed')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$subject = $forum_db->result($result);

	if (!$subject)
	{
		message(ForumCore::$lang['Not subscribed']);
	}

	$query = array(
		'DELETE'	=> 'subscriptions',
		'WHERE'		=> 'user_id='.ForumUser::$forum_user['id'].' AND topic_id='.$topic_id
	);

	($hook = get_hook('mi_unsubscribe_qr_delete_subscription')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	//$forum_flash->add_info(ForumCore::$lang['Unsubscribe redirect']);

	($hook = get_hook('mi_unsubscribe_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link(ForumCore::$forum_url['topic'], array($topic_id, sef_friendly($subject))), ForumCore::$lang['Unsubscribe redirect']);
}


// Subscribe to a forum?
else if (isset($_GET['forum_subscribe']))
{
	if (ForumUser::$forum_user['is_guest'] || ForumCore::$forum_config['o_subscriptions'] != '1')
		message(ForumCore::$lang['No permission']);

	$forum_id = intval($_GET['forum_subscribe']);
	if ($forum_id < 1)
		message(ForumCore::$lang['Bad request']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('forum_subscribe'.$forum_id.ForumUser::$forum_user['id'])))
		csrf_confirm_form();

	($hook = get_hook('mi_forum_subscribe_selected')) ? eval($hook) : null;

	// Make sure the user can view the forum
	$query = array(
		'SELECT'	=> 'f.forum_name',
		'FROM'		=> 'forums AS f',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'forum_perms AS fp',
				'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.ForumUser::$forum_user['g_id'].')'
			)
		),
		'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$forum_id
	);
	($hook = get_hook('mi_forum_subscribe_qr_forum_exists')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$forum_name = $forum_db->result($result);

	if (!$forum_name)
	{
		message(ForumCore::$lang['Bad request']);
	}

	$query = array(
		'SELECT'	=> 'COUNT(fs.user_id)',
		'FROM'		=> 'forum_subscriptions AS fs',
		'WHERE'		=> 'user_id='.ForumUser::$forum_user['id'].' AND forum_id='.$forum_id
	);

	($hook = get_hook('mi_forum_subscribe_qr_check_subscribed')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if ($forum_db->result($result) > 0)
	{
		message(ForumCore::$lang['Already subscribed']);
	}

	$query = array(
		'INSERT'	=> 'user_id, forum_id',
		'INTO'		=> 'forum_subscriptions',
		'VALUES'	=> ForumUser::$forum_user['id'].' ,'.$forum_id
	);

	($hook = get_hook('mi_forum_subscribe_add_subscription')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	//$forum_flash->add_info(ForumCore::$lang['Subscribe redirect']);

	($hook = get_hook('mi_forum_subscribe_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link(ForumCore::$forum_url['forum'], array($forum_id, sef_friendly($forum_name))), ForumCore::$lang['Subscribe redirect']);
}


// Unsubscribe from a topic?
else if (isset($_GET['forum_unsubscribe']))
{
	if (ForumUser::$forum_user['is_guest'] || ForumCore::$forum_config['o_subscriptions'] != '1')
		message(ForumCore::$lang['No permission']);

	$forum_id = intval($_GET['forum_unsubscribe']);
	if ($forum_id < 1)
		message(ForumCore::$lang['Bad request']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('forum_unsubscribe'.$forum_id.ForumUser::$forum_user['id'])))
		csrf_confirm_form();

	($hook = get_hook('mi_forum_unsubscribe_selected')) ? eval($hook) : null;

	// Make sure the user can view the forum
	$query = array(
		'SELECT'	=> 'f.forum_name',
		'FROM'		=> 'forums AS f',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'forum_perms AS fp',
				'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.ForumUser::$forum_user['g_id'].')'
			)
		),
		'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$forum_id
	);

	($hook = get_hook('mi_forum_unsubscribe_qr_check_subscribed')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$forum_name = $forum_db->result($result);

	if (!$forum_name)
	{
		message(ForumCore::$lang['Not subscribed']);
	}

	$query = array(
		'DELETE'	=> 'forum_subscriptions',
		'WHERE'		=> 'user_id='.ForumUser::$forum_user['id'].' AND forum_id='.$forum_id
	);

	($hook = get_hook('mi_unsubscribe_qr_delete_subscription')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	//$forum_flash->add_info(ForumCore::$lang['Unsubscribe redirect']);

	($hook = get_hook('mi_forum_unsubscribe_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link(ForumCore::$forum_url['forum'], array($forum_id, sef_friendly($forum_name))), ForumCore::$lang['Unsubscribe redirect']);
}

($hook = get_hook('mi_new_action')) ? eval($hook) : null;

