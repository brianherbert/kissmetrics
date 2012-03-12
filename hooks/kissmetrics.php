<?php defined('SYSPATH') or die('No direct script access.');
/**
 * KISSmetrics events
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   Ushahidi Team <team@ushahidi.com>
 * @package	   Ushahidi - http://source.ushahididev.com
 * @module     KISSMetrics
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license	   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */

class kissmetrics {

	/**
	 * Registers the main event add method
	 */
	public function __construct()
	{
		// Hook into routing
		Event::add('system.pre_controller', array($this, 'add'));

		KM::init(Kohana::config("kissmetrics.api_key"), array(
			'log_dir' => Kohana::config("kissmetrics.log_dir"),
			'use_cron' => true,
			'to_stderr' => true
			));
	}

	/**
	 * Adds all the events to the main Ushahidi application
	 */
	public function add()
	{
		// MHI Specific Events

		Event::add('ushahidi_action.mhi_deployment_created', array($this, '_mhi_deployment_created'));
		Event::add('ushahidi_action.mhi_user_created', array($this, '_mhi_user_created'));
		Event::add('ushahidi_action.mhi_user_email_change', array($this, '_mhi_user_email_change'));

		// Platform Events

		Event::add('ushahidi_action.report_add', array($this, '_report_add'));
		Event::add('ushahidi_action.checkin_recorded', array($this, '_checkin_recorded'));
		Event::add('ushahidi_action.user_login', array($this, '_user_login'));
		Event::add('ushahidi_action.user_logout', array($this, '_user_logout'));
		Event::add('ushahidi_action.site_settings_modified', array($this, '_site_settings_modified'));
		Event::add('ushahidi_action.map_settings_modified', array($this, '_map_settings_modified'));
		Event::add('ushahidi_action.header_scripts_admin', array($this, '_header_scripts_admin'));
		Event::add('ushahidi_action.header_scripts_member', array($this, '_header_scripts_member'));

		// Platform Filters

		Event::add('ushahidi_filter.header_js', array($this, '_modify_header_js'));
	}

	// MHI USER CHANGED EMAIL
	public function _mhi_user_email_change()
	{
		// Let KISSmetrics know that this person now has a new email address
		$emails = Event::$data;
		KM::identify($this->identify());
		KM::alias($this->identify(), $emails['new_email']);
	}

	// MHI DEPLOYMENT CREATED
	public function _mhi_deployment_created()
	{
		$deployment_name = Event::$data;
		KM::identify($this->identify());
		KM::record('Crowdmap Deployment Created', array('Deployment Subdomain' => $deployment_name));
	}

	// MHI USER CREATED
	public function _mhi_user_created()
	{
		$email = Event::$data;
		KM::identify($this->identify());
		KM::alias($this->identify(), $email);
		KM::record('Crowdmap User Created', array('User Email' => $email));
	}

	// HEADER JAVASCRIPT FOR FRONT END
	public function _modify_header_js()
	{
		$header_js = Event::$data;

		if( isset($_SESSION['auth_user']) )
		{
			$user = new User_Model($_SESSION['auth_user']->id);
			$kissmetrics_identify = "
				<script type=\"text/javascript\">
	            	_kmq.push(['identify', '".$user->email."']);
	            </script>
	            ";
			$header_js .= $kissmetrics_identify;
		}

		Event::$data = $header_js;
	}

	// HEADER SCRIPTS ADMIN PANEL
	public function _header_scripts_admin()
	{
		$user = new User_Model($_SESSION['auth_user']->id);
		echo "
			<script type=\"text/javascript\">
            	_kmq.push(['identify', '".$user->email."']);
            </script>
            ";
	}

	// HEADER SCRIPTS MEMBER PANEL
	public function _header_scripts_member()
	{
		$user = new User_Model($_SESSION['auth_user']->id);
		echo "
			<script type=\"text/javascript\">
            	_kmq.push(['identify', '".$user->email."']);
            </script>
            ";
	}

	// SITE SETTINGS MODIFIED
	public function _site_settings_modified()
	{
		// Let KISSmetrics know that this person started using their account
		KM::identify($this->identify());
		KM::record('Activated');
	}

	// MAP SETTINGS MODIFIED
	public function _map_settings_modified()
	{
		// Let KISSmetrics know that this person started using their account
		KM::identify($this->identify());
		KM::record('Activated');
	}

	// REPORT ADDED
	public function _report_add()
	{
		KM::identify($this->identify());
		KM::record('Deployment Report Added');
	}

	// CHECKIN RECORDED
	public function _checkin_recorded()
	{
		KM::identify($this->identify());
		KM::record('Deployment Checkin Recorded');
	}

	// USER LOGIN
	public function _user_login()
	{
		// Identify this user by their email address
		$user = Event::$data;
		KM::identify($this->identify());
		KM::alias($this->identify(), $user->email);
	}

	// USER LOGOUT
	public function _user_logout()
	{
		// Identify this user by their email address
		$user = Event::$data;
		KM::identify(NULL);
	}

	// This returns the identify information for the current user
	public function identify()
	{
		if ( isset($_COOKIE['km_ai']) )
		{
			return $_COOKIE['km_ai'];
		}else{
			// If it's not already set, then create some crazy random string
			$identity = md5(mt_rand(1,mt_getrandmax()));

			return $identity;
		}
	}
}

new kissmetrics;