<?php
/**
 * @copyright (C) 2020 SwiftProjectManager.Com
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package AppsManager
 */
namespace HiveBB;

use \HiveBB\ForumCore;
use \HiveBB\DBLayer;

class AppsManager
{
	var $apps_info = [];
	var $apps_path;

	// Array of all uploaded Apps
	var $apps_uploaded = [];
	// Array of all installed Apps
	var $apps_installed = [];
	// Array of all available Apps
	var $apps_available = [];
	// Array of all updates of Apps
	var $apps_updates = [];
	// Array of all Apps errors
	var $errors = [];

	var $installed_ids = [];

	// Start
	function __construct()
	{
		$this->apps_path = FORUM_ROOT.'apps';
	}

	// Return a list of all apps uploaded
	public function get_uploaded() {

		$apps = array();
		$d = dir($this->apps_path);

		while (($app_id = $d->read()) !== false)
		{
			if ($app_id != '.' && $app_id != '..' && is_dir($this->apps_path.'/'.$app_id))
			{
				if (file_exists($this->apps_path.'/'.$app_id.'/inc/manifest.php'))
				{
					$app_info = include $this->apps_path.'/'.$app_id.'/inc/manifest.php';
					
					if ($this->check_app_info($app_id, $app_info))
						$this->apps_uploaded[$app_id] = $app_info;
					
				}
				else
					$this->errors[] = 'The <strong>'.$app_id.'</strong> application is missing a main manifest.php file.';
			}
		}
		$d->close();
		
		return $apps;
	}

	// Get all installed Apps
	function get_installed() {
		$forum_db = new DBLayer;

		$query = array(
			'SELECT'	=> 'a.*',
			'FROM'		=> 'applications AS a'
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$app_ids = array();
		while ($row = $forum_db->fetch_assoc($result)) {
			$this->installed_ids[] = $row['id'];
			$this->apps_installed[$row['id']] = $row;
		}
		return $this->apps_installed;
	}

	// Get all installed Apps
	function get_available() {
		if (!empty($this->apps_uploaded)) {
			foreach($this->apps_uploaded as $id => $info) {
				if (!in_array($id, $this->installed_ids))
					$this->apps_available[$id] = $info;
			}
		}
		return $this->apps_available;
	}

	// Get available updates
	function get_updates() {
		if (!empty($this->apps_installed)) {
			foreach($this->apps_installed as $inst_id => $inst_info) {
				foreach($this->apps_uploaded as $upl_id => $upl_info) {
					if ($inst_id == $upl_id) {
						if (version_compare($inst_info['version'], $upl_info['version'], '!='))
							$this->apps_updates[$upl_id] = $upl_info;
					}
				}
			}
		}
		return $this->apps_updates;
	}

	function get_app_info($app_id) {
		if (file_exists($this->apps_path.'/'.$app_id.'/inc/manifest.php'))
			$app_info = include $this->apps_path.'/'.$app_id.'/inc/manifest.php';
	
		return isset($app_info) ? $app_info : [];
	}

	// Check if app is installed
	function is_installed($app_id = null) {
		$forum_db = new DBLayer;

		$query = array(
			'SELECT'	=> 'a.id',
			'FROM'		=> 'applications AS a',
			'WHERE'		=> 'a.id=\''.$forum_db->escape($app_id).'\''
		);
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$app = $forum_db->result($result);
	
		return isset($app['id']) ? true : false;
	}

	function add_info($app_info) {
		$this->apps_info[$app_info['id']] = $app_info;
	}

	function check_app_info($app_id, $app_info = []) {

		if (empty($app_info)) {
			$this->errors[] = 'The manifest.php of '.$app_id.' does not content App information.';
			return false;
		}
		else if (!isset($app_info['id']) || (isset($app_info['id']) && $app_id != $app_info['id']))
		{
			$this->errors[] = 'Incorrect <strong>'.$app_id.'</strong>/manifest.php. App <strong>id</strong> cannot be empty.';
			return false;
		}
		else if (!isset($app_info['title']) || $app_info['title'] == '')
		{
			$this->errors[] = 'Incorrect <strong>'.$app_id.'</strong>/manifest.php. App <strong>title</strong> cannot be empty.';
			return false;
		}
		else if (!isset($app_info['description']) || $app_info['description'] == '')
		{
			$this->errors[] = 'Incorrect <strong>'.$app_id.'</strong>/manifest.php. App <strong>description</strong> cannot be empty.';
			return false;
		}
		else if (!isset($app_info['author']) || $app_info['author'] == '')
		{
			$this->errors[] = 'Incorrect <strong>'.$app_id.'</strong>/manifest.php. App <strong>author</strong> cannot be empty.';
			return false;
		}
		else if (!isset($app_info['version']) || $app_info['version'] == '' || preg_match('/[^a-z0-9\- \.]+/i', $app_info['version']))
		{
			$this->errors[] = 'Incorrect <strong>'.$app_id.'</strong>/manifest.php. Wrong App <strong>version</strong>.';
			return false;
		}
		else
			return true;
	}

}
