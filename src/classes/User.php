<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of user
 *
 * @author mc
 */
class User {
    
    private $id = -1,
            $username = "",
            $hashedPassword = "",
            $email = "";
    
    function setUsername($username) {
        $this->username = $username;
    }

    function setHashedPassword($newPassword) {
        $newHashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->hashedPassword = $newHashedPassword;
    }
    
    
    function setEmail($email) {
        $this->email = $email;
    }

    function getId() {
        return $this->id;
    }

    function getUsername() {
        return $this->username;
    }

    function getHashedPassword() {
        return $this->hashedPassword;
    }

    function getEmail() {
        return $this->email;
    }

    public function saveToDb(){
        $pdo = Db::getInstance();

        
        if($this->id == -1){
            $pdo->query()




            $sql = "insert into users (username, mail, hashed_password) values (?, ?, ?)";
            $preparedQuery = $db->prepare($sql);
            $insertArray = array($this->getUsername(), $this->getEmail(), $this->getHashedPassword());
            for($i=1;$i <= 3; $i++ ){
                $preparedQuery->bindValue($i, $insertArray[$i-1]);                
            }           
            if($preparedQuery->execute()){
                $this->id = $db->lastInsertId();
                return true;
            }
        } else {
            $sql = "update users set username = ?, mail = ?, hashed_password = ? where id = ".$this->getId();
            $preparedQuery = $db->prepare($sql);
            $insertArray = array($this->getUsername(), $this->getEmail(), $this->getHashedPassword());
            for($i=1;$i <= 3; $i++ ){
                $preparedQuery->bindValue($i, $insertArray[$i-1]);                
            }
            if($preparedQuery->execute()){
                $this->id = $db->lastInsertId();
                return true;
            }
        }
        return false;
    }
    
    public static function loginUser($username, $password){
        $pdo = Db::getInstance();
        $db = $pdo->get_pdo();
        
        $sql = "select hashed_password from users where username = ?";
        $preparedQuery = $db->prepare($sql);
        $preparedQuery->bindValue(1, $username);
        if($preparedQuery->execute()){
            $queryResult = $preparedQuery->fetchAll(PDO::FETCH_OBJ);
           
            if(count($queryResult) > 0){
                echo "inside If";
                $queryResult = $queryResult[0];
                return ( password_verify($password, $queryResult->hashed_password) )? true : false;
            }
        }
        return false;
    }
    
    public static function getUserIdFromDbByUsername($username){
        $pdo = Db::getInstance();
        $db = $pdo->get_pdo();
        
        $sql = "select id from users where username = ?";
        $preparedQuery = $db->prepare($sql);
        $preparedQuery->bindValue(1, $username);
        if($preparedQuery->execute()){
            $queryResult = $preparedQuery->fetchAll(PDO::FETCH_OBJ);
           return ( count($queryResult) > 0 )? $queryResult[0]->id : null;
        }
        return null;
    }
    
    public static function logOut(){        
        unset($_SESSION['userId']);        
    }
    
    public static function loadUserById($id){
        $pdo = Db::getInstance();
        $db = $pdo->get_pdo();
        
        $sql = "select * from users where id = ?";
        $preparedQuery = $db->prepare($sql);
        $preparedQuery->bindValue(1, $id);
        if($preparedQuery->execute()){
            
            $queryResult = $preparedQuery->fetchAll(PDO::FETCH_OBJ);
            
            if(count($queryResult) > 0){
                $queryResult = $queryResult[0];
                $loadUser = new User();
                $loadUser->setEmail($queryResult->mail);
                $loadUser->setUsername($queryResult->username);
                $loadUser->id = $queryResult->id;
                //echo $queryResult->id."</br>";
                $loadUser->hashedPassword = $queryResult->hashed_password;
                return $loadUser;   
            }
        }
        return null;        
    }
    
    
    public static function loadAllUsers(){
        $pdo = Db::getInstance();
        $db = $pdo->get_pdo();
        $allUsersArray = array();
        
        $sql = "select id from users";
        foreach($db->query($sql) as $userId){
         $allUsersArray[] = User::loadUserById($userId['id']);
        }
        return $allUsersArray;
    }
    
    
    
    public function deleteUser(){
        $pdo = Db::getInstance();
        $db = $pdo->get_pdo();
        
        if($this->id != -1){
            $sql = "delete from users where id =".$this->id;
            echo $sql;
            if($db->query($sql)){
                 return true;   
            }  
            return false;
        }
        return true;
    }
}
