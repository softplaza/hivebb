<?php
/**
 * @package functions.php
 */
use \HiveBB\ForumCore;
use \HiveBB\DBLayer;
use \HiveBB\ForumUser;

defined( 'ABSPATH' ) OR die();

// Load CSS files
function softplaza_hivebb_load_css()
{
	wp_enqueue_style(
		'hivebb-main-css', 
		HIVEBB_PLUGIN_URL . 'core/style/Oxygen/Oxygen.min.css',
		false,
		'1.0.12',
		'all'
	);

    wp_enqueue_style(
		'hivebb-custom', 
		HIVEBB_PLUGIN_URL . 'assets/css/custom.css',
		false,
		time(), //'1.1.2',
		'all'
	);

    wp_enqueue_style(
		'hivebb-bootstrap-min', 
		HIVEBB_PLUGIN_URL . 'vendor/bootstrap/css/bootstrap.min.css'
	);
}

add_action('wp_footer', function()
{
?>
<script>if (typeof PUNBB === 'undefined' || !PUNBB) {
		var PUNBB = {};
	}

	PUNBB.env = {
		base_url: "<?php echo home_url() ?>/",
		base_js_url: "<?php echo FORUM_BASE_URL ?>/include/js/",
		user_lang: "English",
		user_style: "Default",
		user_is_guest: "0",
		page: "viewtopic"
	};</script>
<?php
});


// Load JS files
function softplaza_hivebb_load_js()
{
	wp_enqueue_script(
		'hivebb-common-min', 
		HIVEBB_PLUGIN_URL . 'core/include/js/min/punbb.common.min.js', 
		array(),
		'2.0.3', 
		true
	);
	/*
    wp_enqueue_script(
        'bootstrap-bundle-min', 
        HIVEBB_PLUGIN_URL . 'vendor/bootstrap/js/bootstrap.bundle.min.js', 
		array(),
        '5.1', 
        true
    );
	*/
}

$AdminMenu = new \HiveBB\AdminMenu;

// 
add_action( 'template_redirect', function ()
{
	$forum_pages = [
		'forum'         => 'Forum',
		'viewforum'     => 'Topics',
		'viewtopic'     => 'Posts',
		'userlist'      => 'Userlist',
		'profile'       => 'Profile',
		'search'        => 'Search',
		'post'        	=> 'Post',
		'edit'        	=> 'Edit',
		'delete'        => 'Delete',
		'report'        => 'Report',
		'moderate'      => 'Moderate',
		'misc'          => 'Misc',
		'help'          => 'Help',
		'extern'        => 'Extern',
	];

    if ( is_page( array_keys($forum_pages) ) )
	{
		wp_enqueue_style(
			'hivebb-main', 
			HIVEBB_PLUGIN_URL . 'core/style/Oxygen/Oxygen.min.css',
			false,
			time(), //'1.0.14',
			'all'
		);

		wp_enqueue_script(
			'hivebb-common-min', 
			HIVEBB_PLUGIN_URL . 'core/include/js/min/punbb.common.min.js', 
			array(),
			time(), //'2.0.3', 
			true
		);

/*
		wp_enqueue_style(
			'hivebb-bootstrap-min', 
			HIVEBB_PLUGIN_URL . 'vendor/bootstrap/css/bootstrap.min.css'
		);

		wp_enqueue_script(
			'bootstrap-bundle-min', 
			HIVEBB_PLUGIN_URL . 'vendor/bootstrap/js/bootstrap.bundle.min.js', 
			'', 
			true
		);
*/
    }
	
	foreach($forum_pages as $page_id => $page_name)
	{
		if ( is_page( $page_id ) ) {
			require FORUM_ROOT . $page_id . '.php';
		}
	}
});

add_action( 'user_register', function ( $user_id )
{
	define('FORUM_DISABLE_CSRF_CONFIRM', 1);
	require FORUM_ROOT.'include/common.php';

	$forum_db = new DBLayer;

	$user_info = get_userdata($user_id);
	$now = time();
	$query = array(
		'INSERT'	=> 'wp_user_id, group_id, username, email, language, style, registered, registration_ip, last_visit',
		'INTO'		=> 'users',
		'VALUES'	=> ''.$user_id.', '.ForumCore::$forum_config['o_default_user_group'].', \''.$forum_db->escape($user_info->user_login).'\', \''.$forum_db->escape($user_info->user_email).'\', \''.$forum_db->escape(ForumCore::$forum_config['o_default_lang']).'\', \''.$forum_db->escape(ForumCore::$forum_config['o_default_style']).'\', '.$now.', \''.$forum_db->escape(get_remote_address()).'\', '.$now.''
	);
	$forum_db->query_build($query) or $forum_db->error();
});

// Admin Settings
add_action('admin_post_pun_admin_settings', function()
{
	require FORUM_ROOT.'include/common.php';

	// Load the admin.php language file
	ForumCore::add_lang('admin_common');
	ForumCore::add_lang('admin_settings');

	$forum_db = new DBLayer;

	$section = isset($_GET['section']) ? $_GET['section'] : null;
	if (isset($_POST['form_sent']))
	{
		$form = array_map('trim', $_POST['form']);

		($hook = get_hook('aop_form_submitted')) ? eval($hook) : null;

		// Validate input depending on section
		switch ($section)
		{
			case 'setup':
			{
				($hook = get_hook('aop_setup_validation')) ? eval($hook) : null;

				if ($form['board_title'] == '')
					message(ForumCore::$lang['Error no board title']);

				// Clean default_lang, default_style, and sef
				$form['default_style'] = preg_replace('#[\.\\\/]#', '', $form['default_style']);
				$form['default_lang'] = preg_replace('#[\.\\\/]#', '', $form['default_lang']);
				$form['sef'] = preg_replace('#[\.\\\/]#', '', $form['sef']);

				// Make sure default_lang, default_style, and sef exist
				if (!file_exists(FORUM_ROOT.'style/'.$form['default_style'].'/'.$form['default_style'].'.php'))
					message(ForumCore::$lang['Bad request']);
				if (!file_exists(FORUM_ROOT.'lang/'.$form['default_lang'].'/common.php'))
					message(ForumCore::$lang['Bad request']);
				if (!file_exists(FORUM_ROOT.'include/url/'.$form['sef'].'/forum_urls.php'))
					message(ForumCore::$lang['Bad request']);
				if (!isset($form['default_dst']) || $form['default_dst'] != '1')
					$form['default_dst'] = '0';

				$form['timeout_visit'] = intval($form['timeout_visit']);
				$form['timeout_online'] = intval($form['timeout_online']);
				$form['redirect_delay'] = intval($form['redirect_delay']);

				if ($form['timeout_online'] >= $form['timeout_visit'])
					message(ForumCore::$lang['Error timeout value']);

				$form['disp_topics_default'] = (intval($form['disp_topics_default']) > 0) ? intval($form['disp_topics_default']) : 1;
				$form['disp_posts_default'] = (intval($form['disp_posts_default']) > 0) ? intval($form['disp_posts_default']) : 1;

				if ($form['additional_navlinks'] != '')
					$form['additional_navlinks'] = forum_trim(forum_linebreaks($form['additional_navlinks']));

				break;
			}

			case 'features':
			{
				($hook = get_hook('aop_features_validation')) ? eval($hook) : null;

				if (!isset($form['search_all_forums']) || $form['search_all_forums'] != '1') $form['search_all_forums'] = '0';
				if (!isset($form['ranks']) || $form['ranks'] != '1') $form['ranks'] = '0';
				if (!isset($form['censoring']) || $form['censoring'] != '1') $form['censoring'] = '0';
				if (!isset($form['quickjump']) || $form['quickjump'] != '1') $form['quickjump'] = '0';
				if (!isset($form['show_version']) || $form['show_version'] != '1') $form['show_version'] = '0';
				if (!isset($form['show_moderators']) || $form['show_moderators'] != '1') $form['show_moderators'] = '0';
				if (!isset($form['users_online']) || $form['users_online'] != '1') $form['users_online'] = '0';

				if (!isset($form['quickpost']) || $form['quickpost'] != '1') $form['quickpost'] = '0';
				if (!isset($form['subscriptions']) || $form['subscriptions'] != '1') $form['subscriptions'] = '0';
				if (!isset($form['force_guest_email']) || $form['force_guest_email'] != '1') $form['force_guest_email'] = '0';
				if (!isset($form['show_dot']) || $form['show_dot'] != '1') $form['show_dot'] = '0';
				if (!isset($form['topic_views']) || $form['topic_views'] != '1') $form['topic_views'] = '0';
				if (!isset($form['show_post_count']) || $form['show_post_count'] != '1') $form['show_post_count'] = '0';
				if (!isset($form['show_user_info']) || $form['show_user_info'] != '1') $form['show_user_info'] = '0';

				if (!isset($form['message_bbcode']) || $form['message_bbcode'] != '1') $form['message_bbcode'] = '0';
				if (!isset($form['message_img_tag']) || $form['message_img_tag'] != '1') $form['message_img_tag'] = '0';
				if (!isset($form['smilies']) || $form['smilies'] != '1') $form['smilies'] = '0';
				if (!isset($form['make_links']) || $form['make_links'] != '1') $form['make_links'] = '0';
				if (!isset($form['message_all_caps']) || $form['message_all_caps'] != '1') $form['message_all_caps'] = '0';
				if (!isset($form['subject_all_caps']) || $form['subject_all_caps'] != '1') $form['subject_all_caps'] = '0';

				$form['indent_num_spaces'] = intval($form['indent_num_spaces']);
				$form['quote_depth'] = intval($form['quote_depth']);

				if (!isset($form['signatures']) || $form['signatures'] != '1') $form['signatures'] = '0';
				if (!isset($form['sig_bbcode']) || $form['sig_bbcode'] != '1') $form['sig_bbcode'] = '0';
				if (!isset($form['sig_img_tag']) || $form['sig_img_tag'] != '1') $form['sig_img_tag'] = '0';
				if (!isset($form['smilies_sig']) || $form['smilies_sig'] != '1') $form['smilies_sig'] = '0';
				if (!isset($form['sig_all_caps']) || $form['sig_all_caps'] != '1') $form['sig_all_caps'] = '0';

				$form['sig_length'] = intval($form['sig_length']);
				$form['sig_lines'] = intval($form['sig_lines']);

				if (!isset($form['avatars']) || $form['avatars'] != '1') $form['avatars'] = '0';

				// Make sure avatars_dir doesn't end with a slash
				if (substr($form['avatars_dir'], -1) == '/')
					$form['avatars_dir'] = substr($form['avatars_dir'], 0, -1);

				$form['avatars_width'] = intval($form['avatars_width']);
				$form['avatars_height'] = intval($form['avatars_height']);
				$form['avatars_size'] = intval($form['avatars_size']);

				if (!isset($form['check_for_updates']) || $form['check_for_updates'] != '1') $form['check_for_updates'] = '0';
				if (!isset($form['check_for_versions']) || $form['check_for_versions'] != '1') $form['check_for_versions'] = '0';

				if (!isset($form['mask_passwords']) || $form['mask_passwords'] != '1') $form['mask_passwords'] = '0';
				if (!isset($form['gzip']) || $form['gzip'] != '1') $form['gzip'] = '0';

				break;
			}

			case 'email':
			{
				($hook = get_hook('aop_email_validation')) ? eval($hook) : null;

				if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
					require FORUM_ROOT.'include/email.php';

				$form['admin_email'] = strtolower($form['admin_email']);
				if (!is_valid_email($form['admin_email']))
					message(ForumCore::$lang['Error invalid admin e-mail']);

				$form['webmaster_email'] = strtolower($form['webmaster_email']);
				if (!is_valid_email($form['webmaster_email']))
					message(ForumCore::$lang['Error invalid web e-mail']);

				if (!isset($form['smtp_ssl']) || $form['smtp_ssl'] != '1') $form['smtp_ssl'] = '0';

				break;
			}

			case 'announcements':
			{
				($hook = get_hook('aop_announcements_validation')) ? eval($hook) : null;

				if (!isset($form['announcement']) || $form['announcement'] != '1') $form['announcement'] = '0';

				if ($form['announcement_message'] != '')
					$form['announcement_message'] = forum_linebreaks($form['announcement_message']);
				else
					$form['announcement_message'] = ForumCore::$lang['Announcement message default'];

				break;
			}

			case 'registration':
			{
				($hook = get_hook('aop_registration_validation')) ? eval($hook) : null;

				if (!isset($form['regs_allow']) || $form['regs_allow'] != '1') $form['regs_allow'] = '0';
				if (!isset($form['regs_verify']) || $form['regs_verify'] != '1') $form['regs_verify'] = '0';
				if (!isset($form['allow_banned_email']) || $form['allow_banned_email'] != '1') $form['allow_banned_email'] = '0';
				if (!isset($form['allow_dupe_email']) || $form['allow_dupe_email'] != '1') $form['allow_dupe_email'] = '0';
				if (!isset($form['regs_report']) || $form['regs_report'] != '1') $form['regs_report'] = '0';

				if (!isset($form['rules']) || $form['rules'] != '1') $form['rules'] = '0';

				if ($form['rules_message'] != '')
					$form['rules_message'] = forum_linebreaks($form['rules_message']);
				else
					$form['rules_message'] = ForumCore::$lang['Rules default'];

				break;
			}

			case 'maintenance':
			{
				($hook = get_hook('aop_maintenance_validation')) ? eval($hook) : null;

				if (!isset($form['maintenance']) || $form['maintenance'] != '1') $form['maintenance'] = '0';

				if ($form['maintenance_message'] != '')
					$form['maintenance_message'] = forum_linebreaks($form['maintenance_message']);
				else
					$form['maintenance_message'] = ForumCore::$lang['Maintenance message default'];

				break;
			}

			default:
			{
				($hook = get_hook('aop_new_section_validation')) ? eval($hook) : null;
				break;
			}
		}

		($hook = get_hook('aop_pre_update_configuration')) ? eval($hook) : null;

		foreach ($form as $key => $input)
		{
			// Only update permission values that have changed
			if (array_key_exists('p_'.$key, ForumCore::$forum_config) && ForumCore::$forum_config['p_'.$key] != $input)
			{
				$query = array(
					'UPDATE'	=> 'config',
					'SET'		=> 'conf_value='.intval($input),
					'WHERE'		=> 'conf_name=\'p_'.$forum_db->escape($key).'\''
				);

				($hook = get_hook('aop_qr_update_permission_conf')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}

			// Only update option values that have changed
			if (array_key_exists('o_'.$key, ForumCore::$forum_config) && ForumCore::$forum_config['o_'.$key] != $input)
			{
				if ($input != '' || is_int($input))
					$value = '\''.$forum_db->escape($input).'\'';
				else
					$value = 'NULL';

				$query = array(
					'UPDATE'	=> 'config',
					'SET'		=> 'conf_value='.$value,
					'WHERE'		=> 'conf_name=\'o_'.$forum_db->escape($key).'\''
				);

				($hook = get_hook('aop_qr_update_permission_option')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
		}

		// Regenerate the config cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_config_cache();

		// If changed sef - remove quick-jump cache
		if (!empty(ForumCore::$forum_config['o_sef']) && !empty($form['sef']))
		{
			if (ForumCore::$forum_config['o_sef'] != $form['sef'])
			{
				clean_quickjump_cache();
			}
		}

		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Settings updated']);

		($hook = get_hook('aop_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_settings_'.$section]), ForumCore::$lang['Settings updated']);
	}
});

// Admin Categories
add_action('admin_post_pun_admin_categories', function()
{
	require FORUM_ROOT.'include/common.php';

	// Load the admin.php language file
	ForumCore::add_lang('admin_common');
	ForumCore::add_lang('admin_categories');

	$forum_db = new DBLayer;
	
	// Add a new category
	if (isset($_POST['add_cat']))
	{
		$new_cat_name = forum_trim($_POST['new_cat_name']);
		if ($new_cat_name == '')
			message(ForumCore::$lang['Must name category']);
	
		$new_cat_pos = intval($_POST['position']);
	
		($hook = get_hook('acg_add_cat_form_submitted')) ? eval($hook) : null;
	
		$query = array(
			'INSERT'	=> 'cat_name, disp_position',
			'INTO'		=> 'categories',
			'VALUES'	=> '\''.$forum_db->escape($new_cat_name).'\', '.$new_cat_pos
		);
	
		($hook = get_hook('acg_add_cat_qr_add_category')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	
		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Category added']);
	
		($hook = get_hook('acg_add_cat_pre_redirect')) ? eval($hook) : null;
	
		redirect(pun_admin_link(ForumCore::$forum_url['admin_categories']), ForumCore::$lang['Category added']);
	}
	
	// User pressed the cancel button
	else if (isset($_POST['del_cat_cancel']))
	{
		redirect(pun_admin_link(ForumCore::$forum_url['admin_categories']), ForumCore::$lang['Cancel redirect']);
	}

	// Delete a category with all forums and posts
	else if (isset($_POST['del_cat_comply']))
	{
		$cat_to_delete = intval($_POST['cat_to_delete']);
		if ($cat_to_delete < 1)
			message(ForumCore::$lang['Bad request']);
	
		($hook = get_hook('acg_del_cat_form_submitted')) ? eval($hook) : null;
	
		@set_time_limit(0);

		$query = array(
			'SELECT'	=> 'f.id',
			'FROM'		=> 'forums AS f',
			'WHERE'		=> 'cat_id='.$cat_to_delete
		);

		($hook = get_hook('acg_del_cat_qr_get_forums_to_delete')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$forum_ids = array();
		while ($cur_forum_id = $forum_db->fetch_assoc($result)) {
			$forum_ids[] = $cur_forum_id['id'];
		}

		if (!empty($forum_ids))
		{
			foreach ($forum_ids as $cur_forum)
			{
				// Prune all posts and topics
				prune($cur_forum, 1, -1);

				// Delete the forum
				$query = array(
					'DELETE'	=> 'forums',
					'WHERE'		=> 'id='.$cur_forum
				);

				($hook = get_hook('acg_del_cat_qr_delete_forum')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				// Delete any forum subscriptions
				$query = array(
					'DELETE'	=> 'forum_subscriptions',
					'WHERE'		=> 'forum_id='.$cur_forum
				);

				($hook = get_hook('acg_del_cat_qr_delete_forum_subscriptions')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
		}

		delete_orphans();

		// Delete the category
		$query = array(
			'DELETE'	=> 'categories',
			'WHERE'		=> 'id='.$cat_to_delete
		);

		($hook = get_hook('acg_del_cat_qr_delete_category')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Regenerate the quickjump cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_quickjump_cache();

		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Category deleted']);

		($hook = get_hook('acg_del_cat_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_categories']), ForumCore::$lang['Category deleted']);
	}
	
	// Change position and name of the categories
	else if (isset($_POST['update']))	
	{
		$cat_order = array_map('intval', $_POST['cat_order']);
		$cat_name = array_map('forum_trim', $_POST['cat_name']);
	
		($hook = get_hook('acg_update_cats_form_submitted')) ? eval($hook) : null;
	
		$query = array(
			'SELECT'	=> 'c.id, c.cat_name, c.disp_position',
			'FROM'		=> 'categories AS c',
			'ORDER BY'	=> 'c.id'
		);
	
		($hook = get_hook('acg_update_cats_qr_get_categories')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		while ($cur_cat = $forum_db->fetch_assoc($result))
		{
			// If these aren't set, we're looking at a category that was added after
			// the admin started editing: we don't want to mess with it
			if (isset($cat_name[$cur_cat['id']]) && isset($cat_order[$cur_cat['id']]))
			{
				if ($cat_name[$cur_cat['id']] == '')
					message(ForumCore::$lang['Must name category']);
	
				if ($cat_order[$cur_cat['id']] < 0)
					message(ForumCore::$lang['Must be integer']);
	
				// We only want to update if we changed anything
				if ($cur_cat['cat_name'] != $cat_name[$cur_cat['id']] || $cur_cat['disp_position'] != $cat_order[$cur_cat['id']])
				{
					$query = array(
						'UPDATE'	=> 'categories',
						'SET'		=> 'cat_name=\''.$forum_db->escape($cat_name[$cur_cat['id']]).'\', disp_position='.$cat_order[$cur_cat['id']],
						'WHERE'		=> 'id='.$cur_cat['id']
					);
	
					($hook = get_hook('acg_update_cats_qr_update_category')) ? eval($hook) : null;
					$forum_db->query_build($query) or error(__FILE__, __LINE__);
				}
			}
		}
	
		// Regenerate the quickjump cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';
	
		generate_quickjump_cache();
	
		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Categories updated']);
	
		($hook = get_hook('acg_update_cats_pre_redirect')) ? eval($hook) : null;
	
		redirect(pun_admin_link(ForumCore::$forum_url['admin_categories']), ForumCore::$lang['Categories updated']);
	}
});

// Admin Forums
add_action('admin_post_pun_admin_forums', function()
{
	require FORUM_ROOT.'include/common.php';

	// Load the admin.php language file
	ForumCore::add_lang('admin_common');
	ForumCore::add_lang('admin_forums');

	$forum_db = new DBLayer;

	// Add a "default" forum
	if (isset($_POST['add_forum']))
	{
		$add_to_cat = isset($_POST['add_to_cat']) ? intval($_POST['add_to_cat']) : 0;
		if ($add_to_cat < 1)
			message(ForumCore::$lang['Bad request']);

		$forum_name = forum_trim($_POST['forum_name']);
		$position = intval($_POST['position']);

		if ($forum_name == '')
			message(ForumCore::$lang['Must enter forum message']);

		// Make sure the category we're adding to exists
		$query = array(
			'SELECT'	=> 'COUNT(c.id)',
			'FROM'		=> 'categories AS c',
			'WHERE'		=> 'c.id='.$add_to_cat
		);
		($hook = get_hook('afo_add_forum_qr_validate_category_id')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($forum_db->result($result) != 1)
			message(ForumCore::$lang['Bad request']);


		$query = array(
			'INSERT'	=> 'forum_name, disp_position, cat_id',
			'INTO'		=> 'forums',
			'VALUES'	=> '\''.$forum_db->escape($forum_name).'\', '.$position.', '.$add_to_cat
		);
		($hook = get_hook('afo_add_forum_qr_add_forum')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Regenerate the quickjump cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_quickjump_cache();

		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Forum added']);

		($hook = get_hook('afo_add_forum_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_forums']), ForumCore::$lang['Forum added']);
	}

	// User pressed the cancel button
	else if (isset($_POST['del_forum_cancel']))
	{
		redirect(pun_admin_link(ForumCore::$forum_url['admin_forums']), ForumCore::$lang['Cancel redirect']);
	}

	// Delete a forum with all posts
	else if (isset($_POST['del_forum_comply']))	
	{
		$forum_to_delete = intval($_POST['forum_id']);
		if ($forum_to_delete < 1)
			message(ForumCore::$lang['Bad request']);

		($hook = get_hook('afo_del_forum_form_submitted')) ? eval($hook) : null;

		@set_time_limit(0);

		// Prune all posts and topics
		prune($forum_to_delete, 1, -1);

		delete_orphans();

		// Delete the forum and any forum specific group permissions
		$query = array(
			'DELETE'	=> 'forums',
			'WHERE'		=> 'id='.$forum_to_delete
		);

		($hook = get_hook('afo_del_forum_qr_delete_forum')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		$query = array(
			'DELETE'	=> 'forum_perms',
			'WHERE'		=> 'forum_id='.$forum_to_delete
		);

		($hook = get_hook('afo_del_forum_qr_delete_forum_perms')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Delete forum subscriptions
		$query = array(
			'DELETE'	=> 'forum_subscriptions',
			'WHERE'		=> 'forum_id='.$forum_to_delete
		);

		($hook = get_hook('afo_del_forum_qr_delete_forum_subscriptions')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Regenerate the quickjump cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_quickjump_cache();

		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Forum deleted']);

		($hook = get_hook('afo_del_forum_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_forums']), ForumCore::$lang['Forum deleted']);
	}

	// Update forum positions
	else if (isset($_POST['update_positions']))
	{
		$positions = array_map('intval', $_POST['position']);

		($hook = get_hook('afo_update_positions_form_submitted')) ? eval($hook) : null;

		$query = array(
			'SELECT'	=> 'f.id, f.disp_position',
			'FROM'		=> 'categories AS c',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'forums AS f',
					'ON'			=> 'c.id=f.cat_id'
				)
			),
			'ORDER BY'	=> 'c.disp_position, c.id, f.disp_position'
		);

		($hook = get_hook('afo_update_positions_qr_get_forums')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		while ($cur_forum = $forum_db->fetch_assoc($result))
		{
			// If these aren't set, we're looking at a forum that was added after
			// the admin started editing: we don't want to mess with it
			if (isset($positions[$cur_forum['id']]))
			{
				$new_disp_position = $positions[$cur_forum['id']];

				if ($new_disp_position < 0)
					message(ForumCore::$lang['Must be integer']);

				// We only want to update if we changed the position
				if ($cur_forum['disp_position'] != $new_disp_position)
				{
					$query = array(
						'UPDATE'	=> 'forums',
						'SET'		=> 'disp_position='.$new_disp_position,
						'WHERE'		=> 'id='.$cur_forum['id']
					);

					($hook = get_hook('afo_update_positions_qr_update_forum_position')) ? eval($hook) : null;
					$forum_db->query_build($query) or error(__FILE__, __LINE__);
				}
			}
		}

		// Regenerate the quickjump cache
		require_once FORUM_ROOT.'include/cache.php';
		generate_quickjump_cache();

		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Forums updated']);

		($hook = get_hook('afo_update_positions_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_forums']), ForumCore::$lang['Forums updated']);
	}


	// Update group permissions for $forum_id
	if (isset($_POST['save']))
	{
		$forum_id = intval($_POST['forum_id']);
		if ($forum_id < 1)
			message(ForumCore::$lang['Bad request']);

		// Fetch forum info
		$query = array(
			'SELECT'	=> 'f.id, f.forum_name, f.forum_desc, f.redirect_url, f.num_topics, f.sort_by, f.cat_id',
			'FROM'		=> 'forums AS f',
			'WHERE'		=> 'f.id='.$forum_id
		);

		($hook = get_hook('afo_edit_forum_qr_get_forum_details')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$cur_forum = $forum_db->fetch_assoc($result);

		// Start with the forum details
		$forum_name = forum_trim($_POST['forum_name']);
		$forum_desc = forum_linebreaks(forum_trim($_POST['forum_desc']));
		$cat_id = intval($_POST['cat_id']);
		$sort_by = intval($_POST['sort_by']);
		$redirect_url = isset($_POST['redirect_url']) && $cur_forum['num_topics'] == 0 ? forum_trim($_POST['redirect_url']) : null;

		($hook = get_hook('afo_save_forum_form_submitted')) ? eval($hook) : null;

		if ($forum_name == '')
			message(ForumCore::$lang['Must enter forum message']);

		if ($cat_id < 1)
			message(ForumCore::$lang['Bad request']);

		$forum_desc = ($forum_desc != '') ? '\''.$forum_db->escape($forum_desc).'\'' : 'NULL';
		$redirect_url = ($redirect_url != '') ? '\''.$forum_db->escape($redirect_url).'\'' : 'NULL';

		$query = array(
			'UPDATE'	=> 'forums',
			'SET'		=> 'forum_name=\''.$forum_db->escape($forum_name).'\', forum_desc='.$forum_desc.', redirect_url='.$redirect_url.', sort_by='.$sort_by.', cat_id='.$cat_id,
			'WHERE'		=> 'id='.$forum_id
		);

		($hook = get_hook('afo_save_forum_qr_update_forum')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Now let's deal with the permissions
		if (isset($_POST['read_forum_old']))
		{
			$query = array(
				'SELECT'	=> 'g.g_id, g.g_read_board, g.g_post_replies, g.g_post_topics',
				'FROM'		=> 'groups AS g',
				'WHERE'		=> 'g_id!='.FORUM_ADMIN
			);

			($hook = get_hook('afo_save_forum_qr_get_groups')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			while ($cur_group = $forum_db->fetch_assoc($result))
			{
				// The default permissions for this group
				$perms_default = array(
					'read_forum'	=>	$cur_group['g_read_board'],
					'post_replies'	=>	$cur_group['g_post_replies'],
					'post_topics'	=>	$cur_group['g_post_topics']
				);

				// The old permissions for this group
				$perms_old = array(
					'read_forum'	=>	$_POST['read_forum_old'][$cur_group['g_id']],
					'post_replies'	=>	$_POST['post_replies_old'][$cur_group['g_id']],
					'post_topics'	=>	$_POST['post_topics_old'][$cur_group['g_id']]
				);

				// The new permissions for this group
				$perms_new = array(
					'read_forum'	=>	$cur_group['g_read_board'] == '1' ? (isset($_POST['read_forum_new'][$cur_group['g_id']]) ? '1' : '0') : intval($_POST['read_forum_old'][$cur_group['g_id']]),
					'post_replies'	=>	isset($_POST['post_replies_new'][$cur_group['g_id']]) ? '1' : '0',
					'post_topics'	=>	isset($_POST['post_topics_new'][$cur_group['g_id']]) ? '1' : '0'
				);

				($hook = get_hook('afo_save_forum_pre_perms_compare')) ? eval($hook) : null;

				// Force all permissions values to integers
				$perms_default = array_map('intval', $perms_default);
				$perms_old = array_map('intval', $perms_old);
				$perms_new = array_map('intval', $perms_new);

				// Check if the new permissions differ from the old
				if ($perms_new !== $perms_old)
				{
					// If the new permissions are identical to the default permissions for this group, delete its row in forum_perms
					if ($perms_new === $perms_default)
					{
						$query = array(
							'DELETE'	=> 'forum_perms',
							'WHERE'		=> 'group_id='.$cur_group['g_id'].' AND forum_id='.$forum_id
						);

						($hook = get_hook('afo_save_forum_qr_delete_group_forum_perms')) ? eval($hook) : null;
						$forum_db->query_build($query) or error(__FILE__, __LINE__);
					}
					else
					{
						// Run an UPDATE and see if it affected a row, if not, INSERT
						$query = array(
							'UPDATE'	=> 'forum_perms',
							'WHERE'		=> 'group_id='.$cur_group['g_id'].' AND forum_id='.$forum_id
						);

						$perms_new_values = array();
						foreach ($perms_new as $key => $value)
							$perms_new_values[] = $key.'='.$value;

						$query['SET'] = implode(', ', $perms_new_values);

						($hook = get_hook('afo_save_forum_qr_update_forum_perms')) ? eval($hook) : null;
						$forum_db->query_build($query) or error(__FILE__, __LINE__);
						if (!$forum_db->affected_rows())
						{
							$query = array(
								'INSERT'	=> 'group_id, forum_id',
								'INTO'		=> 'forum_perms',
								'VALUES'	=> $cur_group['g_id'].', '.$forum_id
							);

							$query['INSERT'] .= ', '.implode(', ', array_keys($perms_new));
							$query['VALUES'] .= ', '.implode(', ', $perms_new);

							($hook = get_hook('afo_save_forum_qr_add_forum_perms')) ? eval($hook) : null;
							$forum_db->query_build($query) or error(__FILE__, __LINE__);
						}
					}
				}
			}
		}

		// Regenerate the quickjump cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_quickjump_cache();

		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Forum updated']);

		($hook = get_hook('afo_save_forum_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_forums_forum'], $forum_id), ForumCore::$lang['Forum updated']);
	}

	else if (isset($_POST['revert_perms']))
	{
		$forum_id = intval($_POST['forum_id']);
		if ($forum_id < 1)
			message(ForumCore::$lang['Bad request']);

		($hook = get_hook('afo_revert_perms_form_submitted')) ? eval($hook) : null;

		$query = array(
			'DELETE'	=> 'forum_perms',
			'WHERE'		=> 'forum_id='.$forum_id
		);

		($hook = get_hook('afo_revert_perms_qr_revert_forum_perms')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Regenerate the quickjump cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_quickjump_cache();

		//$forum_flash->add_info(ForumCore::$lang['Permissions reverted']);

		($hook = get_hook('afo_revert_perms_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_forums']).'&edit_forum='.$forum_id, ForumCore::$lang['Permissions reverted']);
	}
});

// Admin Users
add_action('admin_post_pun_admin_users', function()
{
	require FORUM_ROOT.'include/common.php';

	// Load the admin.php language file
	ForumCore::add_lang('admin_common');
	ForumCore::add_lang('admin_users');
	ForumCore::add_lang('admin_bans');
	ForumCore::add_lang('misc');

	$forum_db = new DBLayer;

	// User pressed the cancel button
	if (isset($_POST['delete_users_cancel']))
	{
		redirect(pun_admin_link(ForumCore::$forum_url['admin_users']), ForumCore::$lang['Cancel redirect']);
	}
		
	else if (isset($_POST['delete_users_comply']))
	{
		if (ForumUser::$forum_user['g_id'] != FORUM_ADMIN)
			message(ForumCore::$lang['No permission']);

		if (empty($_POST['users']))
			message(ForumCore::$lang['No users selected']);

		($hook = get_hook('aus_delete_users_selected')) ? eval($hook) : null;

		if (!is_array($_POST['users']))
			$users = explode(',', $_POST['users']);
		else
			$users = array_keys($_POST['users']);

		$users = array_map('intval', $users);

		// We check to make sure there are no administrators in this list
		$query = array(
			'SELECT'	=> 'COUNT(u.id)',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.id IN ('.implode(',', $users).') AND u.group_id='.FORUM_ADMIN
		);
		($hook = get_hook('aus_delete_users_qr_check_for_admins')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($forum_db->result($result) > 0)
			message(ForumCore::$lang['Delete admin message']);

		($hook = get_hook('aus_delete_users_form_submitted')) ? eval($hook) : null;

		foreach ($users as $id)
		{
			// We don't want to delete the Guest user
			if ($id > 1)
				delete_user($id, isset($_POST['delete_posts']));
		}

		// Remove cache file with forum stats
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		{
			require FORUM_ROOT.'include/cache.php';
		}

		clean_stats_cache();

		($hook = get_hook('aus_delete_users_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_users']), ForumCore::$lang['Users deleted']);
	}

	// User pressed the cancel button
	else if (isset($_POST['change_group_cancel']))
	{
		redirect(pun_admin_link(ForumCore::$forum_url['admin_users']), ForumCore::$lang['Cancel redirect']);
	}
	
	// 
	else if (isset($_POST['change_group_comply']))
	{
		if (empty($_POST['users']))
			message(ForumCore::$lang['No users selected']);

		($hook = get_hook('aus_change_group_selected')) ? eval($hook) : null;

		if (!is_array($_POST['users']))
			$users = explode(',', $_POST['users']);
		else
			$users = array_keys($_POST['users']);

		$users = array_map('intval', $users);

		$move_to_group = intval($_POST['move_to_group']);

		($hook = get_hook('aus_change_group_form_submitted')) ? eval($hook) : null;

		// We need some information on the group
		$query = array(
			'SELECT'	=> 'g.g_moderator',
			'FROM'		=> 'groups AS g',
			'WHERE'		=> 'g.g_id='.$move_to_group
		);
		($hook = get_hook('aus_change_group_qr_get_group_moderator_status')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$group_is_mod = $forum_db->result($result);

		if ($move_to_group == FORUM_GUEST || (is_null($group_is_mod) || $group_is_mod === false))
			message(ForumCore::$lang['Bad request']);

		// Move users
		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> 'group_id='.$move_to_group,
			'WHERE'		=> 'id IN ('.implode(',', $users).') AND id>1'
		);
		($hook = get_hook('aus_change_group_qr_change_user_group')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($move_to_group != FORUM_ADMIN && ($group_is_mod !== false && $group_is_mod == '0'))
			clean_forum_moderators();

		($hook = get_hook('aus_change_group_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_users']), ForumCore::$lang['User groups updated']);
	}

	else if (isset($_POST['ban_users_comply']))
	{
		if (ForumUser::$forum_user['g_id'] != FORUM_ADMIN && (ForumUser::$forum_user['g_moderator'] != '1' || ForumUser::$forum_user['g_mod_ban_users'] == '0'))
			message(ForumCore::$lang['No permission']);

		if (empty($_POST['users']))
			message(ForumCore::$lang['No users selected']);

		($hook = get_hook('aus_ban_users_selected')) ? eval($hook) : null;

		if (!is_array($_POST['users']))
			$users = explode(',', $_POST['users']);
		else
			$users = array_keys($_POST['users']);

		$users = array_map('intval', $users);

		// We check to make sure there are no administrators in this list
		$query = array(
			'SELECT'	=> 'COUNT(u.id)',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.id IN ('.implode(',', $users).') AND u.group_id='.FORUM_ADMIN
		);
		($hook = get_hook('aus_ban_users_qr_check_for_admins')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($forum_db->result($result) > 0)
			message(ForumCore::$lang['Ban admin message']);

		$ban_message = forum_trim($_POST['ban_message']);
		$ban_expire = forum_trim($_POST['ban_expire']);

		($hook = get_hook('aus_ban_users_form_submitted')) ? eval($hook) : null;

		if ($ban_expire != '' && $ban_expire != 'Never')
		{
			$ban_expire = strtotime($ban_expire);

			if ($ban_expire == -1 || $ban_expire <= time())
				message(ForumCore::$lang['Invalid expire message']);
		}
		else
			$ban_expire = 'NULL';

		$ban_message = ($ban_message != '') ? '\''.$forum_db->escape($ban_message).'\'' : 'NULL';

		// Get the latest IPs for the posters and store them for a little later
		$query = array(
			'SELECT'	=> 'p.poster_id, p.poster_ip',
			'FROM'		=> 'posts AS p',
			'WHERE'		=> 'p.poster_id IN ('.implode(',', $users).') AND p.poster_id>1',
			'ORDER BY'	=> 'p.posted ASC'
		);
		($hook = get_hook('aus_ban_users_qr_get_latest_user_ips')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$ips = array();
		while ($cur_post = $forum_db->fetch_assoc($result))
			$ips[$cur_post['poster_id']] = $cur_post['poster_ip'];

		// Get the rest of the data for the posters, merge in the IP information, create a ban
		$query = array(
			'SELECT'	=> 'u.id, u.username, u.email, u.registration_ip',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'id IN ('.implode(',', $users).') AND id>1'
		);
		($hook = get_hook('aus_ban_users_qr_get_users')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		while ($cur_user = $forum_db->fetch_assoc($result))
		{
			$ban_ip = isset($ips[$cur_user['id']]) ? $ips[$cur_user['id']] : $cur_user['registration_ip'];

			$query = array(
				'INSERT'	=> 'username, ip, email, message, expire, ban_creator',
				'INTO'		=> 'bans',
				'VALUES'	=> '\''.$forum_db->escape($cur_user['username']).'\', \''.$ban_ip.'\', \''.$forum_db->escape($cur_user['email']).'\', '.$ban_message.', '.$ban_expire.', '.ForumUser::$forum_user['id']
			);
			($hook = get_hook('aus_ban_users_qr_add_ban')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}

		// Regenerate the bans cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_bans_cache();

		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Users banned']);

		($hook = get_hook('aus_ban_users_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_users']), ForumCore::$lang['Users banned']);
	}
});

// Admin Groups
add_action('admin_post_pun_admin_groups', function()
{
	require FORUM_ROOT.'include/common.php';

	// Load the admin.php language file
	ForumCore::add_lang('admin_common');
	ForumCore::add_lang('admin_groups');

	$forum_db = new DBLayer;

	// Add/edit a group (stage 2)
	if (isset($_POST['add_edit_group']))
	{
		// Is this the admin group? (special rules apply)
		$is_admin_group = (isset($_POST['group_id']) && $_POST['group_id'] == FORUM_ADMIN) ? true : false;

		$title = forum_trim($_POST['req_title']);
		$user_title = forum_trim($_POST['user_title']);
		$moderator = isset($_POST['moderator']) && $_POST['moderator'] == '1' ? '1' : '0';
		$mod_edit_users = $moderator == '1' && isset($_POST['mod_edit_users']) && $_POST['mod_edit_users'] == '1' ? '1' : '0';
		$mod_rename_users = $moderator == '1' && isset($_POST['mod_rename_users']) && $_POST['mod_rename_users'] == '1' ? '1' : '0';
		$mod_change_passwords = $moderator == '1' && isset($_POST['mod_change_passwords']) && $_POST['mod_change_passwords'] == '1' ? '1' : '0';
		$mod_ban_users = $moderator == '1' && isset($_POST['mod_ban_users']) && $_POST['mod_ban_users'] == '1' ? '1' : '0';
		$read_board = (isset($_POST['read_board']) && $_POST['read_board'] == '1') || $is_admin_group ? '1' : '0';
		$view_users = (isset($_POST['view_users']) && $_POST['view_users'] == '1') || $is_admin_group ? '1' : '0';
		$post_replies = (isset($_POST['post_replies']) && $_POST['post_replies'] == '1') || $is_admin_group ? '1' : '0';
		$post_topics = (isset($_POST['post_topics']) && $_POST['post_topics'] == '1') || $is_admin_group ? '1' : '0';
		$edit_posts = (isset($_POST['edit_posts']) && $_POST['edit_posts'] == '1') || $is_admin_group ? '1' : '0';
		$delete_posts = (isset($_POST['delete_posts']) && $_POST['delete_posts'] == '1') || $is_admin_group ? '1' : '0';
		$delete_topics = (isset($_POST['delete_topics']) && $_POST['delete_topics'] == '1') || $is_admin_group ? '1' : '0';
		$set_title = (isset($_POST['set_title']) && $_POST['set_title'] == '1') || $is_admin_group ? '1' : '0';
		$search = (isset($_POST['search']) && $_POST['search'] == '1') || $is_admin_group ? '1' : '0';
		$search_users = (isset($_POST['search_users']) && $_POST['search_users'] == '1') || $is_admin_group ? '1' : '0';
		$send_email = (isset($_POST['send_email']) && $_POST['send_email'] == '1') || $is_admin_group ? '1' : '0';
		$post_flood = isset($_POST['post_flood']) ? intval($_POST['post_flood']) : '0';
		$search_flood = isset($_POST['search_flood']) ? intval($_POST['search_flood']) : '0';
		$email_flood = isset($_POST['email_flood']) ? intval($_POST['email_flood']) : '0';

		if ($title == '')
			message(ForumCore::$lang['Must enter group message']);

		$user_title = ($user_title != '') ? '\''.$forum_db->escape($user_title).'\'' : 'NULL';

		($hook = get_hook('agr_add_edit_end_validation')) ? eval($hook) : null;


		if ($_POST['mode'] == 'add')
		{
			($hook = get_hook('agr_add_add_group')) ? eval($hook) : null;

			$query = array(
				'SELECT'	=> 'COUNT(g.g_id)',
				'FROM'		=> 'groups AS g',
				'WHERE'		=> 'g_title=\''.$forum_db->escape($title).'\''
			);

			($hook = get_hook('agr_add_end_qr_check_add_group_title_collision')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			if ($forum_db->result($result) != 0)
				message(sprintf(ForumCore::$lang['Already a group message'], forum_htmlencode($title)));

			// Insert the new group
			$query = array(
				'INSERT'	=> 'g_title, g_user_title, g_moderator, g_mod_edit_users, g_mod_rename_users, g_mod_change_passwords, g_mod_ban_users, g_read_board, g_view_users, g_post_replies, g_post_topics, g_edit_posts, g_delete_posts, g_delete_topics, g_set_title, g_search, g_search_users, g_send_email, g_post_flood, g_search_flood, g_email_flood',
				'INTO'		=> 'groups',
				'VALUES'	=> '\''.$forum_db->escape($title).'\', '.$user_title.', '.$moderator.', '.$mod_edit_users.', '.$mod_rename_users.', '.$mod_change_passwords.', '.$mod_ban_users.', '.$read_board.', '.$view_users.', '.$post_replies.', '.$post_topics.', '.$edit_posts.', '.$delete_posts.', '.$delete_topics.', '.$set_title.', '.$search.', '.$search_users.', '.$send_email.', '.$post_flood.', '.$search_flood.', '.$email_flood
			);

			($hook = get_hook('agr_add_end_qr_add_group')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
			$new_group_id = $forum_db->insert_id();

			// Now lets copy the forum specific permissions from the group which this group is based on
			$query = array(
				'SELECT'	=> 'fp.forum_id, fp.read_forum, fp.post_replies, fp.post_topics',
				'FROM'		=> 'forum_perms AS fp',
				'WHERE'		=> 'group_id='.intval($_POST['base_group'])
			);

			($hook = get_hook('agr_add_end_qr_get_group_forum_perms')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			while ($cur_forum_perm = $forum_db->fetch_assoc($result))
			{
				$query = array(
					'INSERT'	=> 'group_id, forum_id, read_forum, post_replies, post_topics',
					'INTO'		=> 'forum_perms',
					'VALUES'	=> $new_group_id.', '.$cur_forum_perm['forum_id'].', '.$cur_forum_perm['read_forum'].', '.$cur_forum_perm['post_replies'].', '.$cur_forum_perm['post_topics']
				);

				($hook = get_hook('agr_add_end_qr_add_group_forum_perms')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
		}
		else
		{
			$group_id = intval($_POST['group_id']);

			($hook = get_hook('agr_edit_end_edit_group')) ? eval($hook) : null;

			// Make sure admins and guests don't get moderator privileges
			if ($group_id == FORUM_ADMIN || $group_id == FORUM_GUEST)
				$moderator = '0';

			// Make sure the default group isn't assigned moderator privileges
			if ($moderator == '1' && ForumCore::$forum_config['o_default_user_group'] == $group_id)
				message(ForumCore::$lang['Moderator default group']);

			$query = array(
				'SELECT'	=> 'COUNT(g.g_id)',
				'FROM'		=> 'groups AS g',
				'WHERE'		=> 'g_title=\''.$forum_db->escape($title).'\' AND g_id!='.$group_id
			);

			($hook = get_hook('agr_edit_end_qr_check_edit_group_title_collision')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			if ($forum_db->result($result) != 0)
				message(sprintf(ForumCore::$lang['Already a group message'], forum_htmlencode($title)));

			// Save changes
			$query = array(
				'UPDATE'	=> 'groups',
				'SET'		=> 'g_title=\''.$forum_db->escape($title).'\', g_user_title='.$user_title.', g_moderator='.$moderator.', g_mod_edit_users='.$mod_edit_users.', g_mod_rename_users='.$mod_rename_users.', g_mod_change_passwords='.$mod_change_passwords.', g_mod_ban_users='.$mod_ban_users.', g_read_board='.$read_board.', g_view_users='.$view_users.', g_post_replies='.$post_replies.', g_post_topics='.$post_topics.', g_edit_posts='.$edit_posts.', g_delete_posts='.$delete_posts.', g_delete_topics='.$delete_topics.', g_set_title='.$set_title.', g_search='.$search.', g_search_users='.$search_users.', g_send_email='.$send_email.', g_post_flood='.$post_flood.', g_search_flood='.$search_flood.', g_email_flood='.$email_flood,
				'WHERE'		=> 'g_id='.$group_id
			);

			($hook = get_hook('agr_edit_end_qr_update_group')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			// If the group doesn't have moderator privileges (it might have had before), remove its users from the moderator list in all forums
			if (!$moderator)
				clean_forum_moderators();
		}

		// Regenerate the quickjump cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_quickjump_cache();

		// Add flash message
		//$forum_flash->add_info((($_POST['mode'] == 'edit') ? ForumCore::$lang['Group edited'] : ForumCore::$lang['Group added']));

		($hook = get_hook('agr_add_edit_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_groups']), (($_POST['mode'] == 'edit') ? ForumCore::$lang['Group edited'] : ForumCore::$lang['Group added']));
	}

	// Set default group
	else if (isset($_POST['set_default_group']))
	{
		$group_id = intval($_POST['default_group']);

		($hook = get_hook('agr_set_default_group_form_submitted')) ? eval($hook) : null;

		// Make sure it's not the admin or guest groups
		if ($group_id == FORUM_ADMIN || $group_id == FORUM_GUEST)
			message(ForumCore::$lang['Bad request']);

		// Make sure it's not a moderator group
		$query = array(
			'SELECT'	=> 'COUNT(g.g_id)',
			'FROM'		=> 'groups AS g',
			'WHERE'		=> 'g.g_id='.$group_id.' AND g.g_moderator=0',
			'LIMIT'		=> '1'
		);
		($hook = get_hook('agr_set_default_group_qr_get_group_moderation_status')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($forum_db->result($result) != 1)
			message(ForumCore::$lang['Bad request']);

		$query = array(
			'UPDATE'	=> 'config',
			'SET'		=> 'conf_value='.$group_id,
			'WHERE'		=> 'conf_name=\'o_default_user_group\''
		);
		($hook = get_hook('agr_set_default_group_qr_set_default_group')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Regenerate the config cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_config_cache();

		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Default group set']);

		($hook = get_hook('agr_set_default_group_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_groups']), ForumCore::$lang['Default group set']);
	}

	// User pressed the cancel button
	else if (isset($_POST['del_group_cancel']))
	{
		redirect(pun_admin_link(ForumCore::$forum_url['admin_groups']), ForumCore::$lang['Cancel redirect']);
	}
			
	// If the group doesn't have any members or if we've already selected a group to move the members to
	else if (isset($_POST['del_group']))
	{
		$group_id = intval($_POST['group_id']);
		if ($group_id <= FORUM_GUEST)
			message(ForumCore::$lang['Bad request']);
	
		// Make sure we don't remove the default group
		if ($group_id == ForumCore::$forum_config['o_default_user_group'])
			message(ForumCore::$lang['Cannot remove default group']);

		($hook = get_hook('agr_del_group_form_submitted')) ? eval($hook) : null;

		if (isset($_POST['del_group']))	// Move users
		{
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'group_id='.intval($_POST['move_to_group']),
				'WHERE'		=> 'group_id='.$group_id
			);

			($hook = get_hook('agr_del_group_qr_move_users')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}

		// Delete the group and any forum specific permissions
		$query = array(
			'DELETE'	=> 'groups',
			'WHERE'		=> 'g_id='.$group_id
		);
		($hook = get_hook('agr_del_group_qr_delete_group')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		$query = array(
			'DELETE'	=> 'forum_perms',
			'WHERE'		=> 'group_id='.$group_id
		);
		($hook = get_hook('agr_del_group_qr_delete_group_forum_perms')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		clean_forum_moderators();

		// Regenerate the quickjump cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_quickjump_cache();

		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Group removed']);

		($hook = get_hook('agr_del_group_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_groups']), ForumCore::$lang['Group removed']);
	}
});

// Admin Ranks
add_action('admin_post_pun_admin_ranks', function()
{
	require FORUM_ROOT.'include/common.php';

	// Load the admin.php language file
	ForumCore::add_lang('admin_common');
	ForumCore::add_lang('admin_ranks');

	$forum_db = new DBLayer;

	// Add a rank
	if (isset($_POST['add_rank']))
	{
		$rank = forum_trim($_POST['new_rank']);
		$min_posts = intval($_POST['new_min_posts']);

		if ($rank == '')
			message(ForumCore::$lang['Title message']);

		if ($min_posts < 0)
			message(ForumCore::$lang['Min posts message']);

		($hook = get_hook('ark_add_rank_form_submitted')) ? eval($hook) : null;

		// Make sure there isn't already a rank with the same min_posts value
		$query = array(
			'SELECT'	=> 'COUNT(r.id)',
			'FROM'		=> 'ranks AS r',
			'WHERE'		=> 'min_posts='.$min_posts
		);

		($hook = get_hook('ark_add_rank_qr_check_rank_collision')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($forum_db->result($result) > 0)
			message(sprintf(ForumCore::$lang['Min posts occupied message'], $min_posts));

		$query = array(
			'INSERT'	=> $forum_db->quotes.'rank'.$forum_db->quotes.', min_posts',
			'INTO'		=> 'ranks',
			'VALUES'	=> '\''.$forum_db->escape($rank).'\', '.$min_posts
		);

		($hook = get_hook('ark_add_rank_qr_add_rank')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Regenerate the ranks cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_ranks_cache();

		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Rank added']);

		($hook = get_hook('ark_add_rank_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_ranks']), ForumCore::$lang['Rank added']);
	}

	// Update a rank
	else if (isset($_POST['update']))
	{
		$id = intval(key($_POST['update']));

		$rank = forum_trim($_POST['rank'][$id]);
		$min_posts = intval($_POST['min_posts'][$id]);

		if ($rank == '')
			message(ForumCore::$lang['Title message']);

		if ($min_posts < 0)
			message(ForumCore::$lang['Min posts message']);

		($hook = get_hook('ark_update_form_submitted')) ? eval($hook) : null;

		// Make sure there isn't already a rank with the same min_posts value
		$query = array(
			'SELECT'	=> 'COUNT(r.id)',
			'FROM'		=> 'ranks AS r',
			'WHERE'		=> 'id!='.$id.' AND min_posts='.$min_posts
		);

		($hook = get_hook('ark_update_qr_check_rank_collision')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($forum_db->result($result) > 0)
			message(sprintf(ForumCore::$lang['Min posts occupied message'], $min_posts));

		$query = array(
			'UPDATE'	=> 'ranks',
			'SET'		=> $forum_db->quotes.'rank'.$forum_db->quotes.'=\''.$forum_db->escape($rank).'\', min_posts='.$min_posts,
			'WHERE'		=> 'id='.$id
		);

		($hook = get_hook('ark_update_qr_update_rank')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Regenerate the ranks cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_ranks_cache();

		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Rank updated']);

		($hook = get_hook('ark_update_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_ranks']), ForumCore::$lang['Rank updated']);
	}

	// Remove a rank
	else if (isset($_POST['remove']))
	{
		$id = intval(key($_POST['remove']));

		($hook = get_hook('ark_remove_form_submitted')) ? eval($hook) : null;

		$query = array(
			'DELETE'	=> 'ranks',
			'WHERE'		=> 'id='.$id
		);

		($hook = get_hook('ark_remove_qr_delete_rank')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Regenerate the ranks cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_ranks_cache();

		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Rank removed']);

		($hook = get_hook('ark_remove_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_ranks']), ForumCore::$lang['Rank removed']);
	}
});

// Admin Bans
add_action('admin_post_pun_admin_bans', function()
{
	require FORUM_ROOT.'include/common.php';

	// Load the admin.php language file
	ForumCore::add_lang('admin_common');
	ForumCore::add_lang('admin_bans');

	$forum_db = new DBLayer;

	// Add/edit a ban (stage 2)
	if (isset($_POST['add_edit_ban']))
	{
		$ban_user = forum_trim($_POST['ban_user']);
		$ban_ip = forum_trim($_POST['ban_ip']);
		$ban_email = strtolower(forum_trim($_POST['ban_email']));
		$ban_message = forum_trim($_POST['ban_message']);
		$ban_expire = forum_trim($_POST['ban_expire']);

		if ($ban_user == '' && $ban_ip == '' && $ban_email == '')
			message(ForumCore::$lang['Must enter message']);
		else if (strtolower($ban_user) == 'guest')
			message(ForumCore::$lang['Can\'t ban guest user']);

		($hook = get_hook('aba_add_edit_ban_form_submitted')) ? eval($hook) : null;

		// Validate IP/IP range (it's overkill, I know)
		if ($ban_ip != '')
		{
			$ban_ip = preg_replace('/[\s]{2,}/', ' ', $ban_ip);
			$addresses = explode(' ', $ban_ip);
			$addresses = array_map('trim', $addresses);

			for ($i = 0; $i < count($addresses); ++$i)
			{
				if (strpos($addresses[$i], ':') !== false)
				{
					$octets = explode(':', $addresses[$i]);


					for ($c = 0; $c < count($octets); ++$c)
					{

						$octets[$c] = ltrim($octets[$c], "0");

						if ($c > 7 || (!empty($octets[$c]) && !ctype_xdigit($octets[$c])) || intval($octets[$c], 16) > 65535)
							message(ForumCore::$lang['Invalid IP message']);
					}

					$cur_address = implode(':', $octets);
					$addresses[$i] = $cur_address;
				}
				else
				{
					$octets = explode('.', $addresses[$i]);

					for ($c = 0; $c < count($octets); ++$c)
					{

						$octets[$c] = (strlen($octets[$c]) > 1) ? ltrim($octets[$c], "0") : $octets[$c];

						if ($c > 3 || !ctype_digit($octets[$c]) || intval($octets[$c]) > 255)
							message(ForumCore::$lang['Invalid IP message']);
					}

					$cur_address = implode('.', $octets);
					$addresses[$i] = $cur_address;
				}
			}

			$ban_ip = implode(' ', $addresses);
		}

		if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/email.php';

		if ($ban_email != '' && !is_valid_email($ban_email))
		{
			if (!is_valid_email('testuser@'.$ban_email))
				message(ForumCore::$lang['Invalid e-mail message']);
		}

		if ($ban_expire != '' && $ban_expire != 'Never')
		{
			$ban_expire = strtotime($ban_expire);

			if ($ban_expire == -1 || $ban_expire <= time())
				message(ForumCore::$lang['Invalid expire message']);
		}
		else
			$ban_expire = 'NULL';

		$ban_user = ($ban_user != '') ? '\''.$forum_db->escape($ban_user).'\'' : 'NULL';
		$ban_ip = ($ban_ip != '') ? '\''.$forum_db->escape($ban_ip).'\'' : 'NULL';
		$ban_email = ($ban_email != '') ? '\''.$forum_db->escape($ban_email).'\'' : 'NULL';
		$ban_message = ($ban_message != '') ? '\''.$forum_db->escape($ban_message).'\'' : 'NULL';

		if ($_POST['mode'] == 'add')
		{
			$query = array(
				'INSERT'	=> 'username, ip, email, message, expire, ban_creator',
				'INTO'		=> 'bans',
				'VALUES'	=> $ban_user.', '.$ban_ip.', '.$ban_email.', '.$ban_message.', '.$ban_expire.', '.ForumUser::$forum_user['id']
			);
			($hook = get_hook('aba_add_edit_ban_qr_add_ban')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
		else
		{
			$query = array(
				'UPDATE'	=> 'bans',
				'SET'		=> 'username='.$ban_user.', ip='.$ban_ip.', email='.$ban_email.', message='.$ban_message.', expire='.$ban_expire,
				'WHERE'		=> 'id='.intval($_POST['ban_id'])
			);
			($hook = get_hook('aba_qr_update_ban')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}

		// Regenerate the bans cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_bans_cache();

		//$forum_flash->add_info((($_POST['mode'] == 'edit') ? ForumCore::$lang['Ban edited'] : ForumCore::$lang['Ban added']));

		($hook = get_hook('aba_add_edit_ban_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_bans']), (($_POST['mode'] == 'edit') ? ForumCore::$lang['Ban edited'] : ForumCore::$lang['Ban added']));
	}

	else if (isset($_POST['del_ban_cancel']))
	{
		redirect(pun_admin_link(ForumCore::$forum_url['admin_bans']), ForumCore::$lang['Ban removed']);
	}

	// Remove a ban
	else if (isset($_POST['del_ban_comply']))
	{
		$ban_id = intval($_POST['ban_id']);
		if ($ban_id < 1)
			message(ForumCore::$lang['Bad request']);

		($hook = get_hook('aba_del_ban_form_submitted')) ? eval($hook) : null;

		$query = array(
			'DELETE'	=> 'bans',
			'WHERE'		=> 'id='.$ban_id
		);
		($hook = get_hook('aba_del_ban_qr_delete_ban')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Regenerate the bans cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_bans_cache();

		//$forum_flash->add_info(ForumCore::$lang['Ban removed']);

		($hook = get_hook('aba_del_ban_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_bans']), ForumCore::$lang['Ban removed']);
	}
});

// Admin Reports
add_action('admin_post_pun_admin_reports', function()
{
	require FORUM_ROOT.'include/common.php';

	// Load the admin.php language file
	ForumCore::add_lang('admin_common');
	ForumCore::add_lang('admin_reports');

	$forum_db = new DBLayer;

	// Mark reports as read
	if (isset($_POST['mark_as_read']))
	{
		if (empty($_POST['reports']))
			message(ForumCore::$lang['No reports selected']);

		($hook = get_hook('arp_mark_as_read_form_submitted')) ? eval($hook) : null;

		$reports_to_mark = array_map('intval', array_keys($_POST['reports']));

		$query = array(
			'UPDATE'	=> 'reports',
			'SET'		=> 'zapped='.time().', zapped_by='.ForumUser::$forum_user['id'],
			'WHERE'		=> 'id IN('.implode(',', $reports_to_mark).') AND zapped IS NULL'
		);
		($hook = get_hook('arp_mark_as_read_qr_mark_reports_as_read')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Reports marked read']);

		($hook = get_hook('arp_mark_as_read_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_reports']), ForumCore::$lang['Reports marked read']);
	}
});

// Admin Censoring
add_action('admin_post_pun_admin_censoring', function()
{
	require FORUM_ROOT.'include/common.php';

	// Load the admin.php language file
	ForumCore::add_lang('admin_common');
	ForumCore::add_lang('admin_reports');

	$forum_db = new DBLayer;

	// Add a censor word
	if (isset($_POST['add_word']))
	{
		$search_for = forum_trim($_POST['new_search_for']);
		$replace_with = forum_trim($_POST['new_replace_with']);

		if ($search_for == '' || $replace_with == '')
			message(ForumCore::$lang['Must enter text message']);

		($hook = get_hook('acs_add_word_form_submitted')) ? eval($hook) : null;

		$query = array(
			'INSERT'	=> 'search_for, replace_with',
			'INTO'		=> 'censoring',
			'VALUES'	=> '\''.$forum_db->escape($search_for).'\', \''.$forum_db->escape($replace_with).'\''
		);

		($hook = get_hook('acs_add_word_qr_add_censor')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Regenerate the censor cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_censors_cache();

		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Censor word added']);

		($hook = get_hook('acs_add_word_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_management_censoring']), ForumCore::$lang['Censor word added']);
	}

	// Update a censor word
	else if (isset($_POST['update']))
	{
		$id = intval(key($_POST['update']));

		$search_for = forum_trim($_POST['search_for'][$id]);
		$replace_with = forum_trim($_POST['replace_with'][$id]);

		if ($search_for == '' || $replace_with == '')
			message(ForumCore::$lang['Must enter text message']);

		($hook = get_hook('acs_update_form_submitted')) ? eval($hook) : null;

		$query = array(
			'UPDATE'	=> 'censoring',
			'SET'		=> 'search_for=\''.$forum_db->escape($search_for).'\', replace_with=\''.$forum_db->escape($replace_with).'\'',
			'WHERE'		=> 'id='.$id
		);

		($hook = get_hook('acs_update_qr_update_censor')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Regenerate the censor cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_censors_cache();

		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Censor word updated']);

		($hook = get_hook('acs_update_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_management_censoring']), ForumCore::$lang['Censor word updated']);
	}

	// Remove a censor word
	else if (isset($_POST['remove']))
	{
		$id = intval(key($_POST['remove']));

		($hook = get_hook('acs_remove_form_submitted')) ? eval($hook) : null;

		$query = array(
			'DELETE'	=> 'censoring',
			'WHERE'		=> 'id='.$id
		);

		($hook = get_hook('acs_remove_qr_delete_censor')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Regenerate the censor cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_censors_cache();

		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Censor word removed']);

		($hook = get_hook('acs_remove_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_management_censoring']), ForumCore::$lang['Censor word removed']);
	}
});

// Admin Prune
add_action('admin_post_pun_admin_prune', function()
{
	require FORUM_ROOT.'include/common.php';

	// Load the admin.php language file
	ForumCore::add_lang('admin_common');
	ForumCore::add_lang('admin_reports');

	$forum_db = new DBLayer;

	if (isset($_POST['prune_comply']))
	{
		$prune_from = $_POST['prune_from'];
		$prune_days = intval($_POST['prune_days']);
		$prune_date = ($prune_days) ? time() - ($prune_days*86400) : -1;

		($hook = get_hook('apr_prune_comply_form_submitted')) ? eval($hook) : null;

		@set_time_limit(0);

		if ($prune_from == 'all')
		{
			$query = array(
				'SELECT'	=> 'f.id',
				'FROM'		=> 'forums AS f'
			);

			($hook = get_hook('apr_prune_comply_qr_get_all_forums')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			while ($cur_forum = $forum_db->fetch_assoc($result)) {
				prune($cur_forum['id'], $_POST['prune_sticky'], $prune_date);
				sync_forum($cur_forum['id']);
			}
		}
		else
		{
			$prune_from = intval($prune_from);
			prune($prune_from, $_POST['prune_sticky'], $prune_date);
			sync_forum($prune_from);
		}

		delete_orphans();

		//$forum_flash->add_info(ForumCore::$lang['Prune done']);

		($hook = get_hook('apr_prune_pre_redirect')) ? eval($hook) : null;

		redirect(pun_admin_link(ForumCore::$forum_url['admin_management_prune']), ForumCore::$lang['Prune done']);
	}
});

// do_action( "admin_post_nopriv_{$action}" );
add_action('admin_post_pun_post', function()
{
	require FORUM_ROOT.'include/common.php';

	if (ForumUser::$forum_user['g_read_board'] == '0')
		message(ForumCore::$lang['No view']);

	// Load the post.php language file
	ForumCore::add_lang('post');
	ForumCore::add_lang('profile');

	ForumCore::$tid = isset($_POST['tid']) ? intval($_POST['tid']) : 0;
	ForumCore::$fid = isset($_POST['fid']) ? intval($_POST['fid']) : 0;
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

	// Did someone just hit "Submit" or "Preview"?
	if (isset($_POST['form_sent']))
	{
		($hook = get_hook('po_form_submitted')) ? eval($hook) : null;

		// Make sure form_user is correct
		if ((ForumUser::$forum_user['is_guest'] && $_POST['form_user'] != 'Guest') || (!ForumUser::$forum_user['is_guest'] && $_POST['form_user'] != ForumUser::$forum_user['username']))
			message(ForumCore::$lang['Bad request']);

		// Flood protection
		if (!isset($_POST['preview']) && ForumUser::$forum_user['last_post'] != '' && (time() - ForumUser::$forum_user['last_post']) < ForumUser::$forum_user['g_post_flood'] && (time() - ForumUser::$forum_user['last_post']) >= 0)
			ForumCore::$errors[] = sprintf(ForumCore::$lang['Flood'], ForumUser::$forum_user['g_post_flood']);

		// If it's a new topic
		if (ForumCore::$fid)
		{
			$subject = forum_trim($_POST['req_subject']);

			if ($subject == '')
				ForumCore::$errors[] = ForumCore::$lang['No subject'];
			else if (utf8_strlen($subject) > FORUM_SUBJECT_MAXIMUM_LENGTH)
				ForumCore::$errors[] = sprintf(ForumCore::$lang['Too long subject'], FORUM_SUBJECT_MAXIMUM_LENGTH);
			else if (ForumCore::$forum_config['p_subject_all_caps'] == '0' && check_is_all_caps($subject) && !$forum_page['is_admmod'])
				ForumCore::$errors[] = ForumCore::$lang['All caps subject'];
		}

		// If the user is logged in we get the username and e-mail from ForumUser::$forum_user
		if (!ForumUser::$forum_user['is_guest'])
		{
			$username = ForumUser::$forum_user['username'];
			$email = ForumUser::$forum_user['email'];
		}
		// Otherwise it should be in $_POST
		else
		{
			$username = forum_trim($_POST['req_username']);
			$email = strtolower(forum_trim((ForumCore::$forum_config['p_force_guest_email'] == '1') ? $_POST['req_email'] : $_POST['email']));

			// Load the profile.php language file
			require FORUM_ROOT.'lang/'.ForumUser::$forum_user['language'].'/profile.php';

			// It's a guest, so we have to validate the username
			ForumCore::$errors = array_merge(ForumCore::$errors, pun_validate_username($username));

			if (ForumCore::$forum_config['p_force_guest_email'] == '1' || $email != '')
			{
				if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
					require FORUM_ROOT.'include/email.php';

				if (!is_valid_email($email))
					ForumCore::$errors[] = ForumCore::$lang['Invalid e-mail'];

				if (is_banned_email($email))
					ForumCore::$errors[] = ForumCore::$lang['Banned e-mail'];
			}
		}

		// If we're an administrator or moderator, make sure the CSRF token in $_POST is valid
		if (ForumUser::$forum_user['is_admmod'] && (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== generate_form_token(get_current_url())))
			ForumCore::$errors[] = ForumCore::$lang['CSRF token mismatch'];

		// Clean up message from POST
		ForumCore::$post_message = forum_linebreaks(forum_trim($_POST['req_message']));

		if (strlen(ForumCore::$post_message) > FORUM_MAX_POSTSIZE_BYTES)
			ForumCore::$errors[] = sprintf(ForumCore::$lang['Too long message'], forum_number_format(strlen(ForumCore::$post_message)), forum_number_format(FORUM_MAX_POSTSIZE_BYTES));
		else if (ForumCore::$forum_config['p_message_all_caps'] == '0' && check_is_all_caps(ForumCore::$post_message) && !$forum_page['is_admmod'])
			ForumCore::$errors[] = ForumCore::$lang['All caps message'];

		// Validate BBCode syntax
		if (ForumCore::$forum_config['p_message_bbcode'] == '1' || ForumCore::$forum_config['o_make_links'] == '1')
		{
			if (!defined('FORUM_PARSER_LOADED'))
				require FORUM_ROOT.'include/parser.php';

			ForumCore::$post_message = preparse_bbcode(ForumCore::$post_message, ForumCore::$errors);
		}

		if (ForumCore::$post_message == '')
			ForumCore::$errors[] = ForumCore::$lang['No message'];

		$hide_smilies = isset($_POST['hide_smilies']) ? 1 : 0;
		$subscribe = isset($_POST['subscribe']) ? 1 : 0;

		$now = time();

		($hook = get_hook('po_end_validation')) ? eval($hook) : null;

		// Did everything go according to plan?
		if (empty(ForumCore::$errors) && !isset($_POST['preview']))
		{
			// If it's a reply
			if (ForumCore::$tid)
			{
				$post_info = array(
					'is_guest'		=> ForumUser::$forum_user['is_guest'],
					'poster'		=> $username,
					'poster_id'		=> ForumUser::$forum_user['id'],	// Always 1 for guest posts
					'poster_email'	=> (ForumUser::$forum_user['is_guest'] && $email != '') ? $email : null,	// Always null for non-guest posts
					'subject'		=> ForumCore::$cur_posting['subject'],
					'message'		=> ForumCore::$post_message,
					'hide_smilies'	=> $hide_smilies,
					'posted'		=> $now,
					'subscr_action'	=> (ForumCore::$forum_config['o_subscriptions'] == '1' && $subscribe && !ForumCore::$is_subscribed) ? 1 : ((ForumCore::$forum_config['o_subscriptions'] == '1' && !$subscribe && ForumCore::$is_subscribed) ? 2 : 0),
					'topic_id'		=> ForumCore::$tid,
					'forum_id'		=> ForumCore::$cur_posting['id'],
					'update_user'	=> true,
					'update_unread'	=> true
				);

				($hook = get_hook('po_pre_add_post')) ? eval($hook) : null;
				add_post($post_info, $new_pid);
			}
			// If it's a new topic
			else if (ForumCore::$fid)
			{
				$post_info = array(
					'is_guest'		=> ForumUser::$forum_user['is_guest'],
					'poster'		=> $username,
					'poster_id'		=> ForumUser::$forum_user['id'],	// Always 1 for guest posts
					'poster_email'	=> (ForumUser::$forum_user['is_guest'] && $email != '') ? $email : null,	// Always null for non-guest posts
					'subject'		=> $subject,
					'message'		=> ForumCore::$post_message,
					'hide_smilies'	=> $hide_smilies,
					'posted'		=> $now,
					'subscribe'		=> (ForumCore::$forum_config['o_subscriptions'] == '1' && (isset($_POST['subscribe']) && $_POST['subscribe'] == '1')),
					'forum_id'		=> ForumCore::$fid,
					'forum_name'	=> ForumCore::$cur_posting['forum_name'],
					'update_user'	=> true,
					'update_unread'	=> true
				);

				($hook = get_hook('po_pre_add_topic')) ? eval($hook) : null;
				add_topic($post_info, $new_tid, $new_pid);
			}

			($hook = get_hook('po_pre_redirect')) ? eval($hook) : null;

			redirect(forum_link(ForumCore::$forum_url['post'], $new_pid), ForumCore::$lang['Post redirect']);
		}
	}
});

// do_action( "admin_post_nopriv_{$action}" );
add_action('admin_post_pun_profile', function()
{
	require FORUM_ROOT.'include/common.php';

	// Load the profile.php language file
	ForumCore::add_lang('profile');

	$forum_db = new DBLayer;

	ForumCore::$id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
	$section = isset($_GET['section']) ? $_GET['section'] : 'about';	// Default to section "about"

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

	// User pressed the cancel button
	if (isset($_POST['cancel']))
	{
		redirect(forum_link(ForumCore::$forum_url['profile_admin'], ForumCore::$id), ForumCore::$lang['Cancel redirect']);
	}

	// Delete User
	else if (isset($_POST['delete_user_comply']))
	{
		if (ForumUser::$forum_user['g_id'] != FORUM_ADMIN)
			message(ForumCore::$lang['No permission']);

		if (ForumUser::$user['g_id'] == FORUM_ADMIN)
			message(ForumCore::$lang['Cannot delete admin']);

		($hook = get_hook('pf_delete_user_form_submitted')) ? eval($hook) : null;

		delete_user(ForumCore::$id, isset($_POST['delete_posts']));

		// Remove cache file with forum stats
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		{
			require FORUM_ROOT.'include/cache.php';
		}

		clean_stats_cache();

		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['User delete redirect']);

		($hook = get_hook('pf_delete_user_pre_redirect')) ? eval($hook) : null;

		redirect(forum_link(ForumCore::$forum_url['index']), ForumCore::$lang['User delete redirect']);
	}

	// Update Profile Info
	else if (isset($_POST['update']))
	{
		// Make sure we are allowed to edit this user's profile
		if (ForumUser::$forum_user['id'] != ForumCore::$id &&
			ForumUser::$forum_user['g_id'] != FORUM_ADMIN &&
			(ForumUser::$forum_user['g_moderator'] != '1' || ForumUser::$forum_user['g_mod_edit_users'] == '0' || ForumUser::$user['g_id'] == FORUM_ADMIN || ForumUser::$user['g_moderator'] == '1'))
			message(ForumCore::$lang['No permission']);

		($hook = get_hook('pf_change_details_form_submitted')) ? eval($hook) : null;

		$form = array();
		// Extract allowed elements from $_POST['form']
		function extract_elements($allowed_elements)
		{
			$form = array();

			foreach ($_POST['form'] as $key => $value)
			{
				if (in_array($key, $allowed_elements))
					$form[$key] = $value;
			}

			return $form;
		}

		$username_updated = false;

		// Validate input depending on section
		switch ($section)
		{
			case 'identity':
			{
				$form = extract_elements(array('realname', 'url', 'location', 'jabber', 'icq', 'msn', 'aim', 'yahoo', 'facebook', 'twitter', 'linkedin', 'skype'));

				($hook = get_hook('pf_change_details_identity_validation')) ? eval($hook) : null;

				if (ForumUser::$forum_user['is_admmod'])
				{
					// Are we allowed to change usernames?
					if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN || (ForumUser::$forum_user['g_moderator'] == '1' && ForumUser::$forum_user['g_mod_rename_users'] == '1'))
					{
						$form['username'] = forum_trim($_POST['req_username']);
						$old_username = forum_trim($_POST['old_username']);

						// Validate the new username
						ForumCore::$errors = array_merge(ForumCore::$errors, pun_validate_username($form['username'], ForumCore::$id));

						if ($form['username'] != $old_username)
							$username_updated = true;
					}

					// We only allow administrators to update the post count
					if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN)
						$form['num_posts'] = intval($_POST['num_posts']);
				}

				if (ForumUser::$forum_user['is_admmod'])
				{
					if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
						require FORUM_ROOT.'include/email.php';

					// Validate the email-address
					$form['email'] = strtolower(forum_trim($_POST['req_email']));
					if (!is_valid_email($form['email']))
						$errors[] = ForumCore::$lang['Invalid e-mail'];
				}

				if (ForumUser::$forum_user['is_admmod'])
					$form['admin_note'] = forum_trim($_POST['admin_note']);

				if (ForumUser::$forum_user['g_id'] == FORUM_ADMIN)
					$form['title'] = forum_trim($_POST['title']);
				else if (ForumUser::$forum_user['g_set_title'] == '1')
				{
					$form['title'] = forum_trim($_POST['title']);

					if ($form['title'] != '')
					{
						// A list of words that the title may not contain
						// If the language is English, there will be some duplicates, but it's not the end of the world
						$forbidden = array('Member', 'Moderator', 'Administrator', 'Banned', 'Guest', ForumCore::$lang['Member'], ForumCore::$lang['Moderator'], ForumCore::$lang['Administrator'], ForumCore::$lang['Banned'], ForumCore::$lang['Guest']);

						if (in_array($form['title'], $forbidden))
							ForumCore::$errors[] = ForumCore::$lang['Forbidden title'];
					}
				}

				// Add http:// if the URL doesn't contain it or https:// already
				if ($form['url'] != '' && strpos(strtolower($form['url']), 'http://') !== 0 && strpos(strtolower($form['url']), 'https://') !== 0)
					$form['url'] = 'http://'.$form['url'];

				//check Facebook for validity
				if (strpos($form['facebook'], 'http://') === 0 || strpos($form['facebook'], 'https://') === 0)
					if (!preg_match('#https?://(www\.)?facebook.com/.+?#', $form['facebook']))
						ForumCore::$errors[] = ForumCore::$lang['Bad Facebook'];

				//check Twitter for validity
				if (strpos($form['twitter'], 'http://') === 0 || strpos($form['twitter'], 'https://') === 0)
					if (!preg_match('#https?://twitter.com/.+?#', $form['twitter']))
						ForumCore::$errors[] = ForumCore::$lang['Bad Twitter'];

				//check LinkedIn for validity
				if (strpos($form['linkedin'], 'http://') === 0 || strpos($form['linkedin'], 'https://') === 0)
					if (!preg_match('#https?://(www\.)?linkedin.com/.+?#', $form['linkedin']))
						ForumCore::$errors[] = ForumCore::$lang['Bad LinkedIn'];

				// Add http:// if the LinkedIn doesn't contain it or https:// already
				if ($form['linkedin'] != '' && strpos(strtolower($form['linkedin']), 'http://') !== 0 && strpos(strtolower($form['linkedin']), 'https://') !== 0)
					$form['linkedin'] = 'http://'.$form['linkedin'];

				// If the ICQ UIN contains anything other than digits it's invalid
				if ($form['icq'] != '' && !ctype_digit($form['icq']))
					ForumCore::$errors[] = ForumCore::$lang['Bad ICQ'];

				break;
			}

			case 'settings':
			{
				$form = extract_elements(array('dst', 'timezone', 'language', 'email_setting', 'notify_with_post', 'auto_notify', 'time_format', 'date_format', 'disp_topics', 'disp_posts', 'show_smilies', 'show_img', 'show_img_sig', 'show_avatars', 'show_sig', 'style'));

				($hook = get_hook('pf_change_details_settings_validation')) ? eval($hook) : null;

				$form['dst'] = (isset($form['dst'])) ? 1 : 0;
				$form['time_format'] = (isset($form['time_format'])) ? intval($form['time_format']) : 0;
				$form['date_format'] = (isset($form['date_format'])) ? intval($form['date_format']) : 0;
				$form['timezone'] = (isset($form['timezone'])) ? floatval($form['timezone']) : ForumCore::$forum_config['o_default_timezone'];

				// Validate timezone
				if (($form['timezone'] > 14.0) || ($form['timezone'] < -12.0)) {
					message(ForumCore::$lang['Bad request']);
				}

				$form['email_setting'] = intval($form['email_setting']);
				if ($form['email_setting'] < 0 || $form['email_setting'] > 2) $form['email_setting'] = 1;

				if (ForumCore::$forum_config['o_subscriptions'] == '1')
				{
					if (!isset($form['notify_with_post']) || $form['notify_with_post'] != '1') $form['notify_with_post'] = '0';
					if (!isset($form['auto_notify']) || $form['auto_notify'] != '1') $form['auto_notify'] = '0';
				}

				// Make sure we got a valid language string
				if (isset($form['language']))
				{
					$form['language'] = preg_replace('#[\.\\\/]#', '', $form['language']);
					if (!file_exists(FORUM_ROOT.'lang/'.$form['language'].'/common.php'))
						message(ForumCore::$lang['Bad request']);
				}

				if ($form['disp_topics'] != '' && intval($form['disp_topics']) < 3) $form['disp_topics'] = 3;
				if ($form['disp_topics'] != '' && intval($form['disp_topics']) > 75) $form['disp_topics'] = 75;
				if ($form['disp_posts'] != '' && intval($form['disp_posts']) < 3) $form['disp_posts'] = 3;
				if ($form['disp_posts'] != '' && intval($form['disp_posts']) > 75) $form['disp_posts'] = 75;

				if (!isset($form['show_smilies']) || $form['show_smilies'] != '1') $form['show_smilies'] = '0';
				if (!isset($form['show_img']) || $form['show_img'] != '1') $form['show_img'] = '0';
				if (!isset($form['show_img_sig']) || $form['show_img_sig'] != '1') $form['show_img_sig'] = '0';
				if (!isset($form['show_avatars']) || $form['show_avatars'] != '1') $form['show_avatars'] = '0';
				if (!isset($form['show_sig']) || $form['show_sig'] != '1') $form['show_sig'] = '0';

				// Make sure we got a valid style string
				if (isset($form['style']))
				{
					$form['style'] = preg_replace('#[\.\\\/]#', '', $form['style']);
					if (!file_exists(FORUM_ROOT.'style/'.$form['style'].'/'.$form['style'].'.php'))
						message(ForumCore::$lang['Bad request']);
				}
				break;
			}

			case 'signature':
			{
				if (ForumCore::$forum_config['o_signatures'] == '0')
					message(ForumCore::$lang['Signatures disabled']);

				($hook = get_hook('pf_change_details_signature_validation')) ? eval($hook) : null;

				// Clean up signature from POST
				$form['signature'] = forum_linebreaks(forum_trim($_POST['signature']));

				// Validate signature
				if (utf8_strlen($form['signature']) > ForumCore::$forum_config['p_sig_length'])
					ForumCore::$errors[] = sprintf(ForumCore::$lang['Sig too long'], forum_number_format(ForumCore::$forum_config['p_sig_length']), forum_number_format(utf8_strlen($form['signature']) - ForumCore::$forum_config['p_sig_length']));
				if (substr_count($form['signature'], "\n") > (ForumCore::$forum_config['p_sig_lines'] - 1))
					ForumCore::$errors[] = sprintf(ForumCore::$lang['Sig too many lines'], forum_number_format(ForumCore::$forum_config['p_sig_lines']));

				if ($form['signature'] != '' && ForumCore::$forum_config['p_sig_all_caps'] == '0' && check_is_all_caps($form['signature']) && !ForumUser::$forum_user['is_admmod'])
					$form['signature'] = utf8_ucwords(utf8_strtolower($form['signature']));

				// Validate BBCode syntax
				if (ForumCore::$forum_config['p_sig_bbcode'] == '1' || ForumCore::$forum_config['o_make_links'] == '1')
				{
					if (!defined('FORUM_PARSER_LOADED'))
						require FORUM_ROOT.'include/parser.php';

					$form['signature'] = preparse_bbcode($form['signature'], ForumCore::$errors, true);
				}

				break;
			}

			case 'avatar':
			{
				if (ForumCore::$forum_config['o_avatars'] == '0')
					message(ForumCore::$lang['Avatars disabled']);

				($hook = get_hook('pf_change_details_avatar_validation')) ? eval($hook) : null;

				if (!isset($_FILES['req_file']))
				{
					ForumCore::$errors[] = ForumCore::$lang['No file'];
					break;
				}
				else
					$uploaded_file = $_FILES['req_file'];



				// Make sure the upload went smooth
				if (isset($uploaded_file['error']) && empty(ForumCore::$errors))
				{
					switch ($uploaded_file['error'])
					{
						case 1:	// UPLOAD_ERR_INI_SIZE
						case 2:	// UPLOAD_ERR_FORM_SIZE
							ForumCore::$errors[] = ForumCore::$lang['Too large ini'];
							break;

						case 3:	// UPLOAD_ERR_PARTIAL
							ForumCore::$errors[] = ForumCore::$lang['Partial upload'];
							break;

						case 4:	// UPLOAD_ERR_NO_FILE
							ForumCore::$errors[] = ForumCore::$lang['No file'];
							break;

						case 6:	// UPLOAD_ERR_NO_TMP_DIR
							ForumCore::$errors[] = ForumCore::$lang['No tmp directory'];
							break;

						default:
							// No error occured, but was something actually uploaded?
							if ($uploaded_file['size'] == 0)
								ForumCore::$errors[] = ForumCore::$lang['No file'];
							break;
					}
				}

				if (is_uploaded_file($uploaded_file['tmp_name']) && empty(ForumCore::$errors))
				{
					// First check simple by size and mime type
					$allowed_mime_types = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png');
					$allowed_types = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF);

					($hook = get_hook('pf_change_details_avatar_allowed_types')) ? eval($hook) : null;

					if (!in_array($uploaded_file['type'], $allowed_mime_types))
						ForumCore::$errors[] = ForumCore::$lang['Bad type'];
					else
					{
						// Make sure the file isn't too big
						if ($uploaded_file['size'] > ForumCore::$forum_config['o_avatars_size'])
							ForumCore::$errors[] = sprintf(ForumCore::$lang['Too large'], forum_number_format(ForumCore::$forum_config['o_avatars_size']));
					}

					if (empty(ForumCore::$errors))
					{
						$avatar_tmp_file = FORUM_ROOT . ForumCore::$forum_config['o_avatars_dir'].'/'.ForumCore::$id.'.tmp';

						// Move the file to the avatar directory. We do this before checking the width/height to circumvent open_basedir restrictions.
						if (!@move_uploaded_file($uploaded_file['tmp_name'], $avatar_tmp_file))
							ForumCore::$errors[] = sprintf(ForumCore::$lang['Move failed'], '<a href="mailto:'.forum_htmlencode(ForumCore::$forum_config['o_admin_email']).'">'.forum_htmlencode(ForumCore::$forum_config['o_admin_email']).'</a>');

						if (empty(ForumCore::$errors))
						{
							($hook = get_hook('pf_change_details_avatar_modify_size')) ? eval($hook) : null;

							// Now check the width, height, type
							list($width, $height, $type,) = @/**/getimagesize($avatar_tmp_file);
							if (empty($width) || empty($height) || $width > ForumCore::$forum_config['o_avatars_width'] || $height > ForumCore::$forum_config['o_avatars_height'])
							{
								@unlink($avatar_tmp_file);
								ForumCore::$errors[] = sprintf(ForumCore::$lang['Too wide or high'], ForumCore::$forum_config['o_avatars_width'], ForumCore::$forum_config['o_avatars_height']);
							}
							else if ($type == IMAGETYPE_GIF && $uploaded_file['type'] != 'image/gif')	// Prevent dodgy uploads
							{
								@unlink($avatar_tmp_file);
								ForumCore::$errors[] = ForumCore::$lang['Bad type'];
							}

							// Determine type
							$extension = null;
							$avatar_type = FORUM_AVATAR_NONE;
							if ($type == IMAGETYPE_GIF)
							{
								$extension = '.gif';
								$avatar_type = FORUM_AVATAR_GIF;
							}
							else if ($type == IMAGETYPE_JPEG)
							{
								$extension = '.jpg';
								$avatar_type = FORUM_AVATAR_JPG;
							}
							else if ($type == IMAGETYPE_PNG)
							{
								$extension = '.png';
								$avatar_type = FORUM_AVATAR_PNG;
							}

							($hook = get_hook('pf_change_details_avatar_determine_extension')) ? eval($hook) : null;

							// Check type from getimagesize type format
							if (!in_array($avatar_type, $allowed_types) || empty($extension))
							{
								@unlink($avatar_tmp_file);
								ForumCore::$errors[] = ForumCore::$lang['Bad type'];
							}

							($hook = get_hook('pf_change_details_avatar_validate_file')) ? eval($hook) : null;

							if (empty(ForumCore::$errors))
							{
								// Delete any old avatars
								delete_avatar(ForumCore::$id);

								// Put the new avatar in its place
								@rename($avatar_tmp_file, FORUM_ROOT . ForumCore::$forum_config['o_avatars_dir'].'/'.ForumCore::$id.$extension);
								@chmod(FORUM_ROOT . ForumCore::$forum_config['o_avatars_dir'].'/'.ForumCore::$id.$extension, 0644);

								// Avatar
								$avatar_width = (intval($width) > 0) ? intval($width) : 0;
								$avatar_height = (intval($height) > 0) ? intval($height) : 0;

								// Save to DB
								$query = array(
									'UPDATE'	=> 'users',
									'SET'		=> 'avatar=\''.$avatar_type.'\', avatar_height=\''.$avatar_height.'\', avatar_width=\''.$avatar_width.'\'',
									'WHERE'		=> 'id='.ForumCore::$id
								);
								($hook = get_hook('pf_change_details_avatar_qr_update_avatar')) ? eval($hook) : null;
								$forum_db->query_build($query) or error(__FILE__, __LINE__);

								// Update avatar info
								ForumUser::$user['avatar'] = $avatar_type;
								ForumUser::$user['avatar_width'] = $width;
								ForumUser::$user['avatar_height'] = $height;
							}
						}
					}

					redirect(forum_link(ForumCore::$forum_url['profile_'.$section], ForumCore::$id), ForumCore::$lang['Profile redirect']);
				}
				else if (empty(ForumCore::$errors))
					ForumCore::$errors[] = ForumCore::$lang['Unknown failure'];

				break;
			}

			default:
			{
				($hook = get_hook('pf_change_details_new_section_validation')) ? eval($hook) : null;
				break;
			}
		}

		$skip_db_update_sections = array('avatar');

		($hook = get_hook('pf_change_details_pre_database_validation')) ? eval($hook) : null;

		// All sections apart from avatar potentially affect the database
		if (!in_array($section, $skip_db_update_sections) && empty(ForumCore::$errors))
		{
			($hook = get_hook('pf_change_details_database_validation')) ? eval($hook) : null;

			// Singlequotes around non-empty values and NULL for empty values
			$new_values = array();
			foreach ($form as $key => $input)
			{
				$value = ($input !== '') ? '\''.$forum_db->escape($input).'\'' : 'NULL';

				$new_values[] = $key.'='.$value;
			}

			// Make sure we have something to update
			if (empty($new_values))
				message(ForumCore::$lang['Bad request']);

			// Run the update
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> implode(',', $new_values),
				'WHERE'		=> 'id='.ForumCore::$id
			);

			($hook = get_hook('pf_change_details_qr_update_user')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			// If we changed the username we have to update some stuff
			if ($username_updated)
			{
				($hook = get_hook('pf_change_details_username_changed')) ? eval($hook) : null;

				$query = array(
					'UPDATE'	=> 'posts',
					'SET'		=> 'poster=\''.$forum_db->escape($form['username']).'\'',
					'WHERE'		=> 'poster_id='.ForumCore::$id
				);

				($hook = get_hook('pf_change_details_qr_update_posts_poster')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				$query = array(
					'UPDATE'	=> 'topics',
					'SET'		=> 'poster=\''.$forum_db->escape($form['username']).'\'',
					'WHERE'		=> 'poster=\''.$forum_db->escape($old_username).'\''
				);

				($hook = get_hook('pf_change_details_qr_update_topics_poster')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				$query = array(
					'UPDATE'	=> 'topics',
					'SET'		=> 'last_poster=\''.$forum_db->escape($form['username']).'\'',
					'WHERE'		=> 'last_poster=\''.$forum_db->escape($old_username).'\''
				);

				($hook = get_hook('pf_change_details_qr_update_topics_last_poster')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				$query = array(
					'UPDATE'	=> 'forums',
					'SET'		=> 'last_poster=\''.$forum_db->escape($form['username']).'\'',
					'WHERE'		=> 'last_poster=\''.$forum_db->escape($old_username).'\''
				);

				($hook = get_hook('pf_change_details_qr_update_forums_last_poster')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				$query = array(
					'UPDATE'	=> 'online',
					'SET'		=> 'ident=\''.$forum_db->escape($form['username']).'\'',
					'WHERE'		=> 'ident=\''.$forum_db->escape($old_username).'\''
				);

				($hook = get_hook('pf_change_details_qr_update_online_ident')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				$query = array(
					'UPDATE'	=> 'posts',
					'SET'		=> 'edited_by=\''.$forum_db->escape($form['username']).'\'',
					'WHERE'		=> 'edited_by=\''.$forum_db->escape($old_username).'\''
				);

				($hook = get_hook('pf_change_details_qr_update_posts_edited_by')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				// If the user is a moderator or an administrator we have to update the moderator lists and bans cache
				if (ForumUser::$user['g_id'] == FORUM_ADMIN || ForumUser::$user['g_moderator'] == '1')
				{
					$query = array(
						'SELECT'	=> 'f.id, f.moderators',
						'FROM'		=> 'forums AS f'
					);

					($hook = get_hook('pf_change_details_qr_get_all_forum_mods')) ? eval($hook) : null;
					$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
					while ($cur_forum = $forum_db->fetch_assoc($result))
					{
						$cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();

						if (in_array(ForumCore::$id, $cur_moderators))
						{
							unset($cur_moderators[$old_username]);
							$cur_moderators[$form['username']] = ForumCore::$id;
							ksort($cur_moderators);

							$query = array(
								'UPDATE'	=> 'forums',
								'SET'		=> 'moderators=\''.$forum_db->escape(serialize($cur_moderators)).'\'',
								'WHERE'		=> 'id='.$cur_forum['id']
							);

							($hook = get_hook('pf_change_details_qr_update_forum_moderators')) ? eval($hook) : null;
							$forum_db->query_build($query) or error(__FILE__, __LINE__);
						}
					}

					// Regenerate the bans cache
					if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
						require FORUM_ROOT.'include/cache.php';

					generate_bans_cache();
				}
			}

			// Add flash message
			//$forum_flash->add_info(ForumCore::$lang['Profile redirect']);

			($hook = get_hook('pf_change_details_pre_redirect')) ? eval($hook) : null;

			redirect(forum_link(ForumCore::$forum_url['profile_'.$section], ForumCore::$id), ForumCore::$lang['Profile redirect']);
		}
	}

	else if (isset($_POST['update_group_membership']))
	{
		if (ForumUser::$forum_user['g_id'] != FORUM_ADMIN)
			message(ForumCore::$lang['No permission']);
	
		($hook = get_hook('pf_change_group_form_submitted')) ? eval($hook) : null;
	
		$new_group_id = intval($_POST['group_id']);
	
		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> 'group_id='.$new_group_id,
			'WHERE'		=> 'id='.ForumCore::$id
		);
	
		($hook = get_hook('pf_change_group_qr_update_group')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	
		$query = array(
			'SELECT'	=> 'g.g_moderator',
			'FROM'		=> 'groups AS g',
			'WHERE'		=> 'g.g_id='.$new_group_id
		);
	
		($hook = get_hook('pf_change_group_qr_check_new_group_mod')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$new_group_mod = $forum_db->result($result);
	
		// If the user was a moderator or an administrator (and no longer is), we remove him/her from the moderator list in all forums
		if ((ForumUser::$user['g_id'] == FORUM_ADMIN || ForumUser::$user['g_moderator'] == '1') && $new_group_id != FORUM_ADMIN && $new_group_mod != '1')
			clean_forum_moderators();
	
		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Group membership redirect']);
	
		($hook = get_hook('pf_change_group_pre_redirect')) ? eval($hook) : null;
	
		redirect(forum_link(ForumCore::$forum_url['profile_admin'], ForumCore::$id), ForumCore::$lang['Group membership redirect']);
	}

	else if (isset($_POST['update_forums']))
	{
		if (ForumUser::$forum_user['g_id'] != FORUM_ADMIN)
			message(ForumCore::$lang['No permission']);
	
		($hook = get_hook('pf_forum_moderators_form_submitted')) ? eval($hook) : null;
	
		$moderator_in = (isset($_POST['moderator_in'])) ? array_keys($_POST['moderator_in']) : array();
	
		// Loop through all forums
		$query = array(
			'SELECT'	=> 'f.id, f.moderators',
			'FROM'		=> 'forums AS f'
		);
	
		($hook = get_hook('pf_forum_moderators_qr_get_all_forum_mods')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		while ($cur_forum = $forum_db->fetch_assoc($result))
		{
			$cur_moderators = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();
	
			// If the user should have moderator access (and he/she doesn't already have it)
			if (in_array($cur_forum['id'], $moderator_in) && !in_array(ForumCore::$id, $cur_moderators))
			{
				$cur_moderators[ForumUser::$user['username']] = ForumCore::$id;
				ksort($cur_moderators);
			}
			// If the user shouldn't have moderator access (and he/she already has it)
			else if (!in_array($cur_forum['id'], $moderator_in) && in_array(ForumCore::$id, $cur_moderators))
				unset($cur_moderators[ForumUser::$user['username']]);
	
			$cur_moderators = (!empty($cur_moderators)) ? '\''.$forum_db->escape(serialize($cur_moderators)).'\'' : 'NULL';
	
			$query = array(
				'UPDATE'	=> 'forums',
				'SET'		=> 'moderators='.$cur_moderators,
				'WHERE'		=> 'id='.$cur_forum['id']
			);
	
			($hook = get_hook('pf_forum_moderators_qr_update_forum_moderators')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
	
		// Add flash message
		//$forum_flash->add_info(ForumCore::$lang['Moderate forums redirect']);
	
		($hook = get_hook('pf_forum_moderators_pre_redirect')) ? eval($hook) : null;
	
		redirect(forum_link(ForumCore::$forum_url['profile_admin'], ForumCore::$id), ForumCore::$lang['Moderate forums redirect']);
	}

	else if (isset($_POST['ban']))
	{
		if (ForumUser::$forum_user['g_id'] != FORUM_ADMIN && (ForumUser::$forum_user['g_moderator'] != '1' || ForumUser::$forum_user['g_mod_ban_users'] == '0'))
			message(ForumCore::$lang['No permission']);
	
		($hook = get_hook('pf_ban_user_selected')) ? eval($hook) : null;
	
		redirect(pun_admin_link(ForumCore::$forum_url['admin_bans']).'&add_ban='.ForumCore::$id, ForumCore::$lang['Ban redirect']);
	}
});
