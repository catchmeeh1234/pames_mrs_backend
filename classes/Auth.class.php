<?php
    require __DIR__ . '/../vendor/autoload.php';

    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;


    class Auth {
        public function __construct() {}

        public $secretKey = "Pames2024";


        public function getJWT($username) {
            // Generate a JWT
            $tokenPayload = array(
                'appname' => 'PAMES',
                'username' => $username,
                "exp" => time() + 604800,
                // You can include additional claims as needed
            );
        
            $jwt = JWT::encode($tokenPayload, $this->secretKey, 'HS256');
        
            return $jwt;
        }

        public function decodeJWT($token) {
            try {
                $decoded = JWT:: decode ($token, new Key ($this->secretKey, 'HS256'));
                return $decoded;
            } catch (\Throwable $th) {
                return false;
            }
        }

        public function validateJWT($httpAuthorization) {

            try {
                //extract JWT from http request header
                $jwt = $this->extractJWT($httpAuthorization);
                
                if (!$jwt) {
                    return http_response_code(401);
                }
                
                // Decode the JWT
                $decoded = $this->decodeJWT($jwt);
                
                if (!$decoded) {
                    return http_response_code(401);
                }

                if ($decoded->appname <> "PAMES" || !$decoded->username) {
                    return http_response_code(401);
                }
                
                return true;
                
            } catch (\Exception $e) {
                return http_response_code(401);
            }
        }

        public function extractJWT($httpAuthorization) {
            if (!isset($httpAuthorization)) {
                die('Authorization header is missing');
                //return http_response_code(401);
            }
            
            // Get the value of the Authorization header
            $authorizationHeader = $httpAuthorization;
            
            // Check if the Authorization header starts with "Bearer "
            if (substr($authorizationHeader, 0, 7) !== 'Bearer ') {
                die('Invalid Authorization header format');
                //return http_response_code(401);
            }
            
            // Extract the JWT from the Authorization header
            $jwt = substr($authorizationHeader, 7);
            return $jwt;
            // Now $jwt contains the extracted JWT
            
            // You can then proceed with JWT validation or any other necessary processing
            // For example, using the firebase/php-jwt library as shown in previous responses
        }
    }