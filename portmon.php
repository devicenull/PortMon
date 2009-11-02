<?php
/* 	Copyright (c) 2009 Brian "devicenull" Rak
*	Permission is hereby granted, free of charge, to any person obtaining a copy
*	of this software and associated documentation files (the "Software"), to deal
*	in the Software without restriction, including without limitation the rights
*	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
*	copies of the Software, and to permit persons to whom the Software is
*	furnished to do so, subject to the following conditions:
*
*	The above copyright notice and this permission notice shall be included in
*	all copies or substantial portions of the Software.
*
*	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
*	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
*	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
*	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
*	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
*	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
*	THE SOFTWARE.
*/
	/*
	*	Portmon - watches the log output from iptraf and firewalls off any ports that exceed
	*	a certain bandwidth level
	*
	*	Return codes:
	*	0	Normal
	*	-1 	IPtraf log file hasn't changed in size for a significant period of time
	*	-2	No incoming speed levels are defined, check the config file
	*/
	require_once("config.php");
	require_once("portmon.core.php");

	define("ONE_HOUR",3600);

	$log_position = 0;
	if (file_exists($config['data_file']))
	{
		$cached_data = unserialize(file_get_contents($config['data_file']));
	}

	if (!isset($cached_data) || count($cached_data) == 0)
	{
		echo "No cached data found, using defaults\n";
		$cached_data['logpos'] = 0;
		$cached_data['lastupdate'] = time()-ONE_HOUR;
	}
	$log_position = $cached_data['logpos'];

	if (filesize($config['iptraf_log']) < $log_position)
	{
		$log_position = 0;
		echo "iptraf log file seems to have shrunk in size, assuming it was reset\n";
	}

	if (filesize($config['iptraf_log']) == $log_position)
	{
		if (time()-$cached_data['lastupdate'] > ONE_HOUR)
		{
			open_ticket($config,'iptraf log file hasnt been updated in over an hour.  Has it died?');
			exit(-1);
		}
		exit(0);
	}

	if ($log_position == 0)
	{
		echo "Empty log position, resetting to END of current log file.  No events handled this run.\n";
		$cached_data['logpos'] = $log_position = filesize($config['iptraf_log']);
	}

	$log = fopen($config['iptraf_log'],"r");
	fseek($log,$log_position);

	$log_parser = new PortMon_LogParser();
	$info_handler = new PortMon_InfoHandler($config);
	$valid_lines = 0;
	while (!feof($log))
	{
		$line = trim(fgets($log));
		$result = $log_parser->ParseLine($line);
		if ($result != NULL)
		{
			$info_handler->Handle($result);
			$valid_lines += 1;
		}
	}
	
	if ($valid_lines > 0)
	{
		$cached_data['logpos'] = ftell($log);
		$cached_data['lastupdate'] = time();
	}
	file_put_contents($config['data_file'],serialize($cached_data));

