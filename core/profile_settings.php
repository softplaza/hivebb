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
	$section = isset($_GET['section']) ? $_GET['section'] : 'about';

	ForumCore::$forum_page['styles'] = array();
	ForumCore::$forum_page['d'] = dir(FORUM_ROOT.'style');
	while ((ForumCore::$forum_page['entry'] = ForumCore::$forum_page['d']->read()) !== false)
	{
		if (ForumCore::$forum_page['entry'] != '.' && ForumCore::$forum_page['entry'] != '..' && is_dir(FORUM_ROOT.'style/'.ForumCore::$forum_page['entry']) && file_exists(FORUM_ROOT.'style/'.ForumCore::$forum_page['entry'].'/'.ForumCore::$forum_page['entry'].'.php'))
			ForumCore::$forum_page['styles'][] = ForumCore::$forum_page['entry'];
	}
	ForumCore::$forum_page['d']->close();

	ForumCore::$forum_page['languages'] = array();
	ForumCore::$forum_page['d'] = dir(FORUM_ROOT.'lang');
	while ((ForumCore::$forum_page['entry'] = ForumCore::$forum_page['d']->read()) !== false)
	{
		if (ForumCore::$forum_page['entry'] != '.' && ForumCore::$forum_page['entry'] != '..' && is_dir(FORUM_ROOT.'lang/'.ForumCore::$forum_page['entry']) && file_exists(FORUM_ROOT.'lang/'.ForumCore::$forum_page['entry'].'/common.php'))
			ForumCore::$forum_page['languages'][] = ForumCore::$forum_page['entry'];
	}
	ForumCore::$forum_page['d']->close();

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(sprintf(ForumCore::$lang['Users profile'], ForumUser::$user['username']), forum_link(ForumCore::$forum_url['user'], ForumCore::$id)),
		ForumCore::$lang['Section settings']
	);

	// Setup the form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;
	ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['profile_settings'], ForumCore::$id);

	($hook = get_hook('pf_change_details_settings_output_start')) ? eval($hook) : null;

?>
	<div class="main-menu gen-content">
		<ul>
			<?php echo implode("\n\t\t", ForumCore::$forum_page['main_menu']) ?>
		</ul>
	</div>

	<div class="main-subhead">
		<h2 class="hn"><span><?php printf((ForumCore::$forum_page['own_profile']) ? ForumCore::$lang['Settings welcome'] : ForumCore::$lang['Settings welcome user'], forum_htmlencode(ForumUser::$user['username'])) ?></span></h2>
	</div>
	
	<div class="main-content main-frm">
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_profile&section='.$section) ); ?>">
			<div class="hidden">
				<input type="hidden" name="user_id" value="<?php echo ForumCore::$id ?>" />
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_profile&section='.$section) ); ?>" />
			</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_local_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Local settings'] ?></strong></legend>
<?php

		($hook = get_hook('pf_change_details_settings_pre_language')) ? eval($hook) : null;

		// Only display the language selection box if there's more than one language available
		if (count(ForumCore::$forum_page['languages']) > 1)
		{
			natcasesort(ForumCore::$forum_page['languages']);

?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Language'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[language]">
<?php

			foreach (ForumCore::$forum_page['languages'] as $temp)
			{
				if (ForumUser::$forum_user['language'] == $temp)
					echo "\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.$temp.'</option>'."\n";
				else
					echo "\t\t\t\t\t\t".'<option value="'.$temp.'">'.$temp.'</option>'."\n";
			}

?>
						</select></span>
					</div>
				</div>
<?php

		}

		($hook = get_hook('pf_change_details_settings_pre_timezone')) ? eval($hook) : null;

?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Timezone'] ?></span> <small><?php echo ForumCore::$lang['Timezone info'] ?></small></label><br />
						<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[timezone]">
						<option value="-12"<?php if (ForumUser::$user['timezone'] == -12) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-12:00'] ?></option>
						<option value="-11"<?php if (ForumUser::$user['timezone'] == -11) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-11:00'] ?></option>
						<option value="-10"<?php if (ForumUser::$user['timezone'] == -10) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-10:00'] ?></option>
						<option value="-9.5"<?php if (ForumUser::$user['timezone'] == -9.5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-09:30'] ?></option>
						<option value="-9"<?php if (ForumUser::$user['timezone'] == -9) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-09:00'] ?></option>
						<option value="-8"<?php if (ForumUser::$user['timezone'] == -8) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-08:00'] ?></option>
						<option value="-7"<?php if (ForumUser::$user['timezone'] == -7) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-07:00'] ?></option>
						<option value="-6"<?php if (ForumUser::$user['timezone'] == -6) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-06:00'] ?></option>
						<option value="-5"<?php if (ForumUser::$user['timezone'] == -5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-05:00'] ?></option>
						<option value="-4"<?php if (ForumUser::$user['timezone'] == -4) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-04:00'] ?></option>
						<option value="-3.5"<?php if (ForumUser::$user['timezone'] == -3.5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-03:30'] ?></option>
						<option value="-3"<?php if (ForumUser::$user['timezone'] == -3) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-03:00'] ?></option>
						<option value="-2"<?php if (ForumUser::$user['timezone'] == -2) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-02:00'] ?></option>
						<option value="-1"<?php if (ForumUser::$user['timezone'] == -1) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC-01:00'] ?></option>
						<option value="0"<?php if (ForumUser::$user['timezone'] == 0) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC'] ?></option>
						<option value="1"<?php if (ForumUser::$user['timezone'] == 1) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+01:00'] ?></option>
						<option value="2"<?php if (ForumUser::$user['timezone'] == 2) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+02:00'] ?></option>
						<option value="3"<?php if (ForumUser::$user['timezone'] == 3) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+03:00'] ?></option>
						<option value="3.5"<?php if (ForumUser::$user['timezone'] == 3.5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+03:30'] ?></option>
						<option value="4"<?php if (ForumUser::$user['timezone'] == 4) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+04:00'] ?></option>
						<option value="4.5"<?php if (ForumUser::$user['timezone'] == 4.5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+04:30'] ?></option>
						<option value="5"<?php if (ForumUser::$user['timezone'] == 5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+05:00'] ?></option>
						<option value="5.5"<?php if (ForumUser::$user['timezone'] == 5.5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+05:30'] ?></option>
						<option value="5.75"<?php if (ForumUser::$user['timezone'] == 5.75) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+05:45'] ?></option>
						<option value="6"<?php if (ForumUser::$user['timezone'] == 6) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+06:00'] ?></option>
						<option value="6.5"<?php if (ForumUser::$user['timezone'] == 6.5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+06:30'] ?></option>
						<option value="7"<?php if (ForumUser::$user['timezone'] == 7) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+07:00'] ?></option>
						<option value="8"<?php if (ForumUser::$user['timezone'] == 8) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+08:00'] ?></option>
						<option value="8.75"<?php if (ForumUser::$user['timezone'] == 8.75) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+08:45'] ?></option>
						<option value="9"<?php if (ForumUser::$user['timezone'] == 9) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+09:00'] ?></option>
						<option value="9.5"<?php if (ForumUser::$user['timezone'] == 9.5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+09:30'] ?></option>
						<option value="10"<?php if (ForumUser::$user['timezone'] == 10) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+10:00'] ?></option>
						<option value="10.5"<?php if (ForumUser::$user['timezone'] == 10.5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+10:30'] ?></option>
						<option value="11"<?php if (ForumUser::$user['timezone'] == 11) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+11:00'] ?></option>
						<option value="11.5"<?php if (ForumUser::$user['timezone'] == 11.5) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+11:30'] ?></option>
						<option value="12"<?php if (ForumUser::$user['timezone'] == 12) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+12:00'] ?></option>
						<option value="12.75"<?php if (ForumUser::$user['timezone'] == 12.75) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+12:45'] ?></option>
						<option value="13"<?php if (ForumUser::$user['timezone'] == 13) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+13:00'] ?></option>
						<option value="14"<?php if (ForumUser::$user['timezone'] == 14) echo ' selected="selected"' ?>><?php echo ForumCore::$lang['UTC+14:00'] ?></option>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_dst_checkbox')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[dst]" value="1"<?php if (ForumUser::$user['dst'] == 1) echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['DST label'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_time_format')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Time format'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[time_format]">
<?php

		foreach (array_unique(ForumCore::$forum_time_formats) as $key => $time_format)
		{
			echo "\t\t\t\t\t\t".'<option value="'.$key.'"';
			if (ForumUser::$user['time_format'] == $key)
				echo ' selected="selected"';
			echo '>'. format_time(time(), 2, null, $time_format);
			if ($key == 0)
				echo ' ('.ForumCore::$lang['Default'].')';
			echo "</option>\n";
		}

?>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_date_format')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span class="legend"><?php echo ForumCore::$lang['Date format'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[date_format]">
<?php

		foreach (array_unique(ForumCore::$forum_date_formats) as $key => $date_format)
		{
			echo "\t\t\t\t\t\t\t".'<option value="'.$key.'"';
			if (ForumUser::$user['date_format'] == $key)
				echo ' selected="selected"';
			echo '>'. format_time(time(), 1, $date_format, null, true);
			if ($key == 0)
				echo ' ('.ForumCore::$lang['Default'].')';
			echo "</option>\n";
		}

?>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_local_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_details_settings_local_fieldset_end')) ? eval($hook) : null; ?>
<?php ForumCore::$forum_page['item_count'] = 0; ?>
<?php ($hook = get_hook('pf_change_details_settings_pre_display_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Display settings'] ?></strong></legend>
<?php

		($hook = get_hook('pf_change_details_settings_pre_style')) ? eval($hook) : null;

		// Only display the style selection box if there's more than one style available
		if (count(ForumCore::$forum_page['styles']) == 1)
			echo "\t\t\t\t".'<input type="hidden" name="form[style]" value="'.ForumCore::$forum_page['styles'][0].'" />'."\n";
		else if (count(ForumCore::$forum_page['styles']) > 1)
		{
			natcasesort(ForumCore::$forum_page['styles']);

?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Styles'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[style]">
<?php

			foreach (ForumCore::$forum_page['styles'] as $temp)
			{
				if (ForumUser::$user['style'] == $temp)
					echo "\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.str_replace('_', ' ', $temp).'</option>'."\n";
				else
					echo "\t\t\t\t\t\t".'<option value="'.$temp.'">'.str_replace('_', ' ', $temp).'</option>'."\n";
			}

?>
						</select></span>
					</div>
				</div>
<?php

		}

		($hook = get_hook('pf_change_details_settings_pre_image_display_fieldset')) ? eval($hook) : null;

?>
				<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<legend><span><?php echo ForumCore::$lang['Image display'] ?></span></legend>
					<div class="mf-box">
<?php if (ForumCore::$forum_config['o_smilies'] == '1' || ForumCore::$forum_config['o_smilies_sig'] == '1'): ?>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[show_smilies]" value="1"<?php if (ForumUser::$user['show_smilies'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Show smilies'] ?></label>
						</div>
<?php endif; if (ForumCore::$forum_config['o_avatars'] == '1'): ?>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[show_avatars]" value="1"<?php if (ForumUser::$user['show_avatars'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Show avatars'] ?></label>
						</div>
<?php endif; if (ForumCore::$forum_config['p_message_img_tag'] == '1'): ?>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[show_img]" value="1"<?php if (ForumUser::$user['show_img'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Show images'] ?></label>
						</div>
<?php endif; if (ForumCore::$forum_config['o_signatures'] == '1' && ForumCore::$forum_config['p_sig_img_tag'] == '1'): ?>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[show_img_sig]" value="1"<?php if (ForumUser::$user['show_img_sig'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Show images sigs'] ?></label>
						</div>
<?php endif; ?>
<?php ($hook = get_hook('pf_change_details_settings_new_image_display_option')) ? eval($hook) : null; ?>
					</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_image_display_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('pf_change_details_settings_pre_show_sigs_checkbox')) ? eval($hook) : null; ?>
<?php if (ForumCore::$forum_config['o_signatures'] == '1'): ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[show_sig]" value="1"<?php if (ForumUser::$user['show_sig'] == '1') echo ' checked="checked"' ?> /></span>
						<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Signature display'] ?></span> <?php echo ForumCore::$lang['Show sigs'] ?></label>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_display_fieldset_end')) ? eval($hook) : null; ?>
<?php endif; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_details_settings_display_fieldset_end')) ? eval($hook) : null; ?>
<?php ForumCore::$forum_page['item_count'] = 0; ?>
<?php ($hook = get_hook('pf_change_details_settings_pre_pagination_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['Pagination settings'] ?></strong></legend>
<?php ($hook = get_hook('pf_change_details_settings_pre_disp_topics')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box sf-short text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Topics per page'] ?></span> <small><?php echo ForumCore::$lang['Leave blank'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[disp_topics]" value="<?php echo ForumUser::$user['disp_topics'] ?>" size="6" maxlength="3" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_disp_posts')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<div class="sf-box sf-short text">
						<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Posts per page'] ?></span>	<small><?php echo ForumCore::$lang['Leave blank'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="form[disp_posts]" value="<?php echo ForumUser::$user['disp_posts'] ?>" size="6" maxlength="3" /></span>
					</div>
				</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_pagination_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('pf_change_details_settings_pagination_fieldset_end')) ? eval($hook) : null; ?>
<?php ForumCore::$forum_page['item_count'] = 0; ?>
<?php ($hook = get_hook('pf_change_details_settings_pre_email_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo ForumCore::$lang['E-mail and sub settings'] ?></strong></legend>
<?php ($hook = get_hook('pf_change_details_settings_pre_email_settings_fieldset')) ? eval($hook) : null; ?>
				<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<legend><span><?php echo ForumCore::$lang['E-mail settings'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[email_setting]" value="0"<?php if (ForumUser::$user['email_setting'] == '0') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['E-mail setting 1'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[email_setting]" value="1"<?php if (ForumUser::$user['email_setting'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['E-mail setting 2'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="radio" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[email_setting]" value="2"<?php if (ForumUser::$user['email_setting'] == '2') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['E-mail setting 3'] ?></label>
						</div>
<?php ($hook = get_hook('pf_change_details_settings_new_email_setting_option')) ? eval($hook) : null; ?>
					</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_email_settings_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('pf_change_details_settings_email_settings_fieldset_end')) ? eval($hook) : null; ?>
<?php if (ForumCore::$forum_config['o_subscriptions'] == '1'): ?>
				<fieldset class="mf-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
					<legend><span><?php echo ForumCore::$lang['Subscription settings'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[notify_with_post]" value="1"<?php if (ForumUser::$user['notify_with_post'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Notify full'] ?></label>
						</div>
						<div class="mf-item">
							<span class="fld-input"><input type="checkbox" id="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>" name="form[auto_notify]" value="1"<?php if (ForumUser::$user['auto_notify'] == '1') echo ' checked="checked"' ?> /></span>
							<label for="fld<?php echo ForumCore::$forum_page['fld_count'] ?>"><?php echo ForumCore::$lang['Subscribe by default'] ?></label>
						</div>
<?php ($hook = get_hook('pf_change_details_settings_new_subscription_option')) ? eval($hook) : null; ?>
					</div>
<?php ($hook = get_hook('pf_change_details_settings_pre_subscription_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php ($hook = get_hook('pf_change_details_settings_subscription_fieldset_end')) ? eval($hook) : null; ?>
<?php endif; ?>
<?php ($hook = get_hook('pf_change_details_settings_pre_email_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ForumCore::$forum_page['item_count'] = 0; ?>
<?php ($hook = get_hook('pf_change_details_settings_email_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit primary"><input type="submit" name="update" value="<?php echo ForumCore::$lang['Update profile'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	($hook = get_hook('pf_change_details_settings_end')) ? eval($hook) : null;

});

