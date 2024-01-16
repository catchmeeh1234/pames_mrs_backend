<?php
	//$serverName = "192.168.10.17\\sqlexpress, 1433"; //serverName\instanceName, portNumber (default is 1433)
	$serverName = "localhost\\sqlexpress, 1433"; //serverName\instanceName, portNumber (default is 1433)

	$connectionInfo = array( 'Database'=>'ePRMS_test', 'UID'=>'sa', 'PWD'=>'p@$$w0rd', 'CharacterSet' => 'UTF-8', 'TrustServerCertificate' => 'True');
	$conn = sqlsrv_connect( $serverName, $connectionInfo);

	if( !$conn ) 
	{
	     echo "Connection could not be established.<br />";
	     die( print_r( sqlsrv_errors(), true));
	}