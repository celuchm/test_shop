<?php

/**
 * Created by PhpStorm.
 * User: mc
 * Date: 09.05.17
 * Time: 20:39
 */
class db
{
    private static $_instance = null;
    private $_pdo,
            $_query,
            $_result,
            $_firstRow,
            $_count,
            $_lastId,
            $_error = false ,
            $_errors = [];

    private function __construct(){
        try{
            $this->_pdo = new PDO('mysql:host='.Config::get_config("mysql/host").
                                  ';dbname='.Config::get_config("mysql/db_name"),
                                  Config::get_config("mysql/user"),
                                  Config::get_config("mysql/password"));
            $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch ( PDOException $e){
            die($e->getMessage());
        }
    }

    public static function getInstance(){
        if( !isset(self::$_instance)){
            self::$_instance = new db();

        }
            return self::$_instance;

    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * @return mixed
     */
    public function getFirstRow()
    {
        return $this->_firstRow;
    }

    /**
     * @return mixed
     */
    public function getError()
    {
        return $this->_error;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * @return mixed
     */
    public function getLastId()
    {
        return $this->_lastId;
    }

    /**
     * @param mixed $lastId
     */
    private function setLastId($lastId)
    {
        $this->_lastId = $lastId;
    }





    public function query($queryType, $table, $queryParam, $limit = null){

        if ( strtolower($queryType) == "select" ||
             strtolower($queryType) == "update" ||
             strtolower($queryType) == "delete" ||
             strtolower($queryType) == "insert"){
           $bindStatement = $this->prepareBindStatement( $queryType, $table, $queryParam, $limit );
           //var_dump($bindStatement);
           //$this->_query = $bindStatement[0];
        } else {
            $this->_error = true;
            $this->_errors[] = "błędny rodzaj mysql query";
        }
        try {
            if (!$this->_error) {
                if ($this->_query = $this->_pdo->prepare($bindStatement[0])) {
                   // var_dump(count($bindStatement[1]));
                    if ( is_array($bindStatement[1]) && count($bindStatement[1]) ) {
                        for ($i = 1; $i <= count($bindStatement[1]); $i++) {
                            $this->_query->bindValue($i, $bindStatement[1][$i - 1]);
                        }
                    }
                    if ($this->_query->execute()) {
                        if (strtolower($queryType) == "select") {
                            $this->_result = $this->_query->fetchAll(PDO::FETCH_OBJ);
                            $this->_count = $this->_query->rowCount();
                            $this->_firstRow = $this->_result[0];
                        }
                        if ( strtolower($queryType) == "insert") {
                            $this->setLastId($this->_pdo->lastInsertId());
                        }
                    } else {
                        $this->_error = true;
                    }
                }
            }
        } catch (PDOException $e){
            $this->_error = true;
            $this->_errors[] = $e->getMessage();
        }
        return $this;
    }

    private function prepareBindStatement($queryType, $table, $queryParam, $limit = null){

            $where = (array_key_exists("where", $queryParam)) ? $queryParam["where"] : "";
            $columns = array_key_exists("columns", $queryParam) ? $queryParam["columns"] : "*";
            $order = array_key_exists("order", $queryParam) ? $queryParam["order"] : "";
            $set = array_key_exists("set", $queryParam) ? $queryParam["set"] : "";
            $into = array_key_exists("into", $queryParam) ? $queryParam["into"] : "";
            $sqlLimit = is_null($limit) ? "" : $limit;


        switch (strtolower($queryType)) {

            case "select":
                $sqlToBind = $this->createSelectSql( $table, $columns, $where, $order, $limit);
                break;
            case "update":
                $sqlToBind = $this->createUpdateSql( $table, $where, $set);
                break;
            case "insert":
                $sqlToBind = $this->createInsertSql( $table, $into);
                break;
            case "delete":
                $sqlToBind = $this->createDeleteSql( $table, $where);
                break;
        }




        return $sqlToBind;
    }


    private function createSelectSql( $table, $colums,  $where, $order, $limit ){

        $columnsToSelectArray = $colums;
            if( is_array($columnsToSelectArray) ){
                $columnsToSelectString = $this->getColumnsToSelectSqlPary($columnsToSelectArray);
            } else {
                $columnsToSelectString = " * ";
            }

            if( is_array($where) ){
                $whereSqlPart = $this->getWhereSqlPart($where);
                $bindData = array();
                for( $i=0; $i<count($where); $i++){
                    $bindData[] = $where[$i][2];
                }
            } else {
                $whereSqlPart = "";
                $bindData = "";
            }

            if( is_array($order) ){
                $orderColumns = array_keys($order);
                $orderValues = array_values($order);
                $orderSqlPart = $this->getOrderSqlPart($orderColumns, $orderValues);
            } else {
                $orderSqlPart = "";
            }

        $sqlToBind = "select {$columnsToSelectString} from {$table} {$whereSqlPart} {$orderSqlPart}";
        $sqlToBind = array($sqlToBind, $bindData);
        return $sqlToBind;
    }


    private function createUpdateSql( $table, $where, $set ){
        $bindData = array();
        if( is_array($set) ){
            $setArray = array_keys($set);
            $setSqlPart = $this->getSetSqlPart($setArray);
            $bindData = array_merge($bindData, array_values($set));

        }

        if( is_array($where) ){
            $whereSqlPart = $this->getWhereSqlPart($where);
            $bindDataWhere = array();
            for( $i=0; $i<count($where); $i++){
                $bindDataWhere[] = $where[$i][2];
            }
            $bindData = array_merge($bindData, $bindDataWhere);
            /*
            $whereColumns = array_keys($where);
            $whereSqlPart = $this->getWhereSqlPart( array_keys($where));
            $bindData = array_merge($bindData, array_values($where));*/
        } else {
            $whereSqlPart = "";
        }

        $sqlToBind = "update {$table} {$setSqlPart} {$whereSqlPart}";
        $sqlToBind = array($sqlToBind, $bindData);
        return $sqlToBind;
    }

    private function createInsertSql( $table, $into ){

        if( is_array($into) ){
            if( array_key_exists("all", $into)){
                $insertColumns = "";
                $insertValues = array_values($into["all"]);

            } else {
                $insertColumns = array_keys($into);
                $insertValues = array_values($into);
            }
            $bindData = $insertValues;
            $setIntoPart = $this->getIntoSqlPart($insertColumns, $insertValues);
        }
        $sqlToBind = "insert into {$table} {$setIntoPart}";
        $sqlToBind = array($sqlToBind, $bindData);
        return $sqlToBind;
    }

    private function createDeleteSql( $table, $where ){
        if( is_array($where) ){
            $whereSqlPart = $this->getWhereSqlPart($where);
            $bindDataWhere = array();
            for( $i=0; $i<count($where); $i++){
                $bindDataWhere[] = $where[$i][2];
            }
        } else {
            $whereSqlPart = "";
            $bindDataWhere = "";
        }

        $sqlToBind = "delete from {$table} {$whereSqlPart}";
        $sqlToBind = array($sqlToBind, $bindDataWhere);
        return $sqlToBind;
    }


    private function getWhereSqlPart($whereConditions){
        //array("id", "=", 5 )

        $sqlPart = "where ";
        $whereConditionsCount = count($whereConditions);
        for($i=0; $i < $whereConditionsCount ; $i++) {
            $whereColumn = $whereConditions[$i][0];
            $whereOperator = $whereConditions[$i][1];
            $sqlPart .= $whereColumn." ".$whereOperator." ? ";
            if( $i < $whereConditionsCount - 1 ){
                $sqlPart .= " and ";
            }
        }
        return $sqlPart;
    }

    private function getColumnsToSelectSqlPary($columnsToSelectArray){
        $columnsToSelectString = "";
        $columnsToSelectArrayCount = count($columnsToSelectArray);
        for( $i=0; $i< $columnsToSelectArrayCount ; $i++ ){

            $columnsToSelectString .= $columnsToSelectArray[$i];
            if( $i < $columnsToSelectArrayCount -1){
                $columnsToSelectString .= ", ";
            }
        }
        return $columnsToSelectString;
    }

    private function getOrderSqlPart($orderColumns, $orderValues){
        $orderSqlPart = "order by ";
        $orderCount = count($orderColumns);
        for( $i=0; $i< $orderCount ; $i++ ){
            $orderSqlPart .= $orderColumns[$i]." ".$orderValues[$i];
            if( $i < $orderCount-1 ){
                $orderSqlPart .= ", ";
            }
        }
        return $orderSqlPart;
    }

    private function getSetSqlPart($setArray){
        $sqlPart = "set ";
        $setArrayCount = count($setArray);
        for($i=0; $i < $setArrayCount ; $i++) {
            $sqlPart .= $setArray[$i]." = ?";
            if( $i < $setArrayCount - 1 ){
                $sqlPart .= ", ";
            }
        }
        return $sqlPart;
    }

    private function getIntoSqlPart($insertColumns, $insertValues){
        if( $insertColumns == "" ){
            $sqlPart = "values (";
        } else {
            $sqlPart = "(";
            $insertColumnsCount = count($insertColumns);
            for( $i=0;$i<$insertColumnsCount; $i++){
                $sqlPart .= $insertColumns[$i];
                if( $i < $insertColumnsCount -1){
                    $sqlPart .= ", ";
                }
            }
            $sqlPart .= ") values (";
        }

        $insertValuesCount = count($insertValues);
        for( $i=0; $i<$insertValuesCount; $i++){
            $sqlPart .= "?";
            if( $i < $insertValuesCount -1){
                $sqlPart .= ", ";
            }
        }
        $sqlPart .= ")";
        return $sqlPart;
    }
    /*
     * 1. Check query type
     * 2. Prepare bind sql
     * 2. execute prepare statenemt
     * 3. execute execute statement
     * 3. get result
     * 4. return
     *
     */






}