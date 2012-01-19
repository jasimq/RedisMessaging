<?php

function validate_fields($request, $fields) {
    foreach($fields AS $x => $field)    {
	if(!isset($request->data[$field]) OR trim($request->data[$field]) == '' )
	{   
	    logger("FIELD MISSING: $field", 2);
	    return false;
	}
    }
    return true;
}

function bad_request($msg=null, $data=null) {
    $output = array("code" => "bad_request");
    if($msg)
        $output['msg'] = $msg;

    if($data)
        $output = array_merge($output, $data);

    return json_encode($output);
}

