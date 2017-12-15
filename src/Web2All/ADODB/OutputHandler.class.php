<?php

/**
 * @name Web2All_ADODB_OutputHandler
 * 
 * This class is an output handler for the ADODB database abstraction class.
 * It will delegate debugoutput to Web2All_Manager_Main->debugLog().
 * 
 * @author Merijn van den Kroonenberg
 * @copyright (c) Copyright 2008 Web2All B.V.
 * @since 2008-12-08
 */
class Web2All_ADODB_OutputHandler extends Web2All_Manager_Plugin { 
    
  public function __construct(Web2All_Manager_Main $web2all) {
    parent::__construct($web2all);
  }
  
  public function outp($msg,$newline)
  {
    $lines = preg_split("/\r\n|\n\r|\n|\r/",$msg);
    $msg = '';
    foreach ($lines AS $line)
    {
      $line=trim(html_entity_decode(strip_tags($line)));
      if (!$line || $line=='-----') {
      	continue;
      }
      $this->Web2All->debugLog('ADODB '.$line);
    }
  }
  
}
?>