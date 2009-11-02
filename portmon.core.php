<?php
/*      Copyright (c) 2009 Brian "devicenull" Rak
*       Permission is hereby granted, free of charge, to any person obtaining a copy
*       of this software and associated documentation files (the "Software"), to deal
*       in the Software without restriction, including without limitation the rights
*       to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
*       copies of the Software, and to permit persons to whom the Software is
*       furnished to do so, subject to the following conditions:
*
*       The above copyright notice and this permission notice shall be included in
*       all copies or substantial portions of the Software.
*
*       THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
*       IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
*       FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
*       AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
*       LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
*       OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
*       THE SOFTWARE.
*/

	class PortMon_LogParser
	{
		/*
		Example lines:
		*** TCP/UDP traffic log, generated Sun Nov  1 14:34:12 2009

		TCP/80: 1512733 packets, 1600176723 bytes total, 21335.69 kbits/s; 452011 packets, 19089447 bytes incoming, 254.52 kbits/s; 1060722 packets, 1581087276 bytes outgoing, 21081.16 kbits/s
		*/

		function ParseLine($line)
		{
			$m = preg_match("/(TCP|UDP)\/(\d+): (\d+) packets, ([0-9.]+) bytes total, ([0-9.]+) kbits\/s; (\d+) packets, ([0-9.]+) bytes incoming, ([0-9.]+) kbits\/s; (\d+) packets, ([0-9.]+) bytes outgoing, ([0-9.]+) kbits\/s/",$line,$match);
			if ($m > 0)
			{
				return array(	"port_type" => $match[1]
						,"port_num" => $match[2]
						,"total_packets" => $match[3]
						,"total_bytes" => $match[4]
						,"total_speed" => $match[5]
						,"incoming_packets" => $match[6]
						,"incoming_bytes" => $match[7]
						,"incoming_speed" => $match[8]
						,"outgoing_packets" => $match[9]
						,"outgoing_bytes" => $match[10]
						,"outgoing_speed" => $match[11]
						);
			}
			return NULL;	
		}
	}

	class PortMon_InfoHandler
	{
		/* 
		*	This will handle the results from the function above.  If they match any of the 
		*	configured action levels, the specified action will be taken
		*/

		function __construct($config)
		{
			$this->config = $config;
			if (count($config['inc_speed_events']) == 0)
			{
				echo 'No incoming speed events configured!';
				exit(-2);
			}
		}

		function Handle($result)
		{
			$wl_name = strtolower($result['port_type'])."_whitelist";
			if (array_search($result['port_num'],$this->config[$wl_name]) !== FALSE)
				return;

			$inc_speed = (float)$result['incoming_speed'];

			foreach ($this->config['inc_speed_events'] as $cur)
			{
				if ($inc_speed > $cur['speed'])
				{
					$this->_DoAction($result,$cur);

				}
			}
		}

		function _DoAction($result,$event)
		{
			switch ($event['action'])
			{
				case 'EXEC':
				{
					$cmd_string = $event['args'];
					$cmd_string = str_replace(array('{IP}','{PORT}','{PORTTYPE}')
						,array($this->config['ip'],$result['port_num'],$result['port_type'])
						,$cmd_string);
					echo 'Executing '.$cmd_string."\n";
					exec($cmd_string);
					break;
				}
			}
		}
	}
