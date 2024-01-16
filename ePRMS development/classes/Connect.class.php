<?php 
    header('Access-Control-Allow-Origin: *');

    //phpinfo();
    //print_r(PDO::getAvailableDrivers());

    class Connect {
        //private $dsn = "sqlsrv:Server=192.168.10.17;Database=edms;TrustServerCertificate=true";
        private $dsn = "sqlsrv:Server=localhost;Database=ePRMS_test;TrustServerCertificate=true";

        private $user = "sa";
        private $password = 'p@$$w0rd';
    
        private $options = array(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC);
        protected $conn;

        public function __construct() {}
    
        public function openConnection() {
            try {
                $this->conn = new PDO($this->dsn, $this->user, $this->password, $this->options);
                return $this->conn;
            } catch (PDOException $e) {
                echo "error connecting to database: " . $e->getMessage();
            }
        }
    
        public function closeConnection() {
            $this->conn = null;
        }
    }

    // $db = new Database();
    // echo $db->openConnection();


    // $host = "192.168.10.17";
    // $user = "sa";
    // $password = 'p@$$w0rd';
    // $dbname = "edms";

    // $dsn = "sqlsrv:Server=$host;Database=$dbname;TrustServerCertificate=true";

    // try {
    //     $conn = new PDO($dsn, $user, $password);
    //     //$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // } catch(PDOException $e) {
    //     echo "Connection failed: " . $e->getMessage();
    // }


    //$serverName = "192.168.10.17\\sqlexpress, 1433"; //serverName\instanceName, portNumber (default is 1433)
    // $serverName = "localhost\\sqlexpress, 1433"; //serverName\instanceName, portNumber (default is 1433)
    // $connectionInfo = array( 'Database'=>'ebacstest', 'UID'=>'sa', 'PWD'=>'p@$$w0rd', 'CharacterSet' => 'UTF-8', 'TrustServerCertificate'=>'True');
    // $conn = sqlsrv_connect( $serverName, $connectionInfo);

    // if( !$conn )
    // {
    //      echo "Connection could not be established.<br />";
    //      die( print_r( sqlsrv_errors(), true));
    // }

    // else
    // {
    //     echo phpinfo();
    //     //echo "Connected";
    // }
 ?>