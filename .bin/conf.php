<?php 
	function vd($data) 
	{
		var_dump($data);die();
	}

	// $log_file = './log.log';
	// if(!defined('log'))
	// {
	// 	function log($data) 
	// 	{
	// 		if(empty($data))
	// 			return;
	// 		if(is_array($data))
	// 			$data = print_r($data);
	// 		$file = fopen($log_file, "a");
	// 		fwrite($file, $data . "\n");
	// 	}
	// }

	$server = @$argv[1];
	if(is_null($server)) 
		throw new \Exception("Server must be passed as parameter");

	$conf_name = ($server == 'apache')? 'apache_t.conf' : 'nginx_t.conf';
	$server_name = ($server == 'apache')? 'apache2' : 'nginx';

	$conf_file = require "site.conf.php";
	$root = dirname(__DIR__ . '../');
	$conf_file['ROOT'] = $root;

	$conf_template =  file_get_contents($conf_name);

	$conf_file_name = 'default.conf';

	$conf_file['ERROR_LOG'] = $conf_file['ERROR_LOG'] . $server_name . DIRECTORY_SEPARATOR . $conf_file['URL'] . '.log';
	if(!file_exists($conf_file['ERROR_LOG'] . $server_name . DIRECTORY_SEPARATOR . $conf_file['URL'] . '.log'))
	{
		$log_file = $conf_file['ERROR_LOG'];
		shell_exec("touch $log_file");
	}
	
	foreach($conf_file as $key=>$value) 
	{
		if($key == 'URL')
			$conf_file_name = $value . '.conf';
		$conf_template = str_replace('%%' . $key . '%%', $value, $conf_template);
	}

	# Clear unconfigured
	$conf_template = preg_replace("/%%(\w+)%%/i", "", $conf_template);

	# Setup Symlink WEB_DIR -> APP_DIR
	if(!is_dir($conf_file['WEB_DIR'])) 
		throw new Error('Change the WEB_DIR that referenced on web server web dir');

	$public_url = realpath(__DIR__ . '/../public');
	
	if(!$public_url)
		throw new error('Public url find failed');

	$link_url = $conf_file['WEB_DIR'].'/'.$conf_file['URL'];
	if(!file_exists($link_url)) 
		shell_exec("ln -s $public_url $link_url");
	$conf_file['WEB_DIR'] = $link_url;
	
	$server_dir = '';
	if($server == 'apache') 
	{
		$sa_path = '';

		# Ubuntu/Debian
		if(is_dir('/etc/apache2/sites-available')) 
		{
			$sa_path = '/etc/apache2/sites-available';
		}
		if(!empty($sa_path) && !is_file(realpath($sa_path) . DIRECTORY_SEPARATOR . $conf_file_name)) 
		{
			# Write conf file to server path /sites-available
			$out = fopen(realpath($sa_path) . DIRECTORY_SEPARATOR . $conf_file_name, "w") or die('Cannot open file');
			$success = fwrite($out, $conf_template);

			if($success) 
			{
				$se_path = '/etc/apache2/sites-enabled';
				// symlink to sites-enabled
				chdir(realpath($se_path));
				$file = realpath($sa_path) . DIRECTORY_SEPARATOR . $conf_file_name;
				if(!is_file($se_path . DIRECTORY_SEPARATOR . $conf_file_name))
					shell_exec("ln -s /$file /etc/apache2/sites-enabled");
			}
			# trying to restart Apache
			shell_exec('/bin/systemctl restart apache2.service');
		}
	}


	# Check for content of /etc/hosts
	$host_file_path = '/etc/hosts';
	$app_url = $conf_file['URL'];
	if(!is_file($host_file_path)) 
	{
		shell_exec("echo $app_url >> $host_file_path");
		shell_exec("echo www.$app_url >> $host_file_path");
	}else {
		$contents = file_get_contents($host_file_path);
		if(strpos($app_url, $contents) == false) {
			shell_exec("echo 127.0.0.1 www.$app_url >> $host_file_path");
			shell_exec("echo 127.0.0.1 $app_url >> $host_file_path");
		}
	}

	echo "done";
?>