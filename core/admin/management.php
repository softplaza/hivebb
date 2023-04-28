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

defined( 'ABSPATH' ) OR die();

require FORUM_ROOT.'include/common.php';
require FORUM_ROOT.'include/common_admin.php';

$section = isset($_GET['section']) ? $_GET['section'] : null;

if ($section == 'reports')
{
	require FORUM_ROOT.'admin/reports.php';
}
else if ($section == 'prune')
{
	require FORUM_ROOT.'admin/prune.php';
}
else if ($section == 'reindex')
{
	require FORUM_ROOT.'admin/reindex.php';
}
else if ($section == 'censoring')
{
	require FORUM_ROOT.'admin/censoring.php';
}
else
{
	require FORUM_ROOT.'admin/reports.php';
}