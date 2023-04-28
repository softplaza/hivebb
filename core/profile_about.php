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

defined( 'ABSPATH' ) OR die();

// SHORTCODE [pun_content]
add_shortcode('pun_content', function ()
{
	if (ForumUser::$user['signature'] != '')
	{
		if (!defined('FORUM_PARSER_LOADED'))
			require FORUM_ROOT.'include/parser.php';
	
		ForumCore::$parsed_signature = parse_signature(ForumUser::$user['signature']);
	}

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(sprintf(ForumCore::$lang['Users profile'], ForumUser::$user['username']), forum_link(ForumCore::$forum_url['user'], ForumCore::$id)),
		sprintf(ForumCore::$lang['Section about'])
	);

	// Setup user identification
	ForumCore::$forum_page['user_ident'] = array();

	($hook = get_hook('pf_change_details_about_selected')) ? eval($hook) : null;

	ForumCore::$forum_page['user_ident']['username'] = '<li class="username'.((ForumUser::$user['realname'] =='') ? ' fn nickname' : ' nickname').'"><strong>'.forum_htmlencode(ForumUser::$user['username']).'</strong></li>';

	if (ForumCore::$forum_config['o_avatars'] == '1')
	{
		ForumCore::$forum_page['avatar_markup'] = generate_avatar_markup(ForumCore::$id, ForumUser::$user['avatar'], ForumUser::$user['avatar_width'], ForumUser::$user['avatar_height'], ForumUser::$user['username'], TRUE);

		if (!empty(ForumCore::$forum_page['avatar_markup']))
			ForumCore::$forum_page['user_ident']['avatar'] = '<li class="useravatar">'.ForumCore::$forum_page['avatar_markup'].'</li>';
	}

	ForumCore::$forum_page['user_ident']['usertitle'] = '<li class="usertitle"><span>'.get_title(ForumUser::$user).'</span></li>';

	// Create array for private information
	ForumCore::$forum_page['user_private'] = array();

	// Setup user information
	ForumCore::$forum_page['user_info'] = array();

	if (ForumUser::$user['realname'] !='')
		ForumCore::$forum_page['user_info']['realname'] = '<li><span>'.ForumCore::$lang['Realname'].': <strong class="fn">'.forum_htmlencode((ForumCore::$forum_config['o_censoring'] == '1') ? censor_words(ForumUser::$user['realname']) : ForumUser::$user['realname']).'</strong></span></li>';

	if (ForumUser::$user['location'] !='')
		ForumCore::$forum_page['user_info']['location'] = '<li><span>'.ForumCore::$lang['From'].': <strong> '.forum_htmlencode((ForumCore::$forum_config['o_censoring'] == '1') ? censor_words(ForumUser::$user['location']) : ForumUser::$user['location']).'</strong></span></li>';

	ForumCore::$forum_page['user_info']['registered'] = '<li><span>'.ForumCore::$lang['Registered'].': <strong> '.format_time(ForumUser::$user['registered'], 1).'</strong></span></li>';
	ForumCore::$forum_page['user_info']['lastvisit'] = '<li><span>'.ForumCore::$lang['Last visit'].': <strong> '.format_time(ForumUser::$user['last_visit']).'</strong></span></li>';
	ForumCore::$forum_page['user_info']['lastpost'] = '<li><span>'.ForumCore::$lang['Last post'].': <strong> '.format_time(ForumUser::$user['last_post']).'</strong></span></li>';

	if (ForumCore::$forum_config['o_show_post_count'] == '1' || ForumUser::$forum_user['is_admmod'])
		ForumCore::$forum_page['user_info']['posts'] = '<li><span>'.ForumCore::$lang['Posts'].': <strong>'.forum_number_format(ForumUser::$user['num_posts']).'</strong></span></li>';
	else
		ForumCore::$forum_page['user_private']['posts'] = '<li><span>'.ForumCore::$lang['Posts'].': <strong>'.forum_number_format(ForumUser::$user['num_posts']).'</strong></span></li>';

	if (ForumUser::$forum_user['is_admmod'] && ForumUser::$user['admin_note'] != '')
		ForumCore::$forum_page['user_private']['note'] = '<li><span>'.ForumCore::$lang['Note'].': <strong>'.forum_htmlencode(ForumUser::$user['admin_note']).'</strong></span></li>';

	// Setup user address
	ForumCore::$forum_page['user_contact'] = array();

	if ((ForumUser::$user['email_setting'] == '0' && !ForumUser::$forum_user['is_guest']) && ForumUser::$forum_user['g_send_email'] == '1')
		ForumCore::$forum_page['user_contact']['email'] = '<li><span>'.ForumCore::$lang['E-mail'].': <a href="mailto:'.forum_htmlencode(ForumUser::$user['email']).'" class="email">'.forum_htmlencode((ForumCore::$forum_config['o_censoring'] == '1' ? censor_words(ForumUser::$user['email']) : ForumUser::$user['email'])).'</a></span></li>';
	else if (ForumCore::$forum_page['own_profile'] || ForumUser::$forum_user['is_admmod'])
			ForumCore::$forum_page['user_private']['email'] = '<li><span>'.ForumCore::$lang['E-mail'].': <a href="mailto:'.forum_htmlencode(ForumUser::$user['email']).'" class="email">'.forum_htmlencode((ForumCore::$forum_config['o_censoring'] == '1' ? censor_words(ForumUser::$user['email']) : ForumUser::$user['email'])).'</a></span></li>';

	if (ForumUser::$user['email_setting'] != '2')
		ForumCore::$forum_page['user_contact']['forum-mail'] = '<li><span>'.ForumCore::$lang['E-mail'].': <a href="'.forum_link(ForumCore::$forum_url['email'], ForumCore::$id).'">'.ForumCore::$lang['Send forum e-mail'].'</a></span></li>';
	else if (ForumUser::$forum_user['id'] == ForumCore::$id || (ForumUser::$forum_user['is_admmod'] && ForumUser::$user['email_setting'] == '2'))
		ForumCore::$forum_page['user_private']['forum-mail'] = '<li><span>'.ForumCore::$lang['E-mail'].': <a href="'.forum_link(ForumCore::$forum_url['email'], ForumCore::$id).'">'.ForumCore::$lang['Send forum e-mail'].'</a></span></li>';

	// Website
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


	if (ForumUser::$forum_user['is_admmod'])
		ForumCore::$forum_page['user_private']['ip']= '<li><span>'.ForumCore::$lang['IP'].': <a href="'.forum_link(ForumCore::$forum_url['get_host'], forum_htmlencode(ForumUser::$user['registration_ip'])).'">'.forum_htmlencode(ForumUser::$user['registration_ip']).'</a></span></li>';

	// Setup user messaging
	if (ForumUser::$user['jabber'] !='')
		ForumCore::$forum_page['user_contact']['jabber'] = '<li><span>'.ForumCore::$lang['Jabber'].': <strong>'.forum_htmlencode((ForumCore::$forum_config['o_censoring'] == '1') ? censor_words(ForumUser::$user['jabber']) : ForumUser::$user['jabber']).'</strong></span></li>';
	if (ForumUser::$user['skype'] !='')
		ForumCore::$forum_page['user_contact']['skype'] = '<li><span>'.ForumCore::$lang['Skype'].': <strong>'.forum_htmlencode((ForumCore::$forum_config['o_censoring'] == '1') ? censor_words(ForumUser::$user['skype']) : ForumUser::$user['skype']).'</strong></span></li>';
	if (ForumUser::$user['icq'] !='')
		ForumCore::$forum_page['user_contact']['icq'] = '<li><span>'.ForumCore::$lang['ICQ'].': <strong>'.forum_htmlencode(ForumUser::$user['icq']).'</strong></span></li>';
	if (ForumUser::$user['msn'] !='')
		ForumCore::$forum_page['user_contact']['msn'] = '<li><span>'.ForumCore::$lang['MSN'].': <strong>'.forum_htmlencode((ForumCore::$forum_config['o_censoring'] == '1') ? censor_words(ForumUser::$user['msn']) : ForumUser::$user['msn']).'</strong></span></li>';
	if (ForumUser::$user['aim'] !='')
		ForumCore::$forum_page['user_contact']['aim'] = '<li><span>'.ForumCore::$lang['AOL IM'].': <strong>'.forum_htmlencode((ForumCore::$forum_config['o_censoring'] == '1') ? censor_words(ForumUser::$user['aim']) : ForumUser::$user['aim']).'</strong></span></li>';
	if (ForumUser::$user['yahoo'] !='')
		ForumCore::$forum_page['user_contact']['yahoo'] = '<li><span>'.ForumCore::$lang['Yahoo'].': <strong>'.forum_htmlencode((ForumCore::$forum_config['o_censoring'] == '1') ? censor_words(ForumUser::$user['yahoo']) : ForumUser::$user['yahoo']).'</strong></span></li>';

	// Setup signature demo
	if (ForumCore::$forum_config['o_signatures'] == '1' && isset($parsed_signature))
		ForumCore::$forum_page['sig_demo'] = $parsed_signature;

	// Setup search links
	ForumCore::$forum_page['user_activity'] = array();
	if (ForumUser::$forum_user['g_search'] == '1' || ForumUser::$forum_user['is_admmod'])
	{
		ForumCore::$forum_page['user_activity']['search_posts'] = '<li class="first-item"><a href="'.forum_link(ForumCore::$forum_url['search_user_posts'], ForumCore::$id).'">'.((ForumCore::$forum_page['own_profile']) ? ForumCore::$lang['View your posts'] : sprintf(ForumCore::$lang['View user posts'], forum_htmlencode(ForumUser::$user['username']))).'</a></li>';
		ForumCore::$forum_page['user_activity']['search_topics'] = '<li><a href="'.forum_link(ForumCore::$forum_url['search_user_topics'], ForumCore::$id).'">'.((ForumCore::$forum_page['own_profile']) ? ForumCore::$lang['View your topics'] : sprintf(ForumCore::$lang['View user topics'], forum_htmlencode(ForumUser::$user['username']))).'</a></li>';
	}

	// Subscriptions
	if ((ForumCore::$forum_page['own_profile'] || ForumUser::$forum_user['g_id'] == FORUM_ADMIN) && ForumCore::$forum_config['o_subscriptions'] == '1')
	{
		// Topic subscriptions
		ForumCore::$forum_page['user_activity']['search_subs'] = '<li'.(empty(ForumCore::$forum_page['user_activity']) ? ' class="first-item"' : '').'><a href="'.forum_link(ForumCore::$forum_url['search_subscriptions'], ForumCore::$id).'">'.((ForumCore::$forum_page['own_profile']) ? ForumCore::$lang['View your subscriptions'] : sprintf(ForumCore::$lang['View user subscriptions'], forum_htmlencode(ForumUser::$user['username']))).'</a></li>';

		// Forum subscriptions
		ForumCore::$forum_page['user_activity']['search_forum_subs'] = '<li'.(empty(ForumCore::$forum_page['user_activity']) ? ' class="first-item"' : '').'><a href="'.forum_link(ForumCore::$forum_url['search_forum_subscriptions'], ForumCore::$id).'">'.((ForumCore::$forum_page['own_profile']) ? ForumCore::$lang['View your forum subscriptions'] : sprintf(ForumCore::$lang['View user forum subscriptions'], forum_htmlencode(ForumUser::$user['username']))).'</a></li>';
	}

	// Setup user options
	ForumCore::$forum_page['user_options'] = array();

	if (ForumCore::$forum_page['own_profile'] || ForumUser::$forum_user['g_id'] == FORUM_ADMIN || (ForumUser::$forum_user['g_moderator'] == '1' && ForumUser::$forum_user['g_mod_change_passwords'] == '1'))
		ForumCore::$forum_page['user_options']['change_password'] = '<span'.(empty(ForumCore::$forum_page['user_options']) ? ' class="first-item"' : '').'><a href="'.forum_link(ForumCore::$forum_url['change_password'], ForumCore::$id).'">'.((ForumCore::$forum_page['own_profile']) ? ForumCore::$lang['Change your password'] : sprintf(ForumCore::$lang['Change user password'], forum_htmlencode(ForumUser::$user['username']))).'</a></span>';

	if (!ForumUser::$forum_user['is_admmod'])
		ForumCore::$forum_page['user_options']['change_email'] = '<span'.(empty(ForumCore::$forum_page['user_options']) ? ' class="first-item"' : '').'><a href="'.forum_link(ForumCore::$forum_url['change_email'], ForumCore::$id).'">'.((ForumCore::$forum_page['own_profile']) ? ForumCore::$lang['Change your e-mail'] : sprintf(ForumCore::$lang['Change user e-mail'], forum_htmlencode(ForumUser::$user['username']))).'</a></span>';

	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

	// Main section menu e.g. profile menu
	echo '';

	($hook = get_hook('pf_change_details_about_output_start')) ? eval($hook) : null;

?>

	<div class="main-menu gen-content">
		<ul>
			<?php echo implode("\n\t\t", ForumCore::$forum_page['main_menu']) ?>
		</ul>
	</div>

	<div class="main-subhead">
		<h2 class="hn"><span><?php printf((ForumUser::$forum_user['id'] == ForumCore::$id) ? ForumCore::$lang['Profile welcome'] : ForumCore::$lang['Profile welcome user'], forum_htmlencode(ForumUser::$user['username'])) ?></span></h2>
	</div>

	<div class="main-content main-frm">
<?php ($hook = get_hook('pf_change_details_about_pre_user_info')) ? eval($hook) : null; ?>
		<div class="profile ct-group data-group vcard">
<?php ($hook = get_hook('pf_change_details_about_pre_user_ident_info')) ? eval($hook) : null; ?>
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

<?php ($hook = get_hook('pf_change_details_about_pre_user_contact_info')) ? eval($hook) : null; ?>

<?php if (!empty(ForumCore::$forum_page['user_contact'])): ?>
			<div class="ct-set data-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h4 class="ct-legend hn"><span><?php echo ForumCore::$lang['Contact info'] ?></span></h4>
					<ul class="data-box">
						<?php echo implode("\n\t\t\t\t\t\t", ForumCore::$forum_page['user_contact'])."\n" ?>
					</ul>
				</div>
			</div>
	
<?php ($hook = get_hook('pf_change_details_about_pre_user_activity_info')) ? eval($hook) : null; ?>

<?php endif; if (!empty(ForumCore::$forum_page['user_activity'])): ?>
			<div class="ct-set data-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h4 class="ct-legend hn"><span><?php echo ForumCore::$lang['Posts and topics'] ?></span></h4>
					<ul class="data-box">
						<?php echo implode("\n\t\t\t\t\t\t", ForumCore::$forum_page['user_activity']) ?>
					</ul>
				</div>
			</div>

<?php ($hook = get_hook('pf_change_details_about_pre_user_sig_info')) ? eval($hook) : null; ?>

<?php endif; if (isset(ForumCore::$forum_page['sig_demo'])): ?>
			<div class="ct-set data-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h4 class="ct-legend hn"><span><?php echo ForumCore::$lang['Current signature'] ?></span></h4>
					<div class="sig-demo"><?php echo ForumCore::$forum_page['sig_demo'] ?></div>
				</div>
			</div>
<?php endif; ?>

<?php ($hook = get_hook('pf_change_details_about_pre_user_private_info')) ? eval($hook) : null; ?>

<?php if (!empty(ForumCore::$forum_page['user_private'])): ?>
			<div id="private-profile" class="ct-set data-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><span><?php echo ForumCore::$lang['Private info'] ?></span></h3>
					<ul class="data-list">
						<?php echo implode("\n\t\t\t\t\t\t", ForumCore::$forum_page['user_private'])."\n" ?>
					</ul>
				</div>
			</div>
<?php endif; ?>
		</div>
<?php ($hook = get_hook('pf_change_details_about_user_info_end')) ? eval($hook) : null; ?>
	</div>
<?php

	($hook = get_hook('pf_change_details_about_end')) ? eval($hook) : null;
});

