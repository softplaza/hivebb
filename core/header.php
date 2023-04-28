<?php
/**
 * @package header.php
 */
use \HiveBB\ForumCore;
use \HiveBB\ForumUser;

if (defined('FORUM_PAGE_SECTION'))
{
  ForumCore::add_lang('admin_common');

  ForumCore::$forum_page['admin_menu'] = ForumCore::$forum_page['admin_submenu'] = array();

     //if ($forum_user['g_id'] != FORUM_ADMIN)
     if (!is_admin())
         ForumCore::$forum_page['admin_menu']['index'] = '<li class="active nav-item"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_index']).'">'.ForumCore::$lang['Moderate'].'</a></li>';
     else
     {
         ForumCore::$forum_page['admin_menu']['index'] = '<li class="nav-item '.((FORUM_PAGE_SECTION == 'start') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_menu'])) ? '' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_index']).'" class="nav-link">'.ForumCore::$lang['Start'].'</a></li>';
         
         ForumCore::$forum_page['admin_menu']['settings_setup'] = '<li class="nav-item '.((FORUM_PAGE_SECTION == 'settings') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_menu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_settings_setup']).'" class="nav-link">'.ForumCore::$lang['Settings'].'</a></li>';

         ForumCore::$forum_page['admin_menu']['users'] = '<li class="nav-item '.((FORUM_PAGE_SECTION == 'users') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_menu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_users']).'" class="nav-link">'.ForumCore::$lang['Users'].'</a></li>';

         ForumCore::$forum_page['admin_menu']['reports'] = '<li class="nav-item '.((FORUM_PAGE_SECTION == 'management') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_menu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_management_reports']).'" class="nav-link">'.ForumCore::$lang['Management'].'</a></li>';

         ForumCore::$forum_page['admin_menu']['extensions_manage'] = '<li class="nav-item '.((FORUM_PAGE_SECTION == 'extensions') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_menu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_extensions_manage']).'" class="nav-link">'.ForumCore::$lang['Extensions'].'</a></li>';
     }

     ($hook = get_hook('ca_fn_generate_admin_menu_new_link')) ? eval($hook) : null;

     //echo '<nav class="navbar navbar-dark bg-primary">'.implode("\n\t\t", ForumCore::$forum_page['admin_menu']).'</nav>';

     //if ($forum_user['g_id'] != FORUM_ADMIN)
     if (!is_admin())
     {
         ForumCore::$forum_page['admin_submenu']['index'] = '<li class="'.((FORUM_PAGE == 'admin-information') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_index']).'">'.ForumCore::$lang['Information'].'</span></a></li>';
         ForumCore::$forum_page['admin_submenu']['users'] = '<li class="'.((FORUM_PAGE == 'admin-users') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_users']).'">'.ForumCore::$lang['Searches'].'</a></li>';

         if ($forum_config['o_censoring'] == '1')
             ForumCore::$forum_page['admin_submenu']['censoring'] = '<li class="'.((FORUM_PAGE == 'admin-censoring') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_censoring']).'">'.ForumCore::$lang['Censoring'].'</a></li>';

         ForumCore::$forum_page['admin_submenu']['reports'] = '<li class="'.((FORUM_PAGE == 'admin-reports') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_reports']).'">'.ForumCore::$lang['Reports'].'</a></li>';

         if ($forum_user['g_mod_ban_users'] == '1')
             ForumCore::$forum_page['admin_submenu']['bans'] = '<li class="'.((FORUM_PAGE == 'admin-bans') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_bans']).'">'.ForumCore::$lang['Bans'].'</a></li>';
     }
     else
     {
         if (FORUM_PAGE_SECTION == 'start')
         {
             ForumCore::$forum_page['admin_submenu']['index'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-information') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_index']).'" class="nav-link">'.ForumCore::$lang['Information'].'</a></li>';

             ForumCore::$forum_page['admin_submenu']['categories'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-categories') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_categories']).'" class="nav-link">'.ForumCore::$lang['Categories'].'</a></li>';

             ForumCore::$forum_page['admin_submenu']['forums'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-forums') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_forums']).'" class="nav-link">'.ForumCore::$lang['Forums'].'</a></li>';
         }
         else if (FORUM_PAGE_SECTION == 'users')
         {
             ForumCore::$forum_page['admin_submenu']['users'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-users' || FORUM_PAGE == 'admin-uresults' || FORUM_PAGE == 'admin-iresults') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_users']).'" class="nav-link">'.ForumCore::$lang['Searches'].'</a></li>';

             ForumCore::$forum_page['admin_submenu']['groups'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-groups') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_groups']).'" class="nav-link">'.ForumCore::$lang['Groups'].'</a></li>';

             ForumCore::$forum_page['admin_submenu']['ranks'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-ranks') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_ranks']).'" class="nav-link">'.ForumCore::$lang['Ranks'].'</a></li>';

             ForumCore::$forum_page['admin_submenu']['bans'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-bans') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_bans']).'" class="nav-link">'.ForumCore::$lang['Bans'].'</a></li>';
         }
         else if (FORUM_PAGE_SECTION == 'settings')
         {
             ForumCore::$forum_page['admin_submenu']['settings_setup'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-settings-setup') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_settings_setup']).'" class="nav-link">'.ForumCore::$lang['Setup'].'</a></li>';

             ForumCore::$forum_page['admin_submenu']['settings_features'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-settings-features') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_settings_features']).'" class="nav-link">'.ForumCore::$lang['Features'].'</a></li>';

             ForumCore::$forum_page['admin_submenu']['settings-announcements'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-settings-announcements') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_settings_announcements']).'" class="nav-link">'.ForumCore::$lang['Announcements'].'</a></li>';

             ForumCore::$forum_page['admin_submenu']['settings-maintenance'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-settings-maintenance') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_settings_maintenance']).'" class="nav-link">'.ForumCore::$lang['Maintenance mode'].'</a></li>';

             ForumCore::$forum_page['admin_submenu']['settings-email'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-settings-email') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_settings_email']).'" class="nav-link">'.ForumCore::$lang['E-mail'].'</a></li>';

             ForumCore::$forum_page['admin_submenu']['settings-registration'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-settings-registration') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_settings_registration']).'" class="nav-link">'.ForumCore::$lang['Registration'].'</a></li>';
         }
         else if (FORUM_PAGE_SECTION == 'management')
         {
             ForumCore::$forum_page['admin_submenu']['reports'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-reports') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_management_reports']).'" class="nav-link">'.ForumCore::$lang['Reports'].'</a></li>';

             ForumCore::$forum_page['admin_submenu']['prune'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-prune') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_management_prune']).'" class="nav-link">'.ForumCore::$lang['Prune topics'].'</a></li>';

             ForumCore::$forum_page['admin_submenu']['reindex'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-reindex') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_management_reindex']).'" class="nav-link">'.ForumCore::$lang['Rebuild index'].'</a></li>';

             ForumCore::$forum_page['admin_submenu']['censoring'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-censoring') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_management_censoring']).'" class="nav-link">'.ForumCore::$lang['Censoring'].'</a></li>';
         }
         else if (FORUM_PAGE_SECTION == 'extensions')
         {
             ForumCore::$forum_page['admin_submenu']['extensions-manage'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-extensions-manage') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_extensions_manage']).'" class="nav-link">'.ForumCore::$lang['Manage extensions'].'</a></li>';

             ForumCore::$forum_page['admin_submenu']['extensions-hotfixes'] = '<li class="nav-item '.((FORUM_PAGE == 'admin-extensions-hotfixes') ? 'active' : 'normal').((empty(ForumCore::$forum_page['admin_submenu'])) ? ' first-item' : '').'"><a href="'.pun_admin_link(ForumCore::$forum_url['admin_extensions_hotfixes']).'" class="nav-link">'.ForumCore::$lang['Manage hotfixes'].'</a></li>';
         }
     }

     ($hook = get_hook('ca_fn_generate_admin_menu_new_sublink')) ? eval($hook) : null;

     //echo (!empty(ForumCore::$forum_page['admin_submenu'])) ? '<nav class="navbar navbar-dark bg-primary">'.implode("\n\t\t", ForumCore::$forum_page['admin_submenu']).'</nav>' : '';
 ?>
  <div id="brd-navlinks" class="gen-content mt-1">
    <ul>
      <?php echo implode("\n\t\t", ForumCore::$forum_page['admin_submenu']); ?>
    </ul>
  </div>
<?php
}
else
{

  // SHORTCODE [pun_header]
  add_shortcode('pun_header', function ()
  {
?>
  <div id="brd-navlinks" class="gen-content">
    <ul>
      <?php echo generate_navlinks(); ?>
    </ul>
  </div>

  <div id="brd-visit" class="gen-content">
  <?php
    if (ForumUser::$forum_user['is_guest'])
      echo '<p id="welcome"><span>'.ForumCore::$lang['Not logged in'].'</span> <span>'.ForumCore::$lang['Login nag'].'</span></p>';
    else
      echo '<p id="welcome"><span>'.sprintf(ForumCore::$lang['Logged in as'], '<strong>'.forum_htmlencode(ForumUser::$forum_user['username']).'</strong>').'</span></p>';
?>
  </div>
<?php
  });

}
