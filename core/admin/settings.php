<?php
/**
 * Forum settings management page.
 *
 * Allows administrators to control many of the settings used in the site.
 *
 * @copyright (C) 2023 HiveBB, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package HiveBB
 */

use \HiveBB\ForumCore;
use \HiveBB\DBLayer;

defined( 'ABSPATH' ) OR die();

require FORUM_ROOT.'include/common.php';
require FORUM_ROOT.'include/common_admin.php';

($hook = get_hook('aop_start')) ? eval($hook) : null;

if (!is_admin())
	message(ForumCore::$lang['No permission']);

// Load the admin.php language file
ForumCore::add_lang('admin_common');
ForumCore::add_lang('admin_settings');

$section = isset($_GET['section']) ? $_GET['section'] : null;
$forum_db = new DBLayer;
if (!$section || $section == 'setup')
{
	// Setup the form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index'])),
		array(ForumCore::$lang['Settings'], forum_link(ForumCore::$forum_url['admin_settings_setup'])),
		array(ForumCore::$lang['Setup'], forum_link(ForumCore::$forum_url['admin_settings_setup']))
	);

	($hook = get_hook('aop_setup_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'settings');
	define('FORUM_PAGE', 'admin-settings-setup');
	require FORUM_ROOT.'header.php';

	($hook = get_hook('aop_setup_output_start')) ? eval($hook) : null;

?>
	<div class="main-content main-frm">
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_settings&section='.$section) ); ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_settings&section='.$section) ) ?>" />
				<input type="hidden" name="form_sent" value="1" />
			</div>
				<div class="content-head">
					<h2 class="hn"><span><?php echo ForumCore::$lang['Setup personal'] ?></span></h2>
				</div>
<?php ($hook = get_hook('aop_setup_pre_personal_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Setup personal legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_setup_pre_board_title')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>">
								<span><?php echo ForumCore::$lang['Board title label'] ?></span>
							</label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[board_title]" size="50" maxlength="255" value="<?php echo forum_htmlencode(ForumCore::$forum_config['o_board_title']) ?>" /></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_setup_pre_board_descrip')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>">
								<span><?php echo ForumCore::$lang['Board description label'] ?></span>
							</label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[board_desc]" size="50" maxlength="255" value="<?php echo forum_htmlencode(ForumCore::$forum_config['o_board_desc']) ?>" /></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_setup_pre_default_style')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box select">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>">
								<span><?php echo ForumCore::$lang['Default style label'] ?></span>
							</label><br />
							<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[default_style]">
<?php

	$styles = get_style_packs();
	foreach ($styles as $style)
	{
		if (ForumCore::$forum_config['o_default_style'] == $style)
			echo "\t\t\t\t\t\t\t\t".'<option value="'.$style.'" selected="selected">'.str_replace('_', ' ', $style).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t\t".'<option value="'.$style.'">'.str_replace('_', ' ', $style).'</option>'."\n";
	}

?>
							</select></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_setup_pre_personal_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php

	($hook = get_hook('aop_setup_personal_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
				<div class="content-head">
					<h2 class="hn"><span><?php echo ForumCore::$lang['Setup local'] ?></span></h2>
				</div>
<?php ($hook = get_hook('aop_setup_pre_local_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Setup local legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_setup_pre_default_language')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box select">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Default language label'] ?></span><small><?php echo ForumCore::$lang['Default language help'] ?></small></label><br />
							<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[default_lang]">
<?php

	$languages = get_language_packs();
	foreach ($languages as $lang)
	{
		if (ForumCore::$forum_config['o_default_lang'] == $lang)
			echo "\t\t\t\t\t\t\t\t".'<option value="'.$lang.'" selected="selected">'.$lang.'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t\t".'<option value="'.$lang.'">'.$lang.'</option>'."\n";
	}

	// Load the profile.php language file
	ForumCore::add_lang('profile');

?>
							</select></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_setup_pre_default_timezone')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box select">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Default timezone label'] ?></span></label><br />
							<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[default_timezone]">
								<option value="-12"<?php if (ForumCore::$forum_config['o_default_timezone'] == -12) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-12:00'] ?></option>
								<option value="-11"<?php if (ForumCore::$forum_config['o_default_timezone'] == -11) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-11:00'] ?></option>
								<option value="-10"<?php if (ForumCore::$forum_config['o_default_timezone'] == -10) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-10:00'] ?></option>
								<option value="-9.5"<?php if (ForumCore::$forum_config['o_default_timezone'] == -9.5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-09:30'] ?></option>
								<option value="-9"<?php if (ForumCore::$forum_config['o_default_timezone'] == -9) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-09:00'] ?></option>
								<option value="-8"<?php if (ForumCore::$forum_config['o_default_timezone'] == -8) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-08:00'] ?></option>
								<option value="-7"<?php if (ForumCore::$forum_config['o_default_timezone'] == -7) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-07:00'] ?></option>
								<option value="-6"<?php if (ForumCore::$forum_config['o_default_timezone'] == -6) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-06:00'] ?></option>
								<option value="-5"<?php if (ForumCore::$forum_config['o_default_timezone'] == -5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-05:00'] ?></option>
								<option value="-4"<?php if (ForumCore::$forum_config['o_default_timezone'] == -4) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-04:00'] ?></option>
								<option value="-3.5"<?php if (ForumCore::$forum_config['o_default_timezone'] == -3.5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-03:30'] ?></option>
								<option value="-3"<?php if (ForumCore::$forum_config['o_default_timezone'] == -3) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-03:00'] ?></option>
								<option value="-2"<?php if (ForumCore::$forum_config['o_default_timezone'] == -2) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-02:00'] ?></option>
								<option value="-1"<?php if (ForumCore::$forum_config['o_default_timezone'] == -1) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-01:00'] ?></option>
								<option value="0"<?php if (ForumCore::$forum_config['o_default_timezone'] == 0) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC'] ?></option>
								<option value="1"<?php if (ForumCore::$forum_config['o_default_timezone'] == 1) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+01:00'] ?></option>
								<option value="2"<?php if (ForumCore::$forum_config['o_default_timezone'] == 2) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+02:00'] ?></option>
								<option value="3"<?php if (ForumCore::$forum_config['o_default_timezone'] == 3) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+03:00'] ?></option>
								<option value="3.5"<?php if (ForumCore::$forum_config['o_default_timezone'] == 3.5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+03:30'] ?></option>
								<option value="4"<?php if (ForumCore::$forum_config['o_default_timezone'] == 4) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+04:00'] ?></option>
								<option value="4.5"<?php if (ForumCore::$forum_config['o_default_timezone'] == 4.5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+04:30'] ?></option>
								<option value="5"<?php if (ForumCore::$forum_config['o_default_timezone'] == 5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+05:00'] ?></option>
								<option value="5.5"<?php if (ForumCore::$forum_config['o_default_timezone'] == 5.5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+05:30'] ?></option>
								<option value="5.75"<?php if (ForumCore::$forum_config['o_default_timezone'] == 5.75) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+05:45'] ?></option>
								<option value="6"<?php if (ForumCore::$forum_config['o_default_timezone'] == 6) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+06:00'] ?></option>
								<option value="6.5"<?php if (ForumCore::$forum_config['o_default_timezone'] == 6.5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+06:30'] ?></option>
								<option value="7"<?php if (ForumCore::$forum_config['o_default_timezone'] == 7) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+07:00'] ?></option>
								<option value="8"<?php if (ForumCore::$forum_config['o_default_timezone'] == 8) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+08:00'] ?></option>
								<option value="8.75"<?php if (ForumCore::$forum_config['o_default_timezone'] == 8.75) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+08:45'] ?></option>
								<option value="9"<?php if (ForumCore::$forum_config['o_default_timezone'] == 9) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+09:00'] ?></option>
								<option value="9.5"<?php if (ForumCore::$forum_config['o_default_timezone'] == 9.5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+09:30'] ?></option>
								<option value="10"<?php if (ForumCore::$forum_config['o_default_timezone'] == 10) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+10:00'] ?></option>
								<option value="10.5"<?php if (ForumCore::$forum_config['o_default_timezone'] == 10.5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+10:30'] ?></option>
								<option value="11"<?php if (ForumCore::$forum_config['o_default_timezone'] == 11) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+11:00'] ?></option>
								<option value="11.5"<?php if (ForumCore::$forum_config['o_default_timezone'] == 11.5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+11:30'] ?></option>
								<option value="12"<?php if (ForumCore::$forum_config['o_default_timezone'] == 12) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+12:00'] ?></option>
								<option value="12.75"<?php if (ForumCore::$forum_config['o_default_timezone'] == 12.75) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+12:45'] ?></option>
								<option value="13"<?php if (ForumCore::$forum_config['o_default_timezone'] == 13) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+13:00'] ?></option>
								<option value="14"<?php if (ForumCore::$forum_config['o_default_timezone'] == 14) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+14:00'] ?></option>
							</select></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_setup_pre_default_dst')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box checkbox">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[default_dst]" value="1"<?php if (ForumCore::$forum_config['o_default_dst'] == 1) echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['DST label'] ?></label>
						</div>
					</div>
<?php ($hook = get_hook('aop_setup_pre_time_format')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Time format label'] ?></span><small><?php printf(ForumCore::$lang['Current format'], format_time(time(), 2, null, ForumCore::$forum_config['o_time_format']), ForumCore::$lang['External format help']) ?></small></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[time_format]" size="25" maxlength="25" value="<?php echo forum_htmlencode(ForumCore::$forum_config['o_time_format']) ?>" /></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_setup_pre_date_format')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Date format label'] ?></span><small><?php printf(ForumCore::$lang['Current format'], format_time(time(), 1, ForumCore::$forum_config['o_date_format'], null, true), ForumCore::$lang['External format help']) ?></small></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[date_format]" size="25" maxlength="25" value="<?php echo forum_htmlencode(ForumCore::$forum_config['o_date_format']) ?>" /></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_setup_pre_local_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php

	($hook = get_hook('aop_setup_local_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
				<div class="content-head">
					<h2 class="hn"><span><?php echo ForumCore::$lang['Setup timeouts'] ?></span></h2>
				</div>
<?php ($hook = get_hook('aop_setup_pre_timeouts_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Setup timeouts legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_setup_pre_visit_timeout')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Visit timeout label'] ?></span><small><?php echo ForumCore::$lang['Visit timeout help'] ?></small></label><br />
							<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[timeout_visit]" size="5" maxlength="5" value="<?php echo ForumCore::$forum_config['o_timeout_visit'] ?>" /></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_setup_pre_online_timeout')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Online timeout label'] ?></span><small><?php echo ForumCore::$lang['Online timeout help'] ?></small></label><br />
							<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[timeout_online]" size="5" maxlength="5" value="<?php echo ForumCore::$forum_config['o_timeout_online'] ?>" /></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_setup_pre_redirect_time')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Redirect time label'] ?></span><small><?php echo ForumCore::$lang['Redirect time help'] ?></small></label><br />
							<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[redirect_delay]" size="5" maxlength="5" value="<?php echo ForumCore::$forum_config['o_redirect_delay'] ?>" /></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_setup_pre_timeouts_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php

	($hook = get_hook('aop_setup_timeouts_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
				<div class="content-head">
					<h2 class="hn"><span><?php echo ForumCore::$lang['Setup pagination'] ?></span></h2>
				</div>
<?php ($hook = get_hook('aop_setup_pre_pagination_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Setup pagination legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_setup_pre_topics_per_page')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Topics per page label'] ?></span></label><br />
							<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[disp_topics_default]" size="5" maxlength="3" value="<?php echo ForumCore::$forum_config['o_disp_topics_default'] ?>" /></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_setup_pre_posts_per_page')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Posts per page label'] ?></span></label><br />
							<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[disp_posts_default]" size="5" maxlength="3" value="<?php echo ForumCore::$forum_config['o_disp_posts_default'] ?>" /></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_setup_pre_topic_review')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box frm-short text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Topic review label'] ?></span><small><?php echo ForumCore::$lang['Topic review help'] ?></small></label><br />
							<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[topic_review]" size="5" maxlength="3" value="<?php echo ForumCore::$forum_config['o_topic_review'] ?>" /></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_setup_pre_pagination_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php

	($hook = get_hook('aop_setup_pagination_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
				<div class="content-head">
					<h2 class="hn"><span><?php echo ForumCore::$lang['Setup reports'] ?></span></h2>
				</div>
<?php ($hook = get_hook('aop_setup_pre_reports_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Setup reports legend'] ?></strong></legend>
					<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<legend><span><?php echo ForumCore::$lang['Reporting method'] ?></span></legend>
						<div class="mf-box">
							<div class="mf-item">
								<span class="fld-input"><input type="radio" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[report_method]" value="0"<?php if (ForumCore::$forum_config['o_report_method'] == '0') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Report internal label'] ?></label>
							</div>
							<div class="mf-item">
								<span class="fld-input"><input type="radio" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[report_method]" value="1"<?php if (ForumCore::$forum_config['o_report_method'] == '1') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Report email label'] ?></label>
							</div>
							<div class="mf-item">
								<span class="fld-input"><input type="radio" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[report_method]" value="2"<?php if (ForumCore::$forum_config['o_report_method'] == '2') echo ' checked="checked"' ?> /></span>
								<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Report both label'] ?></label>
							</div>
<?php ($hook = get_hook('aop_setup_new_reporting_method')) ? eval($hook) : null; ?>
						</div>
					</fieldset>
<?php ($hook = get_hook('aop_setup_pre_reports_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php

	($hook = get_hook('aop_setup_reports_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
				<div class="content-head">
					<h2 class="hn"><span><?php echo ForumCore::$lang['Setup URL'] ?></span></h2>
				</div>
				<div class="ct-box warn-box">
					<p class="warn"><?php echo ForumCore::$lang['URL scheme info'] ?></p>
				</div>
<?php ($hook = get_hook('aop_setup_pre_url_scheme_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Setup URL legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_setup_pre_url_scheme')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box select">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['URL scheme label'] ?></span><small><?php echo ForumCore::$lang['URL scheme help'] ?></small></label><br />
							<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[sef]">
<?php

	$url_schemes = get_scheme_packs();
	foreach ($url_schemes as $schema)
	{
		if (ForumCore::$forum_config['o_sef'] == $schema)
			echo "\t\t\t\t\t\t\t\t".'<option value="'.$schema.'" selected="selected">'.str_replace('_', ' ', $schema).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t\t".'<option value="'.$schema.'">'.str_replace('_', ' ', $schema).'</option>'."\n";
	}

?>
							</select></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_setup_pre_url_scheme_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php

	($hook = get_hook('aop_setup_url_scheme_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
				<div class="content-head">
					<h2 class="hn"><span><?php echo ForumCore::$lang['Setup links'] ?></span></h2>
				</div>
				<div class="ct-box warn-box">
					<p class="warn"><?php echo ForumCore::$lang['Setup links info'] ?></p>
				</div>
<?php ($hook = get_hook('aop_setup_pre_links_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['Setup links legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_setup_pre_additional_navlinks')) ? eval($hook) : null; ?>
					<div class="txt-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="txt-box textarea">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Enter links label'] ?></span></label>
							<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[additional_navlinks]" rows="3" cols="55"><?php echo forum_htmlencode(ForumCore::$forum_config['o_additional_navlinks']) ?></textarea></span></div>
						</div>
					</div>
<?php ($hook = get_hook('aop_setup_pre_links_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('aop_setup_links_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="save" value="<?php echo ForumCore::$lang['Save changes'] ?>" /></span>
			</div>
		</form>
	</div>

<?php

}

else if ($section == 'features')
{
	// Setup the form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index'])),
		array(ForumCore::$lang['Settings'], forum_link(ForumCore::$forum_url['admin_settings_setup'])),
		array(ForumCore::$lang['Features'], forum_link(ForumCore::$forum_url['admin_settings_features']))
	);

	($hook = get_hook('aop_features_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'settings');
	define('FORUM_PAGE', 'admin-settings-features');
	require FORUM_ROOT.'header.php';

	($hook = get_hook('aop_features_output_start')) ? eval($hook) : null;

?>
	<div class="main-content main-frm">
	<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_settings&section='.$section) ); ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_settings&section='.$section) ) ?>" />
				<input type="hidden" name="form_sent" value="1" />
				<?php 
				//wp_nonce_field( 'contact_form_submit', 'cform_generate_nonce' );
				?>
			</div>
			<div class="content-head">
				<h2 class="hn"><span><?php echo ForumCore::$lang['Features general'] ?></span></h2>
			</div>
<?php ($hook = get_hook('aop_features_pre_general_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Features general legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_features_pre_search_all_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[search_all_forums]" value="1"<?php if (ForumCore::$forum_config['o_search_all_forums'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Searching'] ?></span> <?php echo ForumCore::$lang['Search all label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_ranks_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[ranks]" value="1"<?php if (ForumCore::$forum_config['o_ranks'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['User ranks'] ?></span> <?php echo ForumCore::$lang['User ranks label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_censoring_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[censoring]" value="1"<?php if (ForumCore::$forum_config['o_censoring'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Censor words'] ?></span> <?php echo ForumCore::$lang['Censor words label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_quickjump_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[quickjump]" value="1"<?php if (ForumCore::$forum_config['o_quickjump'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Quick jump'] ?></span> <?php echo ForumCore::$lang['Quick jump label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_show_version_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[show_version]" value="1"<?php if (ForumCore::$forum_config['o_show_version'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Show version'] ?></span> <?php echo ForumCore::$lang['Show version label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_show_moderators_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[show_moderators]" value="1"<?php if (ForumCore::$forum_config['o_show_moderators'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Show moderators'] ?></span> <?php echo ForumCore::$lang['Show moderators label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_users_online_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[users_online]" value="1"<?php if (ForumCore::$forum_config['o_users_online'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Online list'] ?></span> <?php echo ForumCore::$lang['Users online label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_general_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	($hook = get_hook('aop_features_general_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
			<div class="content-head">
				<h2 class="hn"><span><?php echo ForumCore::$lang['Features posting'] ?></span></h2>
			</div>
<?php ($hook = get_hook('aop_features_pre_posting_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo ForumCore::$lang['Features posting legend'] ?></span></legend>
<?php ($hook = get_hook('aop_features_pre_quickpost_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[quickpost]" value="1"<?php if (ForumCore::$forum_config['o_quickpost'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Quick post'] ?></span> <?php echo ForumCore::$lang['Quick post label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_subscriptions_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[subscriptions]" value="1"<?php if (ForumCore::$forum_config['o_subscriptions'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Subscriptions'] ?></span> <?php echo ForumCore::$lang['Subscriptions label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_force_guest_email_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[force_guest_email]" value="1"<?php if (ForumCore::$forum_config['p_force_guest_email'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Guest posting'] ?></span> <?php echo ForumCore::$lang['Guest posting label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_show_dot_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[show_dot]" value="1"<?php if (ForumCore::$forum_config['o_show_dot'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['User has posted'] ?></span> <?php echo ForumCore::$lang['User has posted label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_topic_views_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[topic_views]" value="1"<?php if (ForumCore::$forum_config['o_topic_views'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Topic views'] ?></span> <?php echo ForumCore::$lang['Topic views label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_show_post_count_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[show_post_count]" value="1"<?php if (ForumCore::$forum_config['o_show_post_count'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['User post count'] ?></span> <?php echo ForumCore::$lang['User post count label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_show_user_info_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[show_user_info]" value="1"<?php if (ForumCore::$forum_config['o_show_user_info'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['User info'] ?></span> <?php echo ForumCore::$lang['User info label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_posting_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	($hook = get_hook('aop_features_posting_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
			<div class="content-head">
				<h2 class="hn"><span><?php echo ForumCore::$lang['Features posts'] ?></span></h2>
			</div>
<?php ($hook = get_hook('aop_features_pre_message_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo ForumCore::$lang['Features posts legend'] ?></span></legend>
<?php ($hook = get_hook('aop_features_pre_message_content_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<legend><span><?php echo ForumCore::$lang['Post content group'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[message_bbcode]" value="1"<?php if (ForumCore::$forum_config['p_message_bbcode'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow BBCode label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[message_img_tag]" value="1"<?php if (ForumCore::$forum_config['p_message_img_tag'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow img label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[smilies]" value="1"<?php if (ForumCore::$forum_config['o_smilies'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Smilies in posts label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[make_links]" value="1"<?php if (ForumCore::$forum_config['o_make_links'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Make clickable links label'] ?></label>
						</div>
<?php ($hook = get_hook('aop_features_new_message_content_option')) ? eval($hook) : null; ?>
					</div>
<?php ($hook = get_hook('aop_features_pre_message_content_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('aop_features_message_content_fieldset_end')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<legend><span><?php echo ForumCore::$lang['Allow capitals group'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[message_all_caps]" value="1"<?php if (ForumCore::$forum_config['p_message_all_caps'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['All caps message label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[subject_all_caps]" value="1"<?php if (ForumCore::$forum_config['p_subject_all_caps'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['All caps subject label'] ?></label>
						</div>
<?php ($hook = get_hook('aop_features_new_message_caps_option')) ? eval($hook) : null; ?>
					</div>
<?php ($hook = get_hook('aop_features_pre_message_caps_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('aop_features_message_caps_fieldset_end')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Indent size label'] ?></span><small><?php echo ForumCore::$lang['Indent size help'] ?></small></label><br />
						<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[indent_num_spaces]" size="5" maxlength="3" value="<?php echo ForumCore::$forum_config['o_indent_num_spaces'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_quote_depth')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Quote depth label'] ?></span><small><?php echo ForumCore::$lang['Quote depth help'] ?></small></label><br />
						<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[quote_depth]" size="5" maxlength="3" value="<?php echo ForumCore::$forum_config['o_quote_depth'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_message_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	($hook = get_hook('aop_features_message_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
			<div class="content-head">
				<h2 class="hn"><span><?php echo ForumCore::$lang['Features sigs'] ?></span></h2>
			</div>
<?php ($hook = get_hook('aop_features_pre_sig_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo ForumCore::$lang['Features sigs legend'] ?></span></legend>
<?php ($hook = get_hook('aop_features_pre_signature_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[signatures]" value="1"<?php if (ForumCore::$forum_config['o_signatures'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Allow signatures'] ?></span> <?php echo ForumCore::$lang['Allow signatures label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_sig_content_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<legend><span><?php echo ForumCore::$lang['Signature content group'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[sig_bbcode]" value="1"<?php if (ForumCore::$forum_config['p_sig_bbcode'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['BBCode in sigs label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[sig_img_tag]" value="1"<?php if (ForumCore::$forum_config['p_sig_img_tag'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Img in sigs label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[smilies_sig]" value="1"<?php if (ForumCore::$forum_config['o_smilies_sig'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Smilies in sigs label'] ?></label>
						</div>
<?php ($hook = get_hook('aop_features_new_sig_content_option')) ? eval($hook) : null; ?>
					</div>
<?php ($hook = get_hook('aop_features_pre_sig_content_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('aop_features_sig_content_fieldset_end')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[sig_all_caps]" value="1"<?php if (ForumCore::$forum_config['p_sig_all_caps'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Allow capitals group'] ?></span> <?php echo ForumCore::$lang['All caps sigs label'] ?></label>
					</div>
				</div>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Max sig length label'] ?></span></label><br />
						<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[sig_length]" size="5" maxlength="5" value="<?php echo ForumCore::$forum_config['p_sig_length'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_max_sig_lines')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Max sig lines label'] ?></span></label><br />
						<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[sig_lines]" size="5" maxlength="3" value="<?php echo ForumCore::$forum_config['p_sig_lines'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_sig_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	($hook = get_hook('aop_features_sig_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
			<div class="content-head">
				<h2 class="hn"><span><?php echo ForumCore::$lang['Features Avatars'] ?></span></h2>
			</div>
<?php ($hook = get_hook('aop_features_pre_avatars_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo ForumCore::$lang['Features Avatars legend'] ?></span></legend>
<?php ($hook = get_hook('aop_features_pre_avatar_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[avatars]" value="1"<?php if (ForumCore::$forum_config['o_avatars'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Allow avatars'] ?></span> <?php echo ForumCore::$lang['Allow avatars label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_avatar_directory')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Avatar directory label'] ?></span><small><?php echo ForumCore::$lang['Avatar directory help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[avatars_dir]" size="35" maxlength="50" value="<?php echo forum_htmlencode(ForumCore::$forum_config['o_avatars_dir']) ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_avatar_max_width')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Avatar Max width label'] ?></span><small><?php echo ForumCore::$lang['Avatar Max width help'] ?></small></label><br />
						<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[avatars_width]" size="6" maxlength="5" value="<?php echo ForumCore::$forum_config['o_avatars_width'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_avatar_max_height')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Avatar Max height label'] ?></span><small><?php echo ForumCore::$lang['Avatar Max height help'] ?></small></label><br />
						<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[avatars_height]" size="6" maxlength="5" value="<?php echo ForumCore::$forum_config['o_avatars_height'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_avatar_max_size')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Avatar Max size label'] ?></span><small><?php echo ForumCore::$lang['Avatar Max size help'] ?></small></label><br />
						<span class="fld-input"><input type="number" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[avatars_size]" size="6" maxlength="6" value="<?php echo ForumCore::$forum_config['o_avatars_size'] ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_avatars_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

	($hook = get_hook('aop_features_avatars_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
			<div class="content-head">
				<h2 class="hn"><span><?php echo ForumCore::$lang['Features update'] ?></span></h2>
			</div>
<?php if (function_exists('curl_init') || function_exists('fsockopen') || in_array(strtolower(@ini_get('allow_url_fopen')), array('on', 'true', '1'))): ?>
			<div class="ct-box warn-box">
				<p><?php echo ForumCore::$lang['Features update info'] ?></p>
			</div>
<?php ($hook = get_hook('aop_features_pre_updates_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Features update legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_features_pre_updates_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[check_for_updates]" value="1"<?php if (ForumCore::$forum_config['o_check_for_updates'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Update check'] ?></span> <?php echo ForumCore::$lang['Update check label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_version_updates_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[check_for_versions]" value="1"<?php if (ForumCore::$forum_config['o_check_for_versions'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Check for versions'] ?></span> <?php echo ForumCore::$lang['Auto check for versions'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_updates_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('aop_features_updates_fieldset_end')) ? eval($hook) : null; ?>
<?php else: ?>
			<div class="ct-box warn-box">
				<p><?php echo ForumCore::$lang['Features update disabled info'] ?></p>
			</div>
<?php ($hook = get_hook('aop_features_post_updates_disabled_box')) ? eval($hook) : null; ?>
<?php endif; ?>
<?php
	// Reset counter
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
			<div class="content-head">
				<h2 class="hn"><span><?php echo ForumCore::$lang['Features mask passwords'] ?></span></h2>
			</div>
			<div class="ct-box warn-box">
				<p><?php echo ForumCore::$lang['Features mask passwords info'] ?></p>
			</div>
<?php ($hook = get_hook('aop_features_pre_mask_passwords_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Features mask passwords legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_features_pre_mask_passwords_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[mask_passwords]" value="1"<?php if (ForumCore::$forum_config['o_mask_passwords'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Enable mask passwords'] ?></span> <?php echo ForumCore::$lang['Enable mask passwords label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_mask_passwords_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('aop_features_mask_passwords_fieldset_end')) ? eval($hook) : null; ?>
<?php
	// Reset counter
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
			<div class="content-head">
				<h2 class="hn"><span><?php echo ForumCore::$lang['Features gzip'] ?></span></h2>
			</div>
			<div class="ct-box warn-box">
				<p><?php echo ForumCore::$lang['Features gzip info'] ?></p>
			</div>
<?php ($hook = get_hook('aop_features_pre_gzip_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Features gzip legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_features_pre_gzip_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[gzip]" value="1"<?php if (ForumCore::$forum_config['o_gzip'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Enable gzip'] ?></span> <?php echo ForumCore::$lang['Enable gzip label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_features_pre_gzip_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('aop_features_gzip_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="save" value="<?php echo ForumCore::$lang['Save changes'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

}
else if ($section == 'announcements')
{
	// Setup the form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index'])),
		array(ForumCore::$lang['Settings'], forum_link(ForumCore::$forum_url['admin_settings_setup'])),
		array(ForumCore::$lang['Announcements'], forum_link(ForumCore::$forum_url['admin_settings_announcements']))
	);

	($hook = get_hook('aop_announcements_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'settings');
	define('FORUM_PAGE', 'admin-settings-announcements');
	require FORUM_ROOT.'header.php';

	($hook = get_hook('aop_announcements_output_start')) ? eval($hook) : null;

?>
	<div class="main-content main-frm">
		<div class="content-head">
			<h2 class="hn"><span><?php echo ForumCore::$lang['Announcements head'] ?></span></h2>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_settings&section='.$section) ); ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_settings&section='.$section) ) ?>" />
				<input type="hidden" name="form_sent" value="1" />
			</div>
<?php ($hook = get_hook('aop_announcements_pre_announcement_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Announcements legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_announcements_pre_enable_announcement_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[announcement]" value="1"<?php if (ForumCore::$forum_config['o_announcement'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Enable announcement'] ?></span> <?php echo ForumCore::$lang['Enable announcement label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_announcements_pre_announcement_heading')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Announcement heading label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[announcement_heading]" size="50" maxlength="255" value="<?php echo forum_htmlencode(ForumCore::$forum_config['o_announcement_heading']) ?>" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aop_announcements_pre_announcement_message')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="txt-box textarea">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Announcement message label'] ?></span><small><?php echo ForumCore::$lang['Announcement message help'] ?></small></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[announcement_message]" rows="5" cols="55"><?php echo forum_htmlencode(ForumCore::$forum_config['o_announcement_message']) ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = get_hook('aop_announcements_pre_announcement_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('aop_announcements_announcement_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="save" value="<?php echo ForumCore::$lang['Save changes'] ?>" /></span>
			</div>
		</form>
	</div>
<?php
}
else if ($section == 'registration')
{
	// Setup the form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index'])),
		array(ForumCore::$lang['Settings'], forum_link(ForumCore::$forum_url['admin_settings_setup'])),
		array(ForumCore::$lang['Registration'], forum_link(ForumCore::$forum_url['admin_settings_registration']))
	);

	($hook = get_hook('aop_registration_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'settings');
	define('FORUM_PAGE', 'admin-settings-registration');
	require FORUM_ROOT.'header.php';

	($hook = get_hook('aop_registration_output_start')) ? eval($hook) : null;

?>
	<div class="main-content main-frm">
	<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_settings&section='.$section) ); ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_settings&section='.$section) ) ?>" />
				<input type="hidden" name="form_sent" value="1" />
			</div>
			<div class="content-head">
				<h2 class="hn"><span><?php echo ForumCore::$lang['Registration new'] ?></span></h2>
			</div>
			<div class="ct-box warn-box">
				<p><?php echo ForumCore::$lang['New reg info'] ?></p>
			</div>
<?php ($hook = get_hook('aop_registration_pre_new_regs_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo ForumCore::$lang['Registration new legend'] ?></span></legend>
<?php ($hook = get_hook('aop_registration_pre_allow_new_regs_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[regs_allow]" value="1"<?php if (ForumCore::$forum_config['o_regs_allow'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Allow new reg'] ?></span> <?php echo ForumCore::$lang['Allow new reg label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_registration_pre_verify_regs_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[regs_verify]" value="1"<?php if (ForumCore::$forum_config['o_regs_verify'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Verify reg'] ?></span> <?php echo ForumCore::$lang['Verify reg label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_registration_pre_email_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<legend><span><?php echo ForumCore::$lang['Reg e-mail group'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[allow_banned_email]" value="1"<?php if (ForumCore::$forum_config['p_allow_banned_email'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow banned label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[allow_dupe_email]" value="1"<?php if (ForumCore::$forum_config['p_allow_dupe_email'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow dupe label'] ?></label>
						</div>
<?php ($hook = get_hook('aop_registration_new_email_option')) ? eval($hook) : null; ?>
					</div>
<?php ($hook = get_hook('aop_registration_pre_email_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('aop_registration_email_fieldset_end')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[regs_report]" value="1"<?php if (ForumCore::$forum_config['o_regs_report'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Report new reg'] ?></span> <?php echo ForumCore::$lang['Report new reg label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_registration_pre_email_setting_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<legend><span><?php echo ForumCore::$lang['E-mail setting group'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[default_email_setting]" value="0"<?php if (ForumCore::$forum_config['o_default_email_setting'] == '0') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Display e-mail label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[default_email_setting]" value="1"<?php if (ForumCore::$forum_config['o_default_email_setting'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Allow form e-mail label'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[default_email_setting]" value="2"<?php if (ForumCore::$forum_config['o_default_email_setting'] == '2') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Disallow form e-mail label'] ?></label>
						</div>
<?php ($hook = get_hook('aop_registration_new_email_setting_option')) ? eval($hook) : null; ?>
					</div>
<?php ($hook = get_hook('aop_registration_pre_email_setting_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('aop_registration_email_setting_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php

	($hook = get_hook('aop_registration_new_regs_fieldset_end')) ? eval($hook) : null;

	// Reset counter
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
			<div class="content-head">
				<h2 class="hn"><span><?php echo ForumCore::$lang['Registration rules'] ?></span></h2>
			</div>
				<div class="ct-box warn-box">
					<p><?php echo ForumCore::$lang['Registration rules info'] ?></p>
				</div>
<?php ($hook = get_hook('aop_registration_pre_rules_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><span><?php echo ForumCore::$lang['Registration rules legend'] ?></span></legend>
<?php ($hook = get_hook('aop_registration_pre_rules_checkbox')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box checkbox">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[rules]" value="1"<?php if (ForumCore::$forum_config['o_rules'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Require rules'] ?></span><?php echo ForumCore::$lang['Require rules label'] ?></label>
						</div>
					</div>
<?php ($hook = get_hook('aop_registration_pre_rules_text')) ? eval($hook) : null; ?>
					<div class="txt-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="txt-box textarea">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Compose rules label'] ?></span><small><?php echo ForumCore::$lang['Compose rules help'] ?></small></label>
							<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[rules_message]" rows="10" cols="55"><?php echo forum_htmlencode(ForumCore::$forum_config['o_rules_message']) ?></textarea></span></div>
						</div>
					</div>
<?php ($hook = get_hook('aop_registration_pre_rules_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('aop_registration_rules_fieldset_end')) ? eval($hook) : null; ?>
				<div class="frm-buttons">
					<span class="submit primary"><input type="submit" name="save" value="<?php echo ForumCore::$lang['Save changes'] ?>" /></span>
				</div>
		</form>
	</div>
<?php

}

else if ($section == 'maintenance')
{
	// Setup the form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index'])),
		array(ForumCore::$lang['Maintenance mode'], forum_link(ForumCore::$forum_url['admin_settings_maintenance']))
	);

	($hook = get_hook('aop_maintenance_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'settings');
	define('FORUM_PAGE', 'admin-settings-maintenance');
	require FORUM_ROOT.'header.php';

	($hook = get_hook('aop_maintenance_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo ForumCore::$lang['Maintenance head'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
	<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_settings&section='.$section) ); ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_settings&section='.$section) ) ?>" />
				<input type="hidden" name="form_sent" value="1" />
			</div>
			<div class="ct-box warn-box">
				<p class="important"><?php echo ForumCore::$lang['Maintenance mode info'] ?></p>
				<p class="warn"><?php echo ForumCore::$lang['Maintenance mode warn'] ?></p>
			</div>
<?php ($hook = get_hook('aop_maintenance_pre_maintenance_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Maintenance legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_maintenance_pre_maintenance_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[maintenance]" value="1"<?php if (ForumCore::$forum_config['o_maintenance'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Maintenance mode label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('aop_maintenance_pre_maintenance_message')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="txt-box textarea">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Maintenance message label'] ?></span><small><?php echo ForumCore::$lang['Maintenance message help'] ?></small></label>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[maintenance_message]" rows="5" cols="55"><?php echo forum_htmlencode(ForumCore::$forum_config['o_maintenance_message']) ?></textarea></span></div>
					</div>
				</div>
<?php ($hook = get_hook('aop_maintenance_pre_maintenance_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('aop_maintenance_maintenance_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="save" value="<?php echo ForumCore::$lang['Save changes'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

}

else if ($section == 'email')
{
	// Setup the form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(ForumCore::$lang['Forum administration'], forum_link(ForumCore::$forum_url['admin_index'])),
		array(ForumCore::$lang['Settings'], forum_link(ForumCore::$forum_url['admin_settings_setup'])),
		array(ForumCore::$lang['E-mail'], forum_link(ForumCore::$forum_url['admin_settings_email']))
	);

	($hook = get_hook('aop_email_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'settings');
	define('FORUM_PAGE', 'admin-settings-email');
	require FORUM_ROOT.'header.php';

	($hook = get_hook('aop_email_output_start')) ? eval($hook) : null;

?>
	<div class="main-content frm parted">
	<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_admin_settings&section='.$section) ); ?>">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_admin_settings&section='.$section) ) ?>" />
				<input type="hidden" name="form_sent" value="1" />
			</div>
			<div class="content-head">
				<h2 class="hn"><span><?php echo ForumCore::$lang['E-mail addresses'] ?></span></h2>
			</div>
<?php ($hook = get_hook('aop_email_pre_addresses_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['E-mail addresses legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_email_pre_admin_email')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Admin e-mail'] ?></span></label><br />
							<span class="fld-input"><input type="email" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[admin_email]" size="50" maxlength="80" value="<?php echo forum_htmlencode(ForumCore::$forum_config['o_admin_email']) ?>" /></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_email_pre_webmaster_email')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Webmaster e-mail label'] ?></span><small><?php echo ForumCore::$lang['Webmaster e-mail help'] ?></small></label><br />
							<span class="fld-input"><input type="email" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[webmaster_email]" size="50" maxlength="80" value="<?php echo forum_htmlencode(ForumCore::$forum_config['o_webmaster_email']) ?>" /></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_email_pre_mailing_list')) ? eval($hook) : null; ?>
					<div class="txt-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="txt-box textarea">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Mailing list label'] ?></span><small><?php echo ForumCore::$lang['Mailing list help'] ?></small></label>
							<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[mailing_list]" rows="5" cols="55"><?php echo forum_htmlencode(ForumCore::$forum_config['o_mailing_list']) ?></textarea></span></div>
						</div>
					</div>
<?php ($hook = get_hook('aop_email_pre_addresses_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php

($hook = get_hook('aop_email_addresses_fieldset_end')) ? eval($hook) : null;

// Reset counter
ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = 0;

?>
			<div class="content-head">
				<h2 class="hn"><span><?php echo ForumCore::$lang['E-mail server'] ?></span></h2>
			</div>
				<div class="ct-box warn-box">
					<p><?php echo ForumCore::$lang['E-mail server info'] ?></p>
				</div>
<?php ($hook = get_hook('aop_email_pre_smtp_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
					<legend class="group-legend"><strong><?php echo ForumCore::$lang['E-mail server legend'] ?></strong></legend>
<?php ($hook = get_hook('aop_email_pre_smtp_host')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['SMTP address label'] ?></span><small><?php echo ForumCore::$lang['SMTP address help'] ?></small></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[smtp_host]" size="35" maxlength="100" value="<?php echo forum_htmlencode(ForumCore::$forum_config['o_smtp_host']) ?>" /></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_email_pre_smtp_user')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['SMTP username label'] ?></span><small><?php echo ForumCore::$lang['SMTP username help'] ?></small></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[smtp_user]" size="35" maxlength="50" value="<?php echo forum_htmlencode(ForumCore::$forum_config['o_smtp_user']) ?>" /></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_email_pre_smtp_pass')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box text">
							<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['SMTP password label'] ?></span><small><?php echo ForumCore::$lang['SMTP password help'] ?></small></label><br />
							<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[smtp_pass]" size="35" maxlength="50" value="<?php echo forum_htmlencode(ForumCore::$forum_config['o_smtp_pass']) ?>" /></span>
						</div>
					</div>
<?php ($hook = get_hook('aop_email_pre_smtp_ssl')) ? eval($hook) : null; ?>
					<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
						<div class="sf-box checkbox">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[smtp_ssl]" value="1"<?php if (ForumCore::$forum_config['o_smtp_ssl'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['SMTP SSL'] ?></span> <?php echo ForumCore::$lang['SMTP SSL label'] ?></label>
						</div>
					</div>
<?php ($hook = get_hook('aop_email_pre_smtp_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('aop_email_smtp_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="save" value="<?php echo ForumCore::$lang['Save changes'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

}
else
{
	($hook = get_hook('aop_new_section')) ? eval($hook) : null;
}

($hook = get_hook('aop_end')) ? eval($hook) : null;

require FORUM_ROOT.'footer.php';
