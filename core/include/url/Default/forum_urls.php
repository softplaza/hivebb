<?php
/**
 * Regular URL scheme.
 *
 * @copyright (C) 2023 HiveBB, partially based on PunBB
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package HiveBB
 */


// Make sure no one attempts to run this script "directly"
if (!defined('FORUM'))
	exit;

// These are the regular, "non-SEF" URLs (you probably don't want to edit these)
$forum_url = array(
	'change_email'					=>	'profile?action=change_email&amp;id=$1',
	'change_email_key'				=>	'profile?action=change_email&amp;id=$1&amp;key=$2',
	'change_password'				=>	'profile?action=change_pass&amp;id=$1',
	'change_password_key'			=>	'profile?action=change_pass&amp;id=$1&amp;key=$2',
	'delete_user'					=>	'profile?action=delete_user&amp;id=$1',
	'delete'						=>	'delete?id=$1',
	'delete_avatar'					=>	'profile?action=delete_avatar&amp;id=$1&amp;csrf_token=$2',
	'edit'							=>	'edit?id=$1',
	'email'							=>	'misc?email=$1',
	'forum'							=>	'viewforum?id=$1',
	'forum_rss'						=>	'extern?action=feed&amp;fid=$1&amp;type=rss',
	'forum_atom'					=>	'extern?action=feed&amp;fid=$1&amp;type=atom',
	'forum_subscribe'				=>	'misc?forum_subscribe=$1&amp;csrf_token=$2',
	'forum_unsubscribe'				=>	'misc?forum_unsubscribe=$1&amp;csrf_token=$2',
	'help'							=>	'help?section=$1',
	'index'							=>	'forum',
	'index_rss'						=>	'extern?action=feed&amp;type=rss',
	'index_atom'					=>	'extern?action=feed&amp;type=atom',
	'login'							=>	'login',
	'logout'						=>	'login?action=out&amp;id=$1&amp;csrf_token=$2',
	'mark_read'						=>	'misc?action=markread&amp;csrf_token=$1',
	'mark_forum_read'				=>	'misc?action=markforumread&amp;fid=$1&amp;csrf_token=$2',
	'new_topic'						=>	'post?fid=$1',
	'new_reply'						=>	'post?tid=$1',
	'opensearch'					=>	'misc?action=opensearch',
	'post'							=>	'viewtopic?pid=$1#p$1',
	'profile_about'					=>	'profile?section=about&amp;id=$1',
	'profile_identity'				=>	'profile?section=identity&id=$1',
	'profile_settings'				=>	'profile?section=settings&id=$1',
	'profile_avatar'				=>	'profile?section=avatar&id=$1',
	'profile_signature'				=>	'profile?section=signature&id=$1',
	'profile_admin'					=>	'profile?section=admin&id=$1',
	'quote'							=>	'post?tid=$1&amp;qid=$2',
	'register'						=>	'register',
	'report'						=>	'misc?report=$1',
	'request_password'				=>	'login?action=forget',
	'rules'							=>	'misc?action=rules',
	'search'						=>	'search',
	'search_advanced'				=>	'search?advanced=1',
	'search_resultft'				=>	'search?action=search&amp;keywords=$1&amp;author=$3&amp;forum=$2&amp;search_in=$4&amp;sort_by=$5&amp;sort_dir=$6&amp;show_as=$7',
	'search_results'				=>	'search?search_id=$1',
	'search_new'					=>	'search?action=show_new',
	'search_new_results'			=>	'search?action=show_new&amp;forum=$1',
	'search_recent'					=>	'search?action=show_recent',
	'search_recent_results'			=>	'search?action=show_recent&amp;value=$1',
	'search_unanswered'				=>	'search?action=show_unanswered',
	'search_subscriptions'			=>	'search?action=show_subscriptions&amp;user_id=$1',
	'search_forum_subscriptions'	=>	'search?action=show_forum_subscriptions&amp;user_id=$1',
	'search_user_posts'				=>	'search?action=show_user_posts&amp;user_id=$1',
	'search_user_topics'			=>	'search?action=show_user_topics&amp;user_id=$1',
	'subscribe'						=>	'misc?subscribe=$1&amp;csrf_token=$2',
	'topic'							=>	'viewtopic?id=$1',
	'topic_rss'						=>	'extern?action=feed&amp;tid=$1&amp;type=rss',
	'topic_atom'					=>	'extern?action=feed&amp;tid=$1&amp;type=atom',
	'topic_new_posts'				=>	'viewtopic?id=$1&amp;action=new',
	'topic_last_post'				=>	'viewtopic?id=$1&amp;action=last',
	'unsubscribe'					=>	'misc?unsubscribe=$1&amp;csrf_token=$2',
	'user'							=>	'profile?id=$1',
	'users'							=>	'userlist',
	'users_browse'					=>	'userlist?show_group=$1&amp;sort_by=$2&amp;sort_dir=$3&amp;username=$4',
	'page'							=>	'&amp;p=$1',
	'moderate_forum'				=>	'moderate?fid=$1',
	'get_host'						=>	'moderate?get_host=$1',
	'move'							=>	'moderate?fid=$1&amp;move_topics=$2',
	'open'							=>	'moderate?fid=$1&amp;open=$2&amp;csrf_token=$3',
	'close'							=>	'moderate?fid=$1&amp;close=$2&amp;csrf_token=$3',
	'stick'							=>	'moderate?fid=$1&amp;stick=$2&amp;csrf_token=$3',
	'unstick'						=>	'moderate?fid=$1&amp;unstick=$2&amp;csrf_token=$3',
	'moderate_topic'				=>	'moderate?fid=$1&amp;tid=$2',

	'admin_index'					=>	'hivebb_admin_index',
	'admin_categories'				=>	'hivebb_admin_index&section=categories',
	'admin_forums'					=>	'hivebb_admin_index&section=forums',
	'admin_forums_forum'			=>	'hivebb_admin_index&section=forums#forum$1',

	'admin_settings_'				=>	'hivebb_admin_settings',
	'admin_settings_setup'			=>	'hivebb_admin_settings&section=setup',
	'admin_settings_features'		=>	'hivebb_admin_settings&section=features',
	'admin_settings_content'		=>	'hivebb_admin_settings&section=content',
	'admin_settings_email'			=>	'hivebb_admin_settings&section=email',
	'admin_settings_announcements'	=>	'hivebb_admin_settings&section=announcements',
	'admin_settings_registration'	=>	'hivebb_admin_settings&section=registration',
	'admin_settings_communications'	=>	'hivebb_admin_settings&section=communications',
	'admin_settings_maintenance'	=>	'hivebb_admin_settings&section=maintenance',

	'admin_users'					=>	'hivebb_admin_users&section=search',
	'admin_groups'					=>	'hivebb_admin_users&section=groups',
	'admin_ranks'					=>	'hivebb_admin_users&section=ranks',
	'admin_bans'					=>	'hivebb_admin_users&section=bans&sort_by=1',
	'admin_loader'					=>	'hivebb_admin_loader',// ???

	'admin_reports'					=>	'hivebb_admin_management&section=reports',
	'admin_management_reports'		=>	'hivebb_admin_management&section=reports',
	'admin_management_prune'		=>	'hivebb_admin_management&section=prune',
	'admin_management_reindex'		=>	'hivebb_admin_management&section=reindex',
	'admin_management_censoring'	=>	'hivebb_admin_management&section=censoring',

	'admin_extensions_manage'		=>	'hivebb_admin_extensions&section=manage',
	'admin_extensions_hotfixes'		=>	'hivebb_admin_extensions&section=hotfixes',
	'admin_apps'					=>	'hivebb_admin_apps',
);
