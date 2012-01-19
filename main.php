<?php

include('validation.php');
include('request.php');

#REDIS key formats
#user to user conversation: user-#-conversation-user-list -> user_id
#user to user conversation: user-#-conversations-list -> conv_id
#conversation: conversation-$ -> array(from, msg)
#new conversations for user: user-#-new-conversations -> conv_id

/**
* Send a message from one user to another.
* Conversation is created if does not exist.
* Handles 'new' counts and list
*/
function send($request)
{
    global $redis_server;

    if(!validate_fields($request, array('from', 'to', 'msg_text')))   {
        die(bad_request('missing field'));
    }

    $client = new Predis\Client($redis_server);

    if(isset($request->data['conversation_id']) or !empty($request->data['conversation_id']))
	$conv_id = $request->data['conversation_id'];
    else
	$conv_id = generate_conv_id($request->data['from'], $request->data['to']);

    //add conversation-user to each user
    $client->sadd("user-{$request->data['from']}-conversation-user-list", $request->data['to']);
    $client->sadd("user-{$request->data['to']}-conversation-user-list", $request->data['from']);
    
    //create conversation
    $client->rpush("conversation-$conv_id", "**{$request->data['from']}**{$request->data['msg_text']}" );

    //add conversation for each user
    $client->sadd("user-{$request->data['from']}-conversations-list", $conv_id);
    $client->sadd("user-{$request->data['to']}-conversations-list", $conv_id);

    //add new conv for receiving user
    $client->sadd("user-{$request->data['to']}-new-conversations", $conv_id);

    return json_encode(array('code' => '1', 'conversation_id' => $conv_id));
}

/**
* Gets messages in a conversation
*/
function get_conversation($request)
{
    global $redis_server;

    if(!validate_fields($request, array('conversation_id')))   {
        die(bad_request('missing field'));
    }

    $client = new Predis\Client($redis_server);
    $messages = $client->lrange("conversation-".$request->data['conversation_id'], 0, -1);

    return json_encode(array('code' => '1', 'conversation_id' => $request->data['conversation_id'], 'messages' => $messages));
}

/**
* Gets list of conversation ids for a user
*/
function get_conversations_list($request)
{
    global $redis_server;

    if(!validate_fields($request, array('user')))   {
        die(bad_request('missing field'));
    }

    $client = new Predis\Client($redis_server);
    $convs = $client->smembers("user-{$request->data['user']}-conversations-list");

    return json_encode(array('code' => '1', 'conversations' => $convs, 'user' => $request->data['user']));
}

/**
* Gets list of new conversation ids for a user
*/
function get_new_conversation_list($request)
{
    global $redis_server;

    if(!validate_fields($request, array('user')))   {
        die(bad_request('missing field'));
    }

    $client = new Predis\Client($redis_server);
    $convs = $client->smembers("user-{$request->data['user']}-new-conversations");
    
    return json_encode(array('code' => '1', 'conversations' => $convs, 'user' => $request->data['user'])); 
}

/**
* Gets count of new conversations for a user
*/
function get_new_conversation_count($request)
{
    global $redis_server;

    if(!validate_fields($request, array('user')))   {
        die(bad_request('missing field'));
    }

    $client = new Predis\Client($redis_server);
    $count = $client->scard("user-{$request->data['user']}-new-conversations");
    
    return json_encode(array('code' => '1', 'count' => $count, 'user' => $request->data['user'])); 
}

/**
* Clears the list of new conversations for a user
*/
function clear_new_conversations($request)
{
    global $redis_server;

    if(!validate_fields($request, array('user')))   {
        die(bad_request('missing field'));
    }

    $client = new Predis\Client($redis_server);
    $client->del("user-{$request->data['user']}-new-conversations");

    return successful_request();
}

function generate_conv_id($from, $to) {
    if($from > $to)
	return md5($from."-".$to);
    else    
	return md5($to."-".$from);
}

function flush_db()
{
    global $redis_server;

    $client = new Predis\Client($redis_server);
    $client->flushdb();
}
?>
