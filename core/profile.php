<?php
/**
 * Allows users to view and edit their details.
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

($hook = get_hook('pf_start')) ? eval($hook) : null;

$action = isset($_GET['action']) ? $_GET['action'] : null;
$section = isset($_GET['section']) ? $_GET['section'] : 'about';	// Default to section "about"
ForumCore::$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (ForumCore::$id < 2)
	message(ForumCore::$lang['Bad request']);

if ($action != 'change_pass' || !isset($_GET['key']))
{
	if (ForumUser::$forum_user['g_read_board'] == '0')
		message(ForumCore::$lang['No view']);
	else if (ForumUser::$forum_user['g_view_users'] == '0' && (ForumUser::$forum_user['is_guest'] || ForumUser::$forum_user['id'] != ForumCore::$id))
		message(ForumCore::$lang['No permission']);
}

// Load the profile.php language file
ForumCore::add_lang('profile');

$forum_db = new DBLayer;

// Fetch info about the user whose profile we're viewing
$query = array(
	'SELECT'	=> 'u.*, g.g_id, g.g_user_title, g.g_moderator',
	'FROM'		=> 'users AS u',
	'JOINS'		=> array(
		array(
			'LEFT JOIN'	=> 'groups AS g',
			'ON'		=> 'g.g_id=u.group_id'
		)
	),
	'WHERE'		=> 'u.id='.ForumCore::$id
);

($hook = get_hook('pf_qr_get_user_info')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
ForumUser::$user = $forum_db->fetch_assoc($result);

if (!ForumUser::$user)
	message(ForumCore::$lang['Bad request']);

if ($action == 'change_pass')
{
	($hook = get_hook('pf_change_pass_key_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'profile-changepass');
	require FORUM_ROOT.'header.php';

	require FORUM_ROOT.'profile_change_pass.php';
	
}

else if ($action == 'delete_user')
{
	($hook = get_hook('pf_delete_user_selected')) ? eval($hook) : null;

	if (ForumUser::$forum_user['g_id'] != FORUM_ADMIN)
		message(ForumCore::$lang['No permission']);

	if (ForumUser::$user['g_id'] == FORUM_ADMIN)
		message(ForumCore::$lang['Cannot delete admin']);

	// Setup form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;
	ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['delete_user'], ForumCore::$id);

	// Setup form information
	ForumCore::$forum_page['frm_info'] = array(
		'<li class="warn"><span>'.ForumCore::$lang['Delete warning'].'</span></li>',
		'<li class="warn"><span>'.ForumCore::$lang['Delete posts info'].'</span></li>'
	);

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(sprintf(ForumCore::$lang['Users profile'], ForumUser::$user['username'], ForumCore::$lang['Section admin']), forum_link(ForumCore::$forum_url['profile_admin'], ForumCore::$id)),
		ForumCore::$lang['Delete user']
	);

	($hook = get_hook('pf_delete_user_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'dialogue');
	require FORUM_ROOT.'header.php';

	// SHORTCODE [pun_content]
	add_shortcode('pun_content', function ()
	{
		($hook = get_hook('pf_delete_user_output_start')) ? eval($hook) : null;

?>
	<div class="main-head">
		<h2 class="hn"><span><?php printf((ForumUser::$forum_user['id'] == ForumCore::$id) ? ForumCore::$lang['Profile welcome'] : ForumCore::$lang['Profile welcome user'], forum_htmlencode(ForumUser::$user['username'])) ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div class="ct-box warn-box">
			<ul class="info-list">
				<?php echo implode("\n\t\t\t\t", ForumCore::$forum_page['frm_info'])."\n" ?>
			</ul>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url(admin_url('admin-post.php?action=pun_profile')); ?>">
			<div class="hidden">
				<input type="hidden" name="user_id" value="<?php echo ForumCore::$id ?>" />
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token() ?>" />
			</div>
<?php ($hook = get_hook('pf_delete_user_pre_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Required information'] ?></strong></legend>
<?php ($hook = get_hook('pf_delete_user_pre_confirm_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="delete_posts" value="1" checked="checked" /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Delete posts'] ?></span> <?php printf(ForumCore::$lang['Delete posts label'], forum_htmlencode(ForumUser::$user['username'])) ?></label>
					</div>
				</div>
<?php ($hook = get_hook('pf_delete_user_pre_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_delete_user_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary caution"><input type="submit" name="delete_user_comply" value="<?php echo ForumCore::$lang['Delete user'] ?>" /></span>
				<span class="cancel"><input type="submit" name="cancel" value="<?php echo ForumCore::$lang['Cancel'] ?>" formnovalidate /></span>
			</div>
		</form>
	</div>
<?php

		($hook = get_hook('pf_delete_user_end')) ? eval($hook) : null;
	});
}

else if ($action == 'delete_avatar')
{
	// Make sure we are allowed to delete this user's avatar
	if (ForumUser::$forum_user['id'] != ForumCore::$id &&
		ForumUser::$forum_user['g_id'] != FORUM_ADMIN &&
		(ForumUser::$forum_user['g_moderator'] != '1' || ForumUser::$forum_user['g_mod_edit_users'] == '0' || ForumUser::$user['g_id'] == FORUM_ADMIN || ForumUser::$user['g_moderator'] == '1'))
		message(ForumCore::$lang['No permission']);

	// We validate the CSRF token. If it's set in POST and we're at this point, the token is valid.
	// If it's in GET, we need to make sure it's valid.
	if (!isset($_POST['csrf_token']) && (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== generate_form_token('delete_avatar'.ForumCore::$id.ForumUser::$forum_user['id'])))
		csrf_confirm_form();

	($hook = get_hook('pf_delete_avatar_selected')) ? eval($hook) : null;

	delete_avatar(ForumCore::$id);

	// Add flash message
	//$forum_flash->add_info(ForumCore::$lang['Avatar deleted redirect']);

	($hook = get_hook('pf_delete_avatar_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link(ForumCore::$forum_url['profile_avatar'], ForumCore::$id), ForumCore::$lang['Avatar deleted redirect']);
}

// View or edit?
else if (ForumUser::$forum_user['id'] != ForumCore::$id &&
	ForumUser::$forum_user['g_id'] != FORUM_ADMIN &&
	(ForumUser::$forum_user['g_moderator'] != '1' || ForumUser::$forum_user['g_mod_edit_users'] == '0' || ForumUser::$user['g_id'] == FORUM_ADMIN || ForumUser::$user['g_moderator'] == '1'))
{

	($hook = get_hook('pf_view_details_pre_header_load')) ? eval($hook) : null;

	define('FORUM_ALLOW_INDEX', 1);
	define('FORUM_PAGE', 'profile');
	require FORUM_ROOT.'header.php';

	// SHORTCODE [pun_content]
	add_shortcode('pun_content', function ()
	{
		if (ForumUser::$user['signature'] != '')
		{
			if (!defined('FORUM_PARSER_LOADED'))
				require FORUM_ROOT.'include/parser.php';
		
			ForumCore::$parsed_signature = parse_signature(ForumUser::$user['signature']);
		}

		// Setup user identification
		ForumCore::$forum_page['user_ident'] = array();

		($hook = get_hook('pf_view_details_selected')) ? eval($hook) : null;

		ForumCore::$forum_page['user_ident']['username'] = '<li class="username'.((ForumUser::$user['realname'] =='') ? ' fn nickname' : ' nickname').'"><strong>'.forum_htmlencode(ForumUser::$user['username']).'</strong></li>';

		if (ForumCore::$forum_config['o_avatars'] == '1')
		{
			ForumCore::$forum_page['avatar_markup'] = generate_avatar_markup(ForumCore::$id, ForumUser::$user['avatar'], ForumUser::$user['avatar_width'], ForumUser::$user['avatar_height'], ForumUser::$user['username'], TRUE);

			if (!empty(ForumCore::$forum_page['avatar_markup']))
				ForumCore::$forum_page['user_ident']['avatar'] = '<li class="useravatar">'.ForumCore::$forum_page['avatar_markup'].'</li>';
		}

		ForumCore::$forum_page['user_ident']['usertitle'] = '<li class="usertitle"><span>'.get_title(ForumUser::$user).'</span></li>';

		// Setup user information
		ForumCore::$forum_page['user_info'] = array();

		if (ForumUser::$user['realname'] !='')
			ForumCore::$forum_page['user_info']['realname'] = '<li><span>'.ForumCore::$lang['Realname'].': <strong class="fn">'.forum_htmlencode((ForumCore::$forum_config['o_censoring'] == '1') ? censor_words(ForumUser::$user['realname']) : ForumUser::$user['realname']).'</strong></span></li>';

		if (ForumUser::$user['location'] !='')
			ForumCore::$forum_page['user_info']['location'] = '<li><span>'.ForumCore::$lang['From'].': <strong> '.forum_htmlencode((ForumCore::$forum_config['o_censoring'] == '1') ? censor_words(ForumUser::$user['location']) : ForumUser::$user['location']).'</strong></span></li>';

		ForumCore::$forum_page['user_info']['registered'] = '<li><span>'.ForumCore::$lang['Registered'].': <strong> '.format_time(ForumUser::$user['registered'], 1).'</strong></span></li>';
		ForumCore::$forum_page['user_info']['lastpost'] = '<li><span>'.ForumCore::$lang['Last post'].': <strong> '.format_time(ForumUser::$user['last_post']).'</strong></span></li>';

		if (ForumCore::$forum_config['o_show_post_count'] == '1' || ForumUser::$forum_user['is_admmod'])
			ForumCore::$forum_page['user_info']['posts'] = '<li><span>'.ForumCore::$lang['Posts'].': <strong>'.forum_number_format(ForumUser::$user['num_posts']).'</strong></span></li>';

		// Setup user address
		ForumCore::$forum_page['user_contact'] = array();

		if (ForumUser::$user['email_setting'] == '0' && !ForumUser::$forum_user['is_guest'] && ForumUser::$forum_user['g_send_email'] == '1')
			ForumCore::$forum_page['user_contact']['email'] = '<li><span>'.ForumCore::$lang['E-mail'].': <a href="mailto:'.forum_htmlencode(ForumUser::$user['email']).'" class="email">'.forum_htmlencode((ForumCore::$forum_config['o_censoring'] == '1' ? censor_words(ForumUser::$user['email']) : ForumUser::$user['email'])).'</a></span></li>';

		if (ForumUser::$user['email_setting'] != '2' && !ForumUser::$forum_user['is_guest'] && ForumUser::$forum_user['g_send_email'] == '1')
			ForumCore::$forum_page['user_contact']['forum-mail'] = '<li><span>'.ForumCore::$lang['E-mail'].': <a href="'.forum_link(ForumCore::$forum_url['email'], ForumCore::$id).'">'.ForumCore::$lang['Send forum e-mail'].'</a></span></li>';

		if (ForumUser::$user['url'] != '')
		{
			$url_source = ForumUser::$user['url'];

			// IDNA url handling
			if (defined('FORUM_SUPPORT_PCRE_UNICODE') && defined('FORUM_ENABLE_IDNA'))
			{
				// Load the IDNA class for international url handling
				require_once FORUM_ROOT.'include/idna/idna_convert.class.php';

				$idn = new idna_convert();
				$idn->set_parameter('encoding', 'utf8');
				$idn->set_parameter('strict', false);

				if (preg_match('!^(https?|ftp|news){1}'.preg_quote('://xn--', '!').'!', $url_source))
				{
					ForumUser::$user['url'] = $idn->decode($url_source);
				}
				else
				{
					$url_source = $idn->encode($url_source);
				}
			}

			if (ForumCore::$forum_config['o_censoring'] == '1')
				ForumUser::$user['url'] = censor_words(ForumUser::$user['url']);

			$url_source = forum_htmlencode($url_source);
			ForumUser::$user['url'] = forum_htmlencode(ForumUser::$user['url']);
			ForumCore::$forum_page['url'] = '<a href="'.$url_source.'" class="external url" rel="me">'.ForumUser::$user['url'].'</a>';

			ForumCore::$forum_page['user_contact']['website'] = '<li><span>'.ForumCore::$lang['Website'].': '.ForumCore::$forum_page['url'].'</span></li>';
		}

		// Facebook
		if (ForumUser::$user['facebook'] != '')
		{
			if (ForumCore::$forum_config['o_censoring'] == '1')
			{
				ForumUser::$user['facebook'] = censor_words(ForumUser::$user['facebook']);
			}

			$facebook_url = ((strpos(ForumUser::$user['facebook'], 'http://') === 0) || (strpos(ForumUser::$user['facebook'], 'https://') === 0)) ?
				forum_htmlencode(ForumUser::$user['facebook']) :
				forum_htmlencode('https://www.facebook.com/'.ForumUser::$user['facebook'])
			;
			ForumCore::$forum_page['facebook'] = '<a href="'.$facebook_url.'" class="external url">'.$facebook_url.'</a>';
			ForumCore::$forum_page['user_contact']['facebook'] = '<li><span>'.ForumCore::$lang['Facebook'].': '.ForumCore::$forum_page['facebook'].'</span></li>';
		}

		// Twitter
		if (ForumUser::$user['twitter'] != '')
		{
			if (ForumCore::$forum_config['o_censoring'] == '1')
			{
				ForumUser::$user['twitter'] = censor_words(ForumUser::$user['twitter']);
			}

			$twitter_url = ((strpos(ForumUser::$user['twitter'], 'http://') === 0) || (strpos(ForumUser::$user['twitter'], 'https://') === 0)) ?
				forum_htmlencode(ForumUser::$user['twitter']) :
				forum_htmlencode('https://twitter.com/'.ForumUser::$user['twitter'])
			;
			ForumCore::$forum_page['twitter'] = '<a href="'.$twitter_url.'" class="external url">'.$twitter_url.'</a>';
			ForumCore::$forum_page['user_contact']['twitter'] = '<li><span>'.ForumCore::$lang['Twitter'].': '.ForumCore::$forum_page['twitter'].'</span></li>';
		}

		// LinkedIn
		if (ForumUser::$user['linkedin'] != '')
		{
			if (ForumCore::$forum_config['o_censoring'] == '1')
			{
				ForumUser::$user['linkedin'] = censor_words(ForumUser::$user['linkedin']);
			}

			$linkedin_url = forum_htmlencode(ForumUser::$user['linkedin']);
			ForumCore::$forum_page['linkedin'] = '<a href="'.$linkedin_url.'" class="external url" rel="me">'.$linkedin_url.'</a>';
			ForumCore::$forum_page['user_contact']['linkedin'] = '<li><span>'.ForumCore::$lang['LinkedIn'].': '.ForumCore::$forum_page['linkedin'].'</span></li>';
		}

		if (ForumUser::$user['jabber'] !='')
			ForumCore::$forum_page['user_contact']['jabber'] = '<li><span>'.ForumCore::$lang['Jabber'].': <strong> '.forum_htmlencode((ForumCore::$forum_config['o_censoring'] == '1') ? censor_words(ForumUser::$user['jabber']) : ForumUser::$user['jabber']).'</strong></span></li>';
		if (ForumUser::$user['icq'] !='')
			ForumCore::$forum_page['user_contact']['icq'] = '<li><span>'.ForumCore::$lang['ICQ'].': <strong> '.forum_htmlencode(ForumUser::$user['icq']).'</strong></span></li>';
		if (ForumUser::$user['msn'] !='')
			ForumCore::$forum_page['user_contact']['msn'] = '<li><span>'.ForumCore::$lang['MSN'].': <strong> '.forum_htmlencode((ForumCore::$forum_config['o_censoring'] == '1') ? censor_words(ForumUser::$user['msn']) : ForumUser::$user['msn']).'</strong></span></li>';
		if (ForumUser::$user['aim'] !='')
			ForumCore::$forum_page['user_contact']['aim'] = '<li><span>'.ForumCore::$lang['AOL IM'].': <strong> '.forum_htmlencode((ForumCore::$forum_config['o_censoring'] == '1') ? censor_words(ForumUser::$user['aim']) : ForumUser::$user['aim']).'</strong></span></li>';
		if (ForumUser::$user['yahoo'] !='')
			ForumCore::$forum_page['user_contact']['yahoo'] = '<li><span>'.ForumCore::$lang['Yahoo'].': <strong> '.forum_htmlencode((ForumCore::$forum_config['o_censoring'] == '1') ? censor_words(ForumUser::$user['yahoo']) : ForumUser::$user['yahoo']).'</strong></span></li>';

		// Setup signature demo
		if (ForumCore::$forum_config['o_signatures'] == '1' && isset(ForumCore::$parsed_signature))
			ForumCore::$forum_page['sig_demo'] = ForumCore::$parsed_signature;

		// Setup search links
		if (ForumUser::$forum_user['g_search'] == '1')
		{
			ForumCore::$forum_page['user_activity'] = array();
			ForumCore::$forum_page['user_activity']['search_posts'] = '<li class="first-item"><a href="'.forum_link(ForumCore::$forum_url['search_user_posts'], ForumCore::$id).'">'.sprintf(ForumCore::$lang['View user posts'], forum_htmlencode(ForumUser::$user['username'])).'</a></li>';
			ForumCore::$forum_page['user_activity']['search_topics'] = '<li><a href="'.forum_link(ForumCore::$forum_url['search_user_topics'], ForumCore::$id).'">'.sprintf(ForumCore::$lang['View user topics'], forum_htmlencode(ForumUser::$user['username'])).'</a></li>';
		}

		// Setup breadcrumbs
		ForumCore::$forum_page['crumbs'] = array(
			array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
			sprintf(ForumCore::$lang['Users profile'], ForumUser::$user['username'])
		);

		ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

		($hook = get_hook('pf_view_details_output_start')) ? eval($hook) : null;

?>
	<div class="main-head">
		<h2 class="hn"><span><?php printf((ForumUser::$forum_user['id'] == ForumCore::$id) ? ForumCore::$lang['Profile welcome'] : ForumCore::$lang['Profile welcome user'], forum_htmlencode(ForumUser::$user['username'])) ?></span></h2>
	</div>
	<div class="main-content main-frm">
<?php ($hook = get_hook('pf_view_details_pre_user_info')) ? eval($hook) : null; ?>
		<div class="profile ct-group data-group vcard">
<?php ($hook = get_hook('pf_view_details_pre_user_ident_info')) ? eval($hook) : null; ?>
			<div class="ct-set data-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<ul class="user-ident ct-legend">
						<?php echo implode("\n\t\t\t\t\t\t", ForumCore::$forum_page['user_ident']) ?>
					</ul>
					<ul class="data-list">
						<?php echo implode("\n\t\t\t\t\t\t", ForumCore::$forum_page['user_info'])."\n" ?>
					</ul>
				</div>
			</div>
<?php ($hook = get_hook('pf_view_details_pre_user_contact_info')) ? eval($hook) : null; ?>
<?php if (!empty(ForumCore::$forum_page['user_contact'])): ?>
			<div class="ct-set data-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><span><?php echo ForumCore::$lang['Contact info'] ?></span></h3>
					<ul class="data-list">
						<?php echo implode("\n\t\t\t\t\t\t", ForumCore::$forum_page['user_contact'])."\n" ?>
					</ul>
				</div>
			</div>
<?php endif; ($hook = get_hook('pf_view_details_pre_user_activity_info')) ? eval($hook) : null; ?>
<?php if (!empty(ForumCore::$forum_page['user_activity'])): ?>
			<div class="ct-set data-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><span><?php echo ForumCore::$lang['Posts and topics'] ?></span></h3>
					<ul class="data-box">
						<?php echo implode("\n\t\t\t\t\t\t", ForumCore::$forum_page['user_activity']) ?>
					</ul>
				</div>
			</div>
<?php endif; ($hook = get_hook('pf_view_details_pre_user_sig_info')) ? eval($hook) : null; ?>
<?php if (isset(ForumCore::$forum_page['sig_demo'])): ?>
			<div class="ct-set data-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><span><?php echo ForumCore::$lang['Current signature'] ?></span></h3>
					<div class="sig-demo"><?php echo ForumCore::$forum_page['sig_demo']."\n" ?></div>
				</div>
			</div>
<?php endif; ?>
		</div>
<?php ($hook = get_hook('pf_view_details_user_info_end')) ? eval($hook) : null; ?>
	</div>
<?php

		($hook = get_hook('pf_view_details_end')) ? eval($hook) : null;

	});

}
else
{
	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		sprintf(ForumCore::$lang['Users profile'], ForumUser::$user['username'])
	);

	// Is this users own profile
	ForumCore::$forum_page['own_profile'] = (ForumUser::$forum_user['id'] == ForumCore::$id) ? true : false;

	// Setup navigation menu
	ForumCore::$forum_page['main_menu'] = array();
	ForumCore::$forum_page['main_menu']['about'] = '<li class="first-item'.(($section == 'about') ? ' active' : '').'"><a href="'.forum_link(ForumCore::$forum_url['profile_about'], ForumCore::$id).'"><span>'.ForumCore::$lang['Section about'].'</span></a></li>';

	ForumCore::$forum_page['main_menu']['identity'] = '<li'.(($section == 'identity') ? ' class="active"' : '').'><a href="'.forum_link(ForumCore::$forum_url['profile_identity'], ForumCore::$id).'"><span>'.ForumCore::$lang['Section identity'].'</span></a></li>';

	ForumCore::$forum_page['main_menu']['settings'] = '<li'.(($section == 'settings') ? ' class="active"' : '').'><a href="'.forum_link(ForumCore::$forum_url['profile_settings'], ForumCore::$id).'"><span>'.ForumCore::$lang['Section settings'].'</span></a></li>';

	if (ForumCore::$forum_config['o_signatures'] == '1')
		ForumCore::$forum_page['main_menu']['signature'] = '<li'.(($section == 'signature') ? ' class="active"' : '').'><a href="'.forum_link(ForumCore::$forum_url['profile_signature'], ForumCore::$id).'"><span>'.ForumCore::$lang['Section signature'].'</span></a></li>';

	if (ForumCore::$forum_config['o_avatars'] == '1')
		ForumCore::$forum_page['main_menu']['avatar'] = '<li'.(($section == 'avatar') ? ' class="active"' : '').'><a href="'.forum_link(ForumCore::$forum_url['profile_avatar'], ForumCore::$id).'"><span>'.ForumCore::$lang['Section avatar'].'</span></a></li>';

	if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN || (ForumUser::$forum_user['g_moderator'] == '1' && ForumUser::$forum_user['g_mod_ban_users'] == '1' && !ForumCore::$forum_page['own_profile']))
		ForumCore::$forum_page['main_menu']['admin'] = '<li'.(($section == 'admin') ? ' class="active"' : '').'><a href="'.forum_link(ForumCore::$forum_url['profile_admin'], ForumCore::$id).'"><span>'.ForumCore::$lang['Section admin'].'</span></a></li>';

	($hook = get_hook('pf_change_details_modify_main_menu')) ? eval($hook) : null;
	// End navigation menu

	if ($section == 'about')
	{
		($hook = get_hook('pf_change_details_about_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE', 'profile-about');
		require FORUM_ROOT.'header.php';
		require FORUM_ROOT.'profile_about.php';
	}

	else if ($section == 'identity')
	{
		($hook = get_hook('pf_change_details_identity_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE', 'profile-identity');
		require FORUM_ROOT.'header.php';
		require FORUM_ROOT.'profile_identity.php';
	}

	else if ($section == 'settings')
	{
		($hook = get_hook('pf_change_details_settings_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE', 'profile-settings');
		require FORUM_ROOT.'header.php';
		require FORUM_ROOT.'profile_settings.php';
	}

	else if ($section == 'signature' && ForumCore::$forum_config['o_signatures'] == '1')
	{
		($hook = get_hook('pf_change_details_signature_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE', 'profile-signature');
		require FORUM_ROOT.'header.php';
		require FORUM_ROOT.'profile_signature.php';
	}

	else if ($section == 'avatar' && ForumCore::$forum_config['o_avatars'] == '1')
	{
		($hook = get_hook('pf_change_details_avatar_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE', 'profile-avatar');
		require FORUM_ROOT.'header.php';
		require FORUM_ROOT.'profile_avatar.php';
	}

	else if ($section == 'admin')//ok
	{
		($hook = get_hook('pf_change_details_admin_pre_header_load')) ? eval($hook) : null;

		define('FORUM_PAGE', 'profile-admin');
		require FORUM_ROOT.'header.php';
		require FORUM_ROOT.'profile_admin.php';
	}

	($hook = get_hook('pf_change_details_new_section')) ? eval($hook) : null;
}

($hook = get_hook('pf_new_action')) ? eval($hook) : null;

require FORUM_ROOT.'footer.php';
