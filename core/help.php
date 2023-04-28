<?php
/**
 * Help page.
 *
 * Provides examples of how to use various features of the forum (ie: BBCode, smilies).
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

($hook = get_hook('he_start')) ? eval($hook) : null;

if (ForumUser::$forum_user['g_read_board'] == '0')
	message(ForumCore::$lang['No view']);

// Load the help.php language file
ForumCore::add_lang('help');

ForumCore::$section = isset($_GET['section']) ? $_GET['section'] : null;
if (!ForumCore::$section)
	message(ForumCore::$lang['Bad request']);

$forum_page['crumbs'] = array(
	array(ForumCore::$forum_config['o_board_title'], forum_link(ForumCore::$forum_url['help'])),
	ForumCore::$lang['Help']
);

define('FORUM_PAGE', 'help');
require FORUM_ROOT.'header.php';

// SHORTCODE [pun_content]
add_shortcode('pun_content', function ()
{



	($hook = get_hook('he_main_output_start')) ? eval($hook) : null;
?>
<div id="brd-main" class="main">

<div class="main-head">
	<h1 class="hn"><span><?php echo ForumCore::$lang['Help'] ?></span></h1>
</div>
<?php

	if (!ForumCore::$section || ForumCore::$section == 'bbcode')
	{

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php printf(ForumCore::$lang['Help with'], ForumCore::$lang['BBCode']) ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div class="ct-box info-box">
			<p><?php echo ForumCore::$lang['BBCode info'] ?></p>
		</div>
		<div class="ct-box help-box">
			<h3 class="hn"><span><?php echo ForumCore::$lang['Text style'] ?></span></h3>
			<div class="entry-content">
				<code>[b]<?php echo ForumCore::$lang['Bold text'] ?>[/b]</code> <span><?php echo ForumCore::$lang['produces'] ?></span>
				<samp><strong><?php echo ForumCore::$lang['Bold text'] ?></strong></samp>
			</div>
			<div class="entry-content">
				<code>[u]<?php echo ForumCore::$lang['Underlined text'] ?>[/u]</code> <span><?php echo ForumCore::$lang['produces'] ?></span>
				<samp><span class="bbu"><?php echo ForumCore::$lang['Underlined text'] ?></span></samp>
			</div>
			<div class="entry-content">
				<code>[i]<?php echo ForumCore::$lang['Italic text'] ?>[/i]</code> <span><?php echo ForumCore::$lang['produces'] ?></span>
				<samp><i><?php echo ForumCore::$lang['Italic text'] ?></i></samp>
			</div>
			<div class="entry-content">
				<code>[color=#FF0000]<?php echo ForumCore::$lang['Red text'] ?>[/color]</code> <span><?php echo ForumCore::$lang['produces'] ?></span>
				<samp><span style="color: #ff0000"><?php echo ForumCore::$lang['Red text'] ?></span></samp>
			</div>
			<div class="entry-content">
				<code>[color=blue]<?php echo ForumCore::$lang['Blue text'] ?>[/color]</code> <span><?php echo ForumCore::$lang['produces'] ?></span>
				<samp><span style="color: blue"><?php echo ForumCore::$lang['Blue text'] ?></span></samp>
			</div>
			<div class="entry-content">
				<code>[b][u]<?php echo ForumCore::$lang['Bold, underlined text'] ?>[/u][/b]</code> <span><?php echo ForumCore::$lang['produces'] ?></span>
				<samp><span class="bbu"><strong><?php echo ForumCore::$lang['Bold, underlined text'] ?></strong></span></samp>
			</div>
			<div class="entry-content">
				<code>[h]<?php echo ForumCore::$lang['Heading text'] ?>[/h]</code> <span><?php echo ForumCore::$lang['produces'] ?></span>
				<div class="entry-content"><h5><samp><?php echo ForumCore::$lang['Heading text'] ?></samp></h5></div>
			</div>
<?php ($hook = get_hook('he_new_bbcode_text_style')) ? eval($hook) : null; ?>
		</div>
		<div class="ct-box help-box">
			<h3 class="hn"><span><?php echo ForumCore::$lang['Links info'] ?></span></h3>
			<div class="entry-content">
				<code>[url=<?php echo ForumCore::$base_url.'/' ?>]<?php echo forum_htmlencode(ForumCore::$forum_config['o_board_title']) ?>[/url]</code> <span><?php echo ForumCore::$lang['produces'] ?></span>
				<samp><a href="<?php echo ForumCore::$base_url.'/' ?>"><?php echo forum_htmlencode(ForumCore::$forum_config['o_board_title']) ?></a></samp>
			</div>
			<div class="entry-content">
				<code>[url]<?php echo ForumCore::$base_url.'/' ?>[/url]</code> <span><?php echo ForumCore::$lang['produces'] ?></span>
				<samp><a href="<?php echo ForumCore::$base_url ?>"><?php echo ForumCore::$base_url.'/' ?></a></samp>
			</div>
			<div class="entry-content">
				<code>[email]name@example.com[/email]</code> <span><?php echo ForumCore::$lang['produces'] ?></span>
				<samp><a href="mailto:name@example.com">name@example.com</a></samp>
			</div>
			<div class="entry-content">
				<code>[email=name@example.com]<?php echo ForumCore::$lang['My e-mail address'] ?>[/email]</code> <span><?php echo ForumCore::$lang['produces'] ?></span>
				<samp><a href="mailto:name@example.com"><?php echo ForumCore::$lang['My e-mail address'] ?></a></samp>
			</div>
<?php ($hook = get_hook('he_new_bbcode_link')) ? eval($hook) : null; ?>
		</div>
		<div class="ct-box help-box">
			<h3 class="hn"><span><?php echo ForumCore::$lang['Quotes info'] ?></span></h3>
			<div class="entry-content">
				<code>[quote=James]<?php echo ForumCore::$lang['Quote text'] ?>[/quote]</code> <span><?php echo ForumCore::$lang['produces named'] ?></span>
				<div class="quotebox"><cite>James <?php echo ForumCore::$lang['wrote'] ?>:</cite><blockquote><p><?php echo ForumCore::$lang['Quote text'] ?></p></blockquote></div>
				<code>[quote]<?php echo ForumCore::$lang['Quote text'] ?>[/quote]</code> <span><?php echo ForumCore::$lang['produces unnamed'] ?></span>
				<div class="quotebox"><blockquote><p><?php echo ForumCore::$lang['Quote text'] ?></p></blockquote></div>
			</div>
		</div>
		<div class="ct-box help-box">
			<h3 class="hn"><span><?php echo ForumCore::$lang['Code info'] ?></span></h3>
			<div class="entry-content">
				<code>[code]<?php echo ForumCore::$lang['Code text'] ?>[/code]</code> <span><?php echo ForumCore::$lang['produces code box'] ?></span>
				<div class="codebox"><pre><code><?php echo ForumCore::$lang['Code text'] ?></code></pre></div>
				<code>[code]<?php echo ForumCore::$lang['Code text long'] ?>[/code]</code> <span><?php echo ForumCore::$lang['produces scroll box'] ?></span>
				<div class="codebox"><pre><code><?php echo ForumCore::$lang['Code text long'] ?></code></pre></div>
			</div>
		</div>
		<div class="ct-box help-box">
			<h3 class="hn"><span><?php echo ForumCore::$lang['List info'] ?></span></h3>
			<div class="entry-content">
				<code>[list][*]<?php echo ForumCore::$lang['List text 1'] ?>[/*][*]<?php echo ForumCore::$lang['List text 2'] ?>[/*][*]<?php echo ForumCore::$lang['List text 3'] ?>[/*][/list]</code> <span><?php echo ForumCore::$lang['produces list'] ?></span>
				<ul><li><?php echo ForumCore::$lang['List text 1'] ?></li><li><?php echo ForumCore::$lang['List text 2'] ?></li><li><?php echo ForumCore::$lang['List text 3'] ?></li></ul>
				<code>[list=1][*]<?php echo ForumCore::$lang['List text 1'] ?>[/*][*]<?php echo ForumCore::$lang['List text 2'] ?>[/*][*]<?php echo ForumCore::$lang['List text 3'] ?>[/*][/list]</code> <span><?php echo ForumCore::$lang['produces decimal list'] ?></span>
				<ol class="decimal"><li><?php echo ForumCore::$lang['List text 1'] ?></li><li><?php echo ForumCore::$lang['List text 2'] ?></li><li><?php echo ForumCore::$lang['List text 3'] ?></li></ol>
				<code>[list=a][*]<?php echo ForumCore::$lang['List text 1'] ?>[/*][*]<?php echo ForumCore::$lang['List text 2'] ?>[/*][*]<?php echo ForumCore::$lang['List text 3'] ?>[/*][/list]</code> <span><?php echo ForumCore::$lang['produces alpha list'] ?></span>
				<ol class="alpha"><li><?php echo ForumCore::$lang['List text 1'] ?></li><li><?php echo ForumCore::$lang['List text 2'] ?></li><li><?php echo ForumCore::$lang['List text 3'] ?></li></ol>
			</div>
		</div>
<?php ($hook = get_hook('he_new_bbcode_section')) ? eval($hook) : null; ?>
	</div>
<?php

	}
	else if (ForumCore::$section == 'img')
	{

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php printf(ForumCore::$lang['Help with'], ForumCore::$lang['Images']) ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div class="ct-box help-box">
			<p class="hn"><?php echo ForumCore::$lang['Image info'] ?></p>
			<div class="entry-content">
				<code>[img=HiveBB bbcode test]<?php echo ForumCore::$dir_url ?>img/test.png[/img]</code>
				<samp><img src="<?php echo ForumCore::$dir_url ?>img/test.png" alt="HiveBB bbcode test" /></samp>
			</div>
		</div>
		<?php ($hook = get_hook('he_new_img_section')) ? eval($hook) : null; ?>
	</div>
<?php

	}
	else if (ForumCore::$section == 'smilies')
	{

?>
	<div id="smilies" class="main-subhead">
		<h2 class="hn"><span><?php printf(ForumCore::$lang['Help with'], ForumCore::$lang['Smilies']) ?></span></h2>
	</div>

	<div class="main-content main-frm">
		<div class="ct-box help-box">
			<p class="hn"><?php echo ForumCore::$lang['Smilies info'] ?></p>
			<div class="entry-content">
<?php

	// Display the smiley set
	if (!defined('FORUM_PARSER_LOADED'))
		require FORUM_ROOT.'include/parser.php';

	$smiley_groups = array();

	($hook = get_hook('he_pre_smile_display')) ? eval($hook) : null;

	$smilies = pun_default_smiles();
	foreach ($smilies as $smiley_text => $smiley_img)
		$smiley_groups[$smiley_img][] = $smiley_text;

	foreach ($smiley_groups as $smiley_img => $smiley_texts)
		echo "\t\t\t\t".'<p>'.implode(' '.ForumCore::$lang['and'].' ', $smiley_texts).' <span>'.ForumCore::$lang['produces'].'</span> <img src="'.ForumCore::$dir_url.'img/smilies/'.$smiley_img.'" width="15" height="15" alt="'.$smiley_texts[0].'" /></p>'."\n";

?>
			</div>
		</div>
	</div>
<?php

	}

	($hook = get_hook('he_new_section')) ? eval($hook) : null;

?>

</div>
<?php

	($hook = get_hook('he_end')) ? eval($hook) : null;

});

require FORUM_ROOT.'footer.php';
