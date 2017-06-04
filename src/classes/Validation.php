<?php

class Validation {
    private $_passed = false,
            $_errors = array();
            
    
    public function check($source, $items = array()){
        //var_dump($source);
        foreach($items as $fieldToValidate => $appliedRules){
            
            foreach($appliedRules as $ruleName => $ruleValue){
            //echo "field to Validate: ".$fieldToValidate.", rule to applay: ".$ruleName.". rule value: ".$ruleValue."</br>";
            
                switch($ruleName){
                    case 'required':
                        if($ruleValue){
                            if( $source[$fieldToValidate] == "" ){
                                $this->_errors[] = "fill ".$fieldToValidate;                                
                            }
                        }                        
                        break;
                    case 'length':
                        if(strlen($source[$fieldToValidate]) > $ruleValue ){
                            $this->_errors[] = $fieldToValidate." is too long. Allowed lenght is ".$ruleValue;
                        }
                        break;
                    case 'match':
                        foreach($ruleValue as $valueToMach => $fieldToMach)
                            
                        if($source[$fieldToValidate] != $valueToMach){
                            $this->_errors[] = $fieldToValidate." does not match ".$fieldToMach;
                        }
                        break;
                    case 'unique':
                        
                        if (strlen($source[$fieldToValidate]) > 0){
                            $sql = "select * from ".$ruleValue." where ".$fieldToValidate." = ?";
                            
                            $pdo = Db::getInstance();
                            $db = $pdo->get_pdo();

                            $preparedQuery = $db->prepare($sql);
                            $preparedQuery->bindValue(1,$source[$fieldToValidate]);
                            if($preparedQuery->execute()){
                                $queryResult = $preparedQuery->fetchAll(PDO::FETCH_OBJ);
                                //echo "executer</br>";
                               if(count($queryResult) > 0){
                                   //echo "no result</br>";
                                   $this->_errors[] = $fieldToValidate." is not unique!";
                               }
                            }
                        }
                        
                        break;    
                }
            }
        }
        if(count($this->_errors) == 0){
            $this->_passed = true;
        }
        return $this;
    }
    
    public function passed(){
        return $this->_passed;
    }
    
    function get_errors() {
        return $this->_errors;
    }


    
}
