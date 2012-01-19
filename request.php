<?php

Class Request
{
    //public $method;    
    //public $action;
    public $output;
    public $request;
    public $data;
     
    function __construct($request, $supported_requests) {
	/*if(isset($_REQUEST['method']))
	    $this->method = $this->clean($_REQUEST['method']);
	else
	    die('METHOD MISSING');
	
	if(isset($_REQUEST['action']))
	    $this->action = $this->clean($_REQUEST['action']);

	if(!in_array($_REQUEST['action'], $this->supported_actions))
	    die('INVALID ACTION');
	*/

	if(isset($_REQUEST['request']))
	    $this->request = $this->clean($_REQUEST['request']);
	else
	    die('REQUEST MISSING');

	if(!in_array($_REQUEST['request'], $supported_requests))
	    die('INVALID request');

	if(isset($_REQUEST['output']))
	    $this->output = $this->clean($_REQUEST['output']);
	//else	
	    //$this->output = 'json';

	foreach($_REQUEST as $key => $value)
	    $this->data[$key] = $this->clean($value);
    }

    public function clean($value) {
	return mysql_escape_string($value);
    }
}
?>
