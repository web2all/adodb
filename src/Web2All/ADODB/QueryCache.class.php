<?php

/**
 * Web2All Query Cache database class
 * 
 * This class allows buffered insert's. 
 * 
 * functionality copied from Web2All_MySQL_QueryCache
 *
 * @author Merijn van den Kroonenberg
 * @copyright (c) Copyright 2007 Web2All B.V.
 * @since 2007-12-05
 */
class Web2All_ADODB_QueryCache extends Web2All_Manager_Plugin {

  /**
   * ADODB database handle
   *
   * @var ADOConnection
   */
  protected $db;
  
  /**
   * assoc array with chached insert queries.
   * key: insert part of query. 'INSERT INTO table (fieldname1,fieldname2) VALUES '
   * value: array with all value parts. '(fieldvalue1,fieldvalue2)'
   *
   * @var unknown_type
   */
  protected $insertquerystore=array();

  /**
   * constructor
   *
   * @param Web2All_Manager_Main $web2all
   * @param ADOConnection $db
   */
  public function __construct(Web2All_Manager_Main $web2all, $db) {
    parent::__construct($web2all);
    
    $this->db=$db;
  }
  
  /**
   * Destructor, flush remaining queries
   */
  public function __destruct() {
    try {
      $this->flushAllQueries();
    }
    catch(Exception $e){
      // if it fails, too bad
    }
    parent::__destruct();
  }
  
  /**
   * flush a specific insert query
   *
   * @param string $insert_part  'INSERT INTO table (fieldname1,fieldname2) VALUES '
   */
  public function flushInsertQuery($insert_part)
  {
    // build full query
    $values=implode(',',$this->insertquerystore[$insert_part]);
    
    // flush cache (flush early, conserve memory)
    unset($this->insertquerystore[$insert_part]);
    // execute query
    $this->db->Execute($insert_part.$values);
  }
  
  /**
   * flush all cached queries to the database
   *
   */
  public function flushAllQueries()
  {
    // loop the keys only (better memory conservation)
    foreach (array_keys($this->insertquerystore) as $insert_part) {
      $this->flushInsertQuery($insert_part);
    }
  }
  
  /**
   * Do a cached insert query
   *
   * @param string $insert_part  'INSERT INTO table (fieldname1,fieldname2) VALUES '
   * @param array $values  array(fieldvalue1,fieldvalue2)
   */
  public function doInsertQueryCached($insert_part, $values)
  {
    if (!array_key_exists($insert_part,$this->insertquerystore)) {
      // add new query to store
      $this->insertquerystore[$insert_part]=array();
    }else{
      if (count($this->insertquerystore[$insert_part])>50) {
        $this->flushInsertQuery($insert_part);
        $this->insertquerystore[$insert_part]=array();
      }
    }
    
    $this->insertquerystore[$insert_part][]='('.implode(',',$values).')';
    
  }
  
}
?>