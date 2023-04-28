<?php
/**
 * Loads common functions used in the administration panel.
 *
 * @copyright (C) 2023 HiveBB, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package HiveBB
 */
use \HiveBB\ForumCore;
use \HiveBB\ForumUser;
use \HiveBB\DBLayer;

// Make sure no one attempts to run this script "directly"
if (!defined('FORUM'))
	exit;

//
// Display the admin navigation menu
//
function generate_admin_menu($submenu)
{
	$return = ($hook = get_hook('ca_fn_generate_admin_menu_start')) ? eval($hook) : null;
	if ($return !== null)
		return $return;

	if ($submenu)
	{
		ForumCore::$forum_page['admin_submenu'] = array();

		if (ForumUser::$forum_user['g_id'] != FORUM_ADMIN)
		{
			ForumCore::$forum_page['admin_submenu']['index'] = '<li class="'.((FORUM_PAGE == 'admin-information') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_index']).'">'.ForumCore::$lang['Information'].'</span></a></li>';
			ForumCore::$forum_page['admin_submenu']['users'] = '<li class="'.((FORUM_PAGE == 'admin-users') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_users']).'">'.ForumCore::$lang['Searches'].'</a></li>';

			if (ForumCore::$forum_config['o_censoring'] == '1')
				ForumCore::$forum_page['admin_submenu']['censoring'] = '<li class="'.((FORUM_PAGE == 'admin-censoring') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_censoring']).'">'.ForumCore::$lang['Censoring'].'</a></li>';

			ForumCore::$forum_page['admin_submenu']['reports'] = '<li class="'.((FORUM_PAGE == 'admin-reports') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_reports']).'">'.ForumCore::$lang['Reports'].'</a></li>';

			if (ForumUser::$forum_user['g_mod_ban_users'] == '1')
				ForumCore::$forum_page['admin_submenu']['bans'] = '<li class="'.((FORUM_PAGE == 'admin-bans') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_bans']).'">'.ForumCore::$lang['Bans'].'</a></li>';
		}
		else
		{
			if (FORUM_PAGE_SECTION == 'start')
			{
				ForumCore::$forum_page['admin_submenu']['index'] = '<li class="'.((FORUM_PAGE == 'admin-information') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_index']).'">'.ForumCore::$lang['Information'].'</a></li>';
				ForumCore::$forum_page['admin_submenu']['categories'] = '<li class="'.((FORUM_PAGE == 'admin-categories') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_categories']).'">'.ForumCore::$lang['Categories'].'</a></li>';
				ForumCore::$forum_page['admin_submenu']['forums'] = '<li class="'.((FORUM_PAGE == 'admin-forums') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_forums']).'">'.ForumCore::$lang['Forums'].'</a></li>';
			}
			else if (FORUM_PAGE_SECTION == 'users')
			{
				ForumCore::$forum_page['admin_submenu']['users'] = '<li class="'.((FORUM_PAGE == 'admin-users' || FORUM_PAGE == 'admin-uresults' || FORUM_PAGE == 'admin-iresults') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_users']).'">'.ForumCore::$lang['Searches'].'</a></li>';
				ForumCore::$forum_page['admin_submenu']['groups'] = '<li class="'.((FORUM_PAGE == 'admin-groups') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_groups']).'">'.ForumCore::$lang['Groups'].'</a></li>';
				ForumCore::$forum_page['admin_submenu']['ranks'] = '<li class="'.((FORUM_PAGE == 'admin-ranks') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_ranks']).'">'.ForumCore::$lang['Ranks'].'</a></li>';
				ForumCore::$forum_page['admin_submenu']['bans'] = '<li class="'.((FORUM_PAGE == 'admin-bans') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_bans']).'">'.ForumCore::$lang['Bans'].'</a></li>';
			}
			else if (FORUM_PAGE_SECTION == 'settings')
			{
				ForumCore::$forum_page['admin_submenu']['settings_setup'] = '<li class="'.((FORUM_PAGE == 'admin-settings-setup') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_settings_setup']).'">'.ForumCore::$lang['Setup'].'</a></li>';
				ForumCore::$forum_page['admin_submenu']['settings_features'] = '<li class="'.((FORUM_PAGE == 'admin-settings-features') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_settings_features']).'">'.ForumCore::$lang['Features'].'</a></li>';
				ForumCore::$forum_page['admin_submenu']['settings-announcements'] = '<li class="'.((FORUM_PAGE == 'admin-settings-announcements') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_settings_announcements']).'">'.ForumCore::$lang['Announcements'].'</a></li>';
				ForumCore::$forum_page['admin_submenu']['settings-email'] = '<li class="'.((FORUM_PAGE == 'admin-settings-email') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_settings_email']).'">'.ForumCore::$lang['E-mail'].'</a></li>';
				ForumCore::$forum_page['admin_submenu']['settings-registration'] = '<li class="'.((FORUM_PAGE == 'admin-settings-registration') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_settings_registration']).'">'.ForumCore::$lang['Registration'].'</a></li>';
				ForumCore::$forum_page['admin_submenu']['censoring'] = '<li class="'.((FORUM_PAGE == 'admin-censoring') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_censoring']).'">'.ForumCore::$lang['Censoring'].'</a></li>';
			}
			else if (FORUM_PAGE_SECTION == 'management')
			{
				ForumCore::$forum_page['admin_submenu']['reports'] = '<li class="'.((FORUM_PAGE == 'admin-reports') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_reports']).'">'.ForumCore::$lang['Reports'].'</a></li>';
				ForumCore::$forum_page['admin_submenu']['prune'] = '<li class="'.((FORUM_PAGE == 'admin-prune') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_prune']).'">'.ForumCore::$lang['Prune topics'].'</a></li>';
				ForumCore::$forum_page['admin_submenu']['reindex'] = '<li class="'.((FORUM_PAGE == 'admin-reindex') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_reindex']).'">'.ForumCore::$lang['Rebuild index'].'</a></li>';
				ForumCore::$forum_page['admin_submenu']['options-maintenance'] = '<li class="'.((FORUM_PAGE == 'admin-settings-maintenance') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_settings_maintenance']).'">'.ForumCore::$lang['Maintenance mode'].'</a></li>';
			}
			else if (FORUM_PAGE_SECTION == 'extensions')
			{
				ForumCore::$forum_page['admin_submenu']['extensions-manage'] = '<li class="'.((FORUM_PAGE == 'admin-extensions-manage') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_extensions_manage']).'">'.ForumCore::$lang['Manage extensions'].'</a></li>';
				ForumCore::$forum_page['admin_submenu']['extensions-hotfixes'] = '<li class="'.((FORUM_PAGE == 'admin-extensions-hotfixes') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_extensions_hotfixes']).'">'.ForumCore::$lang['Manage hotfixes'].'</a></li>';
			}
		}

		($hook = get_hook('ca_fn_generate_admin_menu_new_sublink')) ? eval($hook) : null;

		return (!empty(ForumCore::$forum_page['admin_submenu'])) ? implode("\n\t\t", ForumCore::$forum_page['admin_submenu']) : '';
	}
	else
	{
		if (ForumUser::$forum_user['g_id'] != FORUM_ADMIN)
			ForumCore::$forum_page['admin_menu']['index'] = '<li class="active first-item"><a href="'.forum_link(ForumCore::$forum_url['admin_index']).'"><span>'.ForumCore::$lang['Moderate'].'</span></a></li>';
		else
		{
			ForumCore::$forum_page['admin_menu']['index'] = '<li class="'.((FORUM_PAGE_SECTION == 'start') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_menu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_index']).'"><span>'.ForumCore::$lang['Start'].'</span></a></li>';
			ForumCore::$forum_page['admin_menu']['settings_setup'] = '<li class="'.((FORUM_PAGE_SECTION == 'settings') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_menu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_settings_setup']).'"><span>'.ForumCore::$lang['Settings'].'</span></a></li>';
			ForumCore::$forum_page['admin_menu']['users'] = '<li class="'.((FORUM_PAGE_SECTION == 'users') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_menu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_users']).'"><span>'.ForumCore::$lang['Users'].'</span></a></li>';
			ForumCore::$forum_page['admin_menu']['reports'] = '<li class="'.((FORUM_PAGE_SECTION == 'management') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_menu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_reports']).'"><span>'.ForumCore::$lang['Management'].'</span></a></li>';
			ForumCore::$forum_page['admin_menu']['extensions_manage'] = '<li class="'.((FORUM_PAGE_SECTION == 'extensions') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_menu'])) ? ' first-item' : '').'"><a href="'.forum_link(ForumCore::$forum_url['admin_extensions_manage']).'"><span>'.ForumCore::$lang['Extensions'].'</span></a></li>';
		}

		($hook = get_hook('ca_fn_generate_admin_menu_new_link')) ? eval($hook) : null;

		return implode("\n\t\t", ForumCore::$forum_page['admin_menu']);
	}
}

// Add config value to forum config table
// Warning!
// This function dont refresh config cache - use "forum_clear_cache()" if
// call this function outside install/uninstall extension manifest section
function forum_config_add($name, $value)
{
	$forum_db = new DBLayer;

	if (!empty($name) && !isset(ForumCore::$forum_config[$name]))
	{
		$query = array(
			'INSERT'	=> 'conf_name, conf_value',
			'INTO'		=> 'config',
			'VALUES'	=> '\''.$name.'\', \''.$value.'\''
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}
}


// Remove config value from forum config table
// Warning!
// This function dont refresh config cache - use "forum_clear_cache()" if
// call this function outside install/uninstall extension manifest section
function forum_config_remove($name)
{
	$forum_db = new DBLayer;

	if (is_array($name) && count($name) > 0)
	{
		if (!function_exists('clean_conf_names'))
		{
			function clean_conf_names($n)
			{
				global $forum_db;
				return '\''.$forum_db->escape($n).'\'';
			}
		}

		$name = array_map('clean_conf_names', $name);

		$query = array(
			'DELETE'	=> 'config',
			'WHERE'		=> 'conf_name in ('.implode(',', $name).')',
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}
	else if (!empty($name))
	{
		$query = array(
			'DELETE'	=> 'config',
			'WHERE'		=> 'conf_name=\''.$forum_db->escape($name).'\''
		);
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}
}

($hook = get_hook('ca_new_function')) ? eval($hook) : null;
