<?php

    class User extends Connect {
        private $username;
        private $password;

        public function __construct()
        {
            
        }

        public function loadNotificationsCounter($userid) {
            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT notification_counter FROM UserAccounts WHERE id = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$userid]);
            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 1) {
                foreach ($result as $row) {
                    return $row["notification_counter"];
                }
            }

        }

        public function login($username, $password) {
            $this->username = $username;
            $this->password = $password;

            //hash password
            $hashedPassword = md5($password);

            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * FROM UserAccounts WHERE Username = ? AND Password = ?";
            $rowAuthLogin = $connection->prepare($sql);
            $rowAuthLogin->execute([$this->username, $hashedPassword]);
            $users = $rowAuthLogin->fetchAll();

            $count = $rowAuthLogin->rowCount();
            if ($count == 0) {
                $arrayAuthLogin = array('status' => 'Invalid Credentials');
                return json_encode($arrayAuthLogin);
        
            } else {
                session_start();
                $_SESSION['username'] = $this->username;
        
                foreach ($users as $user) {
                    $_SESSION['id'] = $user['id'];
                    $_SESSION['username'] = $user['Username'];
                    $_SESSION['division'] = $user['Division'];
                    $_SESSION['access'] = $user['Access'];
                    $_SESSION['fullname'] = $user['FullName'];
                }
        
                $arrayAuthLogin = array('uid' => $_SESSION['id'], 'username' =>  $_SESSION['username'], 'division' =>  $_SESSION['division'], 'access' =>  $_SESSION['access'], 'fullname' =>  $_SESSION['fullname'], 'status' => 'Login Success');
                
                return json_encode($arrayAuthLogin);
            }
        } 

        public function fetchOneUserAccount($userid) {
            $connection = $this->openConnection();

            //pdo query
            $sql = "SELECT * FROM UserAccounts WHERE id = ?";
            $result = $connection->prepare($sql);
            $result->execute([$userid]);
            $rows = $result->fetchAll();
            $count = count($rows);

            if ($count == 0) {
                $json = array('status' => 'No user account found');
                //return json_encode($json);
                return $json;
            } else {
                return $rows;
            }
        }

        public function editUserAccount($userAccountDetails) {
            $connection = $this->openConnection();

            $obj = json_decode($userAccountDetails, true);
            $id = $obj['id'];
            $username = $obj['Username'];
            $fullname = $obj['FullName'];
            $division = $obj['Division'];
            $designation = $obj['Designation'];
            $email = $obj['email'];
            //$password = $obj['Password'];

            //hash password
            // $hashedPassword = md5($password);

            $originalUsername = $obj['originalUsername'];

            if ($originalUsername == $username) {
                $allow = true;
            } else {
                $allow = false;
            }

            // $empid = "22-084";
            // $username = "juan";
            // $fullname = "juan";
            // $email = "juan@gmail.com";
            // $division = "Administrative Services";
            // $designation = "Data Encoder";
            // $access = "Encoder";

            //check if username already exists
            $qryUser = "SELECT * FROM UserAccounts WHERE Username = :username1";
            $stmtUser = $connection->prepare($qryUser);
            $stmtUser->execute(array(':username1' => $username));
            $rowsUser = $stmtUser->fetchAll();
            $countUser = $stmtUser->rowCount();

            if ($countUser >= 1 && !$allow) {
                return $arrayMessage = array('status' => 'Username already taken');
            } else {
                $sql = "UPDATE UserAccounts SET Username = :username, FullName = :fullname, Division = :division, Designation = :designation, email = :email WHERE id = :id";
                $stmt = $connection->prepare($sql);
                $stmt->execute(array(':username' => $username, ':fullname' => $fullname, ':division' => $division, ':designation' => $designation, ':email' => $email, ':id' => $id));
                $count = $stmt->rowCount();
                
                if ($count != 1) {
                    //print_r($stmt->errorInfo());
                    return $arrayMessage = array('status' => 'Account update failed');
                    //exit("Failed1");
                } else {
                    return $arrayMessage = array('status' => 'Account updated successfully');
                }
            }
        }

        public function fetchAllUserAccounts() {
            $connection = $this->openConnection();

            //pdo query
            $sql = "SELECT * FROM UserAccounts";
            $result = $connection->query($sql);
            $rows = $result->fetchAll();
            $count = count($rows);

            if ($count == 0) {
                $json = array('status' => 'No user accounts found');
                //return json_encode($json);
                return $json;
            } else {
                return $rows;
            }
        }

        public function fetchAccess() {
            $connection = $this->openConnection();

            //pdo query
            $sql = "SELECT * FROM Access";
            $result = $connection->query($sql);
            $rows = $result->fetchAll();
            $count = count($rows);

            if ($count == 0) {
                $json = array('status' => 'No access found');
                //return json_encode($json);
                return $json;
            } else {
                return $rows;
            }
        }

        public function resetUserPassword($userid) {
            $connection = $this->openConnection();

            $hashedPassword = md5('123456');

            $sql = "UPDATE UserAccounts SET Password = :password WHERE id = :id";
            $stmt = $connection->prepare($sql);
            $stmt->execute(array(':password' => $hashedPassword, ':id' => $userid));
            $count = $stmt->rowCount();

            if ($count == 1) {
                return array('status' => "success");
            } else {
                return array('status' => "failed");
            }
        }

        public function changeUserPassword($userAccountDetails, $id) {
            $connection = $this->openConnection();

            $obj = json_decode($userAccountDetails, true);
            $currentPassword = $obj['currentpassword'];
            $newPassword = $obj['newpassword'];
            $hashedNewPassword = md5($newPassword);
            //$confirmNewPassword = $obj['confirmnewpassword']; 

            //check if current password is correct
            $qry = "SELECT * FROM UserAccounts WHERE id = $id";
            $result = $connection->query($qry);
            $rows = $result->fetchAll();
            $rowsCount = $result->rowCount();
            if ($rowsCount == 1) {
                //user is in the database
                foreach ($rows as $row) {
                    $hashedPassword = $row["Password"];
                    $userHashedPassword = md5($currentPassword);
                    if ($hashedPassword == $userHashedPassword) {
                        //update user password
                        $sql = "UPDATE UserAccounts SET Password = :password WHERE id = :id";
                        $stmt = $connection->prepare($sql);
                        $stmt->execute(array(':password' => $hashedNewPassword, ':id' => $id));
                        $count = $stmt->rowCount();

                        if ($count == 1) {
                            return array('status' => "success");
                        } else {
                            return array('status' => "failed");
                        }

                    } else {
                        return array('status' => "wrong password");
                    }
                }
            } else {
                return array('status' => "failed");
            }        
        }

        public function addUserAccount($userAccountDetails) {
            $obj = json_decode($userAccountDetails, true);
            $empId = $obj['Emp_ID'];
            $username = $obj['Username'];
            $password = $obj['Password'];
            $fullname = $obj['FullName'];
            $division = $obj['Division'];
            $designation = $obj['Designation'];
            $access = $obj['Access'];
            $email = $obj['email'];

            $connection = $this->openConnection();
            //insert user to database
            $sql = "INSERT INTO UserAccounts (Emp_ID, Username, Password, FullName, Division, Designation, Access, email) 
                    VALUES (:empId, :username, :password, :fullname, :division, :designation, :access, :email)";
            $stmt = $connection->prepare($sql);
            $stmt->execute(array(':empId' => $empId, ':username' => $username, ':password' => $password, ':fullname' => $fullname, ':division' => $division, ':designation' => $designation, ':access' => $access, ':email' => $email));

            $count = $stmt->rowCount();
            //echo $count2;
            if($count == 1) {
                return array('status' => 'success');
            } else {
                return array('status' => 'failed');
                //die(print_r($stmt2->errorInfo()));
            }

        }
    }

  