<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelBlockrules extends F0FModel
{
	/**
	 * Checks if the current user is blocked, i.e. he's not allowed to subscribe
	 * to this site.
	 *
	 * @param   stdObject  $state  The state of the subscriptions model
	 *
	 * @return  boolean  True if the user is blocked
	 */
	public function isBlocked($state)
	{
		$this->getItemList();

		// Get block rules
		$this->enabled(1);
		$blockrules = $this->getItemList(true);

		if (!count($blockrules))
		{
			return false;
		}

		$this->workaroundIPIssues();

		foreach ($blockrules as $rule)
		{
			$hit = false;
			$match = true;

			if ($rule->username)
			{
				$pattern = strtolower($rule->username);
				$string = strtolower($state->username);
				$hit = true;
				$match = $match && fnmatch($pattern, $string);
			}

			if ($rule->name)
			{
				$pattern = strtolower($rule->name);
				$string = strtolower($state->name);
				$hit = true;
				$match = $match && fnmatch($pattern, $string);
			}

			if ($rule->email)
			{
				$pattern = strtolower($rule->email);
				$string = strtolower($state->email);
				$hit = true;
				$match = $match && (strripos($state->email, $rule->email) === 0);
			}

			if ($rule->iprange)
			{
				$pattern = strtolower($rule->iprange);
				$hit = true;
				$match = $match && $this->isIPInRange($pattern);
			}

			if ($hit && $match)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets the visitor's IP address. Automatically handles reverse proxies
	 * reporting the IPs of intermediate devices, like load balancers. Examples:
	 * https://www.akeebabackup.com/support/admin-tools/13743-double-ip-adresses-in-security-exception-log-warnings.html
	 * http://stackoverflow.com/questions/2422395/why-is-request-envremote-addr-returning-two-ips
	 * The solution used is assuming that the last IP address is the external one.
	 *
	 * @return  string
	 */
	private function getUserIP()
	{
		$ip = $this->_real_getUserIP();

		if( (strstr($ip, ',') !== false) || (strstr($ip, ' ') !== false) ) {
			$ip = str_replace(' ', ',', $ip);
			$ip = str_replace(',,', ',', $ip);
			$ips = explode(',', $ip);
			$ip = '';
			while(empty($ip) && !empty($ips)) {
				$ip = array_pop($ips);
				$ip = trim($ip);
			}
		} else {
			$ip = trim($ip);
		}

		return $ip;
	}

	/**
	 * Gets the visitor's IP address
	 *
	 * @return  string
	 */
	private function _real_getUserIP()
	{
		// Normally the $_SERVER superglobal is set
		if(isset($_SERVER)) {
			// Do we have an x-forwarded-for HTTP header (e.g. NginX)?
			if(array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
				return $_SERVER['HTTP_X_FORWARDED_FOR'];
			}

			// Do we have a client-ip header (e.g. non-transparent proxy)?
			if(array_key_exists('HTTP_CLIENT_IP', $_SERVER)) {
				return $_SERVER['HTTP_CLIENT_IP'];
			}

			// Normal, non-proxied server or server behind a transparent proxy
			return $_SERVER['REMOTE_ADDR'];
		}

		// This part is executed on PHP running as CGI, or on SAPIs which do
		// not set the $_SERVER superglobal

		// If getenv() is disabled, you're screwed
		if(!function_exists('getenv')) {
			return '';
		}

		// Do we have an x-forwarded-for HTTP header?
		if (getenv('HTTP_X_FORWARDED_FOR')) {
			return getenv('HTTP_X_FORWARDED_FOR');
		}

		// Do we have a client-ip header?
		if (getenv('HTTP_CLIENT_IP')) {
			return getenv('HTTP_CLIENT_IP');
		}

		// Normal, non-proxied server or server behind a transparent proxy
		if (getenv('REMOTE_ADDR')) {
			return getenv('REMOTE_ADDR');
		}

		// Catch-all case for broken servers, apparently
		return '';
	}

	/**
	 * Works around the REMOTE_ADDR not containing the user's IP
	 */
	private function workaroundIPIssues()
	{
		$ip = $this->getUserIP();
		if($_SERVER['REMOTE_ADDR'] == $ip) return;

		if(array_key_exists('REMOTE_ADDR', $_SERVER)) {
			$_SERVER['ADMINTOOLS_REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
		} elseif(function_exists('getenv')) {
			if (getenv('REMOTE_ADDR')) {
				$_SERVER['ADMINTOOLS_REMOTE_ADDR'] = getenv('REMOTE_ADDR');
			}
		}

		$_SERVER['REMOTE_ADDR'] = $ip;
	}

	/**
	 * Checks if the user's IP is contained in an IP expression
	 *
	 * @param   array  $ipTable  The list of IP expressions
	 *
	 * @return  null|boolean  True if it's in the list, null if the filtering can't proceed
	 */
	private function isIPInRange($ipExpression)
	{
		// Sanity check
		if(!function_exists('ip2long')) return false;

		// Get our IP address
		$ip = array_key_exists('REMOTE_ADDR', $_SERVER) ? htmlspecialchars($_SERVER['REMOTE_ADDR']) : '0.0.0.0';
		if (strpos($ip, '::') === 0) {
			$ip = substr($ip, strrpos($ip, ':')+1);
		}
		// No point continuing if we can't get an address, right?
		if(empty($ip)) return false;
		$myIP = ip2long($ip);


		if(empty($ipTable)) return false;

		$ipExpression = trim($ipExpression);
		if(strstr($ipExpression, '-'))
		{
			// Inclusive IP range, i.e. 123.123.123.123-124.125.126.127
			list($from,$to) = explode('-', $ipExpression, 2);
			$from = ip2long(trim($from));
			$to = ip2long(trim($to));
			// Swap from/to if they're in the wrong order
			if($from > $to) list($from, $to) = array($to, $from);
			if( ($myIP >= $from) && ($myIP <= $to) ) return true;
		}
		elseif(strstr($ipExpression, '/'))
		{
			// Netmask or CIDR provided, i.e. 123.123.123.123/255.255.255.0
			// or 123.123.123.123/24
			list($ip, $netmask) = explode('/',$ipExpression,2);
			$ip = ip2long(trim($ip));
			$netmask = trim($netmask);

			if(strstr($netmask,'.'))
			{
				// Convert netmask to CIDR
				$long = ip2long($netmask);
				$base = ip2long('255.255.255.255');
				$netmask = 32 - log(($long ^ $base)+1,2);
			}

			// Compare the IP to the masked IP
			$ip_binary_string = sprintf("%032b",$myIP);
			$net_binary_string = sprintf("%032b",$ip);
			if( substr_compare($ip_binary_string,$net_binary_string,0,$netmask) === 0 )
			{
				return true;
			}
		}
		else
		{
			// Standard IP address, i.e. 123.123.123.123 or partial IP address, i.e. 123.[123.][123.][123]
			$dots = 0;
			if(substr($ipExpression, -1) == '.') {
				// Partial IP address. Convert to CIDR and re-match
				foreach(count_chars($ipExpression,1) as $i => $val) {
					if($i == 46) $dots = $val;
				}
				switch($dots) {
					case 1:
						$netmask = '255.0.0.0';
						$ipExpression .= '0.0.0';
						break;

					case 2:
						$netmask = '255.255.0.0';
						$ipExpression .= '0.0';
						break;

					case 3:
						$netmask = '255.255.255.0';
						$ipExpression .= '0';
						break;

					default:
						$dots = 0;
				}

				if($dots) {
					// Convert netmask to CIDR
					$long = ip2long($netmask);
					$base = ip2long('255.255.255.255');
					$netmask = 32 - log(($long ^ $base)+1,2);

					// Compare the IP to the masked IP
					$ip_binary_string = sprintf("%032b",$myIP);
					$net_binary_string = sprintf("%032b",ip2long(trim($ipExpression)));
					if( substr_compare($ip_binary_string,$net_binary_string,0,$netmask) === 0 )
					{
						return true;
					}
				}
			}
			if(!$dots) {
				$ip = ip2long(trim($ipExpression));
				if($ip == $myIP) return true;
			}
		}

		return false;
	}
}