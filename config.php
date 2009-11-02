<?
	$config = array(
		// IP of the interface being monitored
		'ip' => 'example.com'

		// This is the internal data file used to keep track of various things.
		// No special requirements, other then it must be writable by the user running this
		,'data_file' => '/root/portmon/data.dat'

		// Location of the iptraf log file.  iptraf should be started like: iptraf -s eth0 -B
		,'iptraf_log' => '/var/log/iptraf/tcp_udp_services-eth0.log'


		// UDP/TCP whitelists.  Any ports on these will have no action taken even if they exceed the limit
		,'udp_whitelist' => array(22,53)
		,'tcp_whitelist' => array(22,80)

		,'inc_speed_events' => array(
			array('speed'=>1000.0,'action'=>'EXEC','args'=>'echo {IP}:{PORT} has exceeded 1000.0kb/s')
			,array('speed'=>5.0,'action'=>'EXEC','args'=>'echo {IP}:{PORT} has exeeded 5.0kb/s')
			)
		);

		function open_ticket($config,$message)
		{
			echo $message;
		}
