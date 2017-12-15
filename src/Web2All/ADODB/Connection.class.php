<?php
// requires third party package "adodb"
Web2All_Manager_ClassInclude::loadClassname('adodb','INC','adodb');
Web2All_Manager_ClassInclude::loadClassname('adodb-exceptions','INC','adodb');

/**
 * @name Web2All_ADODB_Connection
 * This class is a wrapper for the ADODB database wrapper 
 * http://phplens.com/lens/adodb/docs-adodb.htm
 * http://sourceforge.net/projects/adodb/
 *
 * Requires externals:
 * include  adodb svn://subversion.intra.web2all.nl/web2all_std/trunk/include/adodb
 * OR
 * include  adodb svn://subversion.intra.web2all.nl/web2all_std/trunk/include/adodb5
 * 
 * @author Merijn van den Kroonenberg
 * @copyright (c) Copyright 2007-2017 Web2All B.V.
 * @since 2007-08-02
 */
class Web2All_ADODB_Connection extends Web2All_Manager_Plugin { 
    
  public function __construct(Web2All_Manager_Main $web2all) {
    parent::__construct($web2all);

  }
  
  /**
   * Create a database connection and return it
   *
   * @param mixed $param  can be a adodb connectstring OR an assoc array with config keys.
   * accepted config keys:
   * type          : database type [default:mysql]
   * host          : hostname db server [default:localhost]
   * database      : database name [required]
   * user          : database username [required]
   * password      : password of database user [required]
   * charset       : charset of the database connection [optional]
   * debug_queries : when set, you can override the logging of queries,
   *                 by default, queries are logged at DEBUGLEVEL_FULL [optional]
   * debug_web2all : set true if debugging needs to be delegated to 
   *                 Web2All_Manager_Main. Default false. [optional]
   * @return ADOConnection
   */
  public function connect($param){
    
    $charset='';
    $debug_queries=null;
    $debug_override=false;
    
    if (is_array($param)) {
      // param is hash with settings
      if (!array_key_exists('type',$param) || !$param['type']) {
        $param['type']='mysql';
        if($this->Web2All->DebugLevel >= Web2All_Manager_Main::DEBUGLEVEL_FULL) {
          $this->Web2All->DebugLog('Web2All_ADODB_Connection::connect: defaulting to db type mysql');
        }
      }
      // check if required settings are available
      // but only for specific db types
      if($param['type']=='sqlite3'){
        $param['user']='';
        $param['password']='';
        if (!array_key_exists('database',$param) || !$param['database']) {
          $param['database']='';
        }
      }else{
        if (!array_key_exists('user',$param) || !$param['user']) {
          throw new Exception('Web2All_ADODB_Connection::connect: no user specified');
        }
        if (!array_key_exists('password',$param)) {
          throw new Exception('Web2All_ADODB_Connection::connect: no password specified');
        }
        if (!array_key_exists('database',$param) || !$param['database']) {
          throw new Exception('Web2All_ADODB_Connection::connect: no database specified');
        }
        if (!array_key_exists('host',$param) || !$param['host']) {
          $param['host']='localhost';
          if($this->Web2All->DebugLevel >= Web2All_Manager_Main::DEBUGLEVEL_FULL) {
            $this->Web2All->DebugLog('Web2All_ADODB_Connection::connect: defaulting to host localhost');
          }
        }
      }
    
      if (array_key_exists('charset',$param) && $param['charset']) {
        $charset=$param['charset'];
      }
      if (array_key_exists('debug_queries',$param)) {
        $debug_queries=$param['debug_queries'];
      }
      if (array_key_exists('debug_override',$param)) {
        $debug_override=$param['debug_override'];
      }

      if (strpos($param['type'],'informix')!==false && array_key_exists('database',$param)) {
        $param['host'].= '; database='.$param['database'];
      }
      if (strpos($param['type'],'mysql')===0 && array_key_exists('database',$param)) {
        $param['database'].='?new';
      }
      if (array_key_exists('service',$param)) {
        $param['host'].= '; service='.$param['service'];
      }
      if (array_key_exists('server',$param)) {
        $param['host'].= '; server='.$param['server'];
      }
      if (array_key_exists('protocol',$param)) {
        $param['host'].= '; protocol='.$param['protocol'];
      }
      
      $param=$param['type'].'://'.($param['user'] ? $param['user'].($param['password'] ? ':'.$param['password'] : '').'@' : '').($param['host'] ? $param['host'].'/' : '').$param['database'];
      if($this->Web2All->DebugLevel >= Web2All_Manager_Main::DEBUGLEVEL_FULL) {
        $this->Web2All->DebugLog('Web2All_ADODB_Connection::connect: connect uri:'.$param);
      }
      
    }
    
    // override debug output
    $debug_output=(!is_null($debug_queries) && $debug_queries) || (is_null($debug_queries) && $this->Web2All->DebugLevel >= Web2All_Manager_Main::DEBUGLEVEL_HIGH);
    if($debug_output && $debug_override){
      global $ADODB_OUTP;
      if (!isset($ADODB_OUTP)) {
        $outp_handler=$this->Web2All->PluginGlobal->Web2All_ADODB_OutputHandler();
        $ADODB_OUTP=array($outp_handler,'outp');
      }
    }
    // we suppress trace information and re throw exceptions, everything
    // to hide the connect password which would show in the error report.
    $this->Web2All->suppressTrace();
    try{
      $conn = ADONewConnection( $param );
    }
    catch (Exception $e){
      $this->Web2All->enableTrace();
      throw new Exception($e->getMessage());
    }
    $this->Web2All->enableTrace();
    if($debug_output) {
      // when set to true then html errors will be output on query errors. Thats why we set to 1, to prevent this.
      // adodb does things like this: if ($this->debug == 99) adodb_backtrace(true,5);	
      // and when comparing a boolean against int, the int will be converted to boolean, thus this will return true
      $conn->debug = 1;
    }
    $conn->SetFetchMode(ADODB_FETCH_ASSOC);
    
    // if needed, set the charset (mysql only??)
    if ($charset) {
      // a charset is specified
      // if its set to 'database' then use the default charset/collation of the current database
      if ($charset=='database') {
        $conn->Execute('SET CHARACTER SET @@character_set_database');
      }else{
        $conn->Execute('SET NAMES '.$charset);
      }
    }
    
    
    return $conn;
  }
    
}

?>