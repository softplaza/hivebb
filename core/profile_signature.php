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

	$section = isset($_GET['section']) ? $_GET['section'] : 'about';

	ForumCore::$forum_page['sig_info'][] = '<li>'.ForumCore::$lang['Signature info'].'</li>';

	if (ForumUser::$user['signature'] != '')
		ForumCore::$forum_page['sig_demo'] = ForumCore::$parsed_signature;

	// Setup breadcrumbs
	ForumCore::$forum_page['crumbs'] = array(
		array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['index'])),
		array(sprintf(ForumCore::$lang['Users profile'], ForumUser::$user['username']), forum_link(ForumCore::$forum_url['user'], ForumCore::$id)),
		ForumCore::$lang['Section signature']
	);

	// Setup the form
	ForumCore::$forum_page['group_count'] = ForumCore::$forum_page['item_count'] = ForumCore::$forum_page['fld_count'] = 0;
	ForumCore::$forum_page['form_action'] = forum_link(ForumCore::$forum_url['profile_signature'], ForumCore::$id);

	// Setup help
	ForumCore::$forum_page['text_options'] = array();
	if (ForumCore::$forum_config['p_sig_bbcode'] == '1')
		ForumCore::$forum_page['text_options']['bbcode'] = '<span'.(empty(ForumCore::$forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link(ForumCore::$forum_url['help'], 'bbcode').'" title="'.sprintf(ForumCore::$lang['Help page'], ForumCore::$lang['BBCode']).'">'.ForumCore::$lang['BBCode'].'</a></span>';
	if (ForumCore::$forum_config['p_sig_img_tag'] == '1')
		ForumCore::$forum_page['text_options']['img'] = '<span'.(empty(ForumCore::$forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link(ForumCore::$forum_url['help'], 'img').'" title="'.sprintf(ForumCore::$lang['Help page'], ForumCore::$lang['Images']).'">'.ForumCore::$lang['Images'].'</a></span>';
	if (ForumCore::$forum_config['o_smilies_sig'] == '1')
		ForumCore::$forum_page['text_options']['smilies'] = '<span'.(empty(ForumCore::$forum_page['text_options']) ? ' class="first-item"' : '').'><a class="exthelp" href="'.forum_link(ForumCore::$forum_url['help'], 'smilies').'" title="'.sprintf(ForumCore::$lang['Help page'], ForumCore::$lang['Smilies']).'">'.ForumCore::$lang['Smilies'].'</a></span>';


	($hook = get_hook('pf_change_details_signature_output_start')) ? eval($hook) : null;

?>
<div class="main-menu gen-content">
	<ul>
		<?php echo implode("\n\t\t", ForumCore::$forum_page['main_menu']) ?>
	</ul>
</div>

<div class="main-subhead">
	<h2 class="hn"><span><?php printf((ForumCore::$forum_page['own_profile']) ? ForumCore::$lang['Sig welcome'] : ForumCore::$lang['Sig welcome user'], forum_htmlencode(ForumUser::$user['username'])) ?></span></h2>
</div>

<div class="main-content main-frm">
<?php

	if (!empty(ForumCore::$forum_page['text_options']))
		echo "\t\t".'<p class="content-options options">'.sprintf(ForumCore::$lang['You may use'], implode(' ', ForumCore::$forum_page['text_options'])).'</p>'."\n";

	// If there were any errors, show them
	if (!empty(ForumCore::$errors))
	{
		ForumCore::$forum_page['errors'] = array();
		foreach (ForumCore::$errors as $cur_error)
			ForumCore::$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

		($hook = get_hook('pf_change_details_signature_pre_errors')) ? eval($hook) : null;

?>
	<div class="ct-box error-box">
		<h2 class="warn hn"><?php echo ForumCore::$lang['Profile update errors'] ?></h2>
		<ul class="error-list">
			<?php echo implode("\n\t\t\t\t\t", ForumCore::$forum_page['errors'])."\n" ?>
		</ul>
	</div>
<?php

	}

?>
	<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo esc_url( admin_url('admin-post.php?action=pun_profile&section='.$section) ); ?>">
		<div class="hidden">
			<input type="hidden" name="user_id" value="<?php echo ForumCore::$id ?>" />
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token( admin_url('admin-post.php?action=pun_profile&section='.$section) ); ?>" />
		</div>
<?php ($hook = get_hook('pf_change_details_signature_pre_fieldset')) ? eval($hook) : null; ?>
		<fieldset class="frm-group group<?php echo ++ForumCore::$forum_page['group_count'] ?>">
			<legend class="group-legend"><strong><?php echo ForumCore::$lang['Signature'] ?></strong></legend>
<?php ($hook = get_hook('pf_change_details_signature_pre_signature_demo')) ? eval($hook) : null; ?>
<?php if (isset(ForumCore::$forum_page['sig_demo'])): ?>
			<div class="ct-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
				<div class="ct-box">
					<h3 class="ct-legend hn"><?php echo ForumCore::$lang['Current signature'] ?></h3>
					<div class="sig-demo"><?php echo ForumCore::$forum_page['sig_demo'] ?></div>
				</div>
			</div>
<?php endif; ($hook = get_hook('pf_change_details_signature_pre_signature_text')) ? eval($hook) : null; ?>
			<div class="txt-set set<?php echo ++ForumCore::$forum_page['item_count'] ?>">
				<div class="txt-box textarea">
					<label for="fld<?php echo ++ForumCore::$forum_page['fld_count'] ?>"><span><?php echo ForumCore::$lang['Compose signature'] ?></span> <small><?php printf(ForumCore::$lang['Sig max size'], forum_number_format(ForumCore::$forum_config['p_sig_length']), forum_number_format(ForumCore::$forum_config['p_sig_lines'])) ?></small></label>
					<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo ForumCore::$forum_page['fld_count'] ?>" name="signature" rows="4" cols="65"><?php echo(isset($_POST['signature']) ? forum_htmlencode($_POST['signature']) : forum_htmlencode(ForumUser::$user['signature'])) ?></textarea></span></div>
				</div>
			</div>
<?php ($hook = get_hook('pf_change_details_signature_pre_fieldset_end')) ? eval($hook) : null; ?>
		</fieldset>
<?php ($hook = get_hook('pf_change_details_signature_fieldset_end')) ? eval($hook) : null; ?>
		<div class="frm-buttons">
			<span class="submit primary"><input type="submit" name="update" value="<?php echo ForumCore::$lang['Update profile'] ?>" /></span>
		</div>
	</form>
</div>
<?php

	($hook = get_hook('pf_change_details_signature_end')) ? eval($hook) : null;

});

