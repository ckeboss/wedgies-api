<?php

function auth($username, $password) {
	
	$auth_url = "https://www.wedgies.com/auth/twitter";
	
	//open connection
	$ch = curl_init();
	
	curl_setopt($ch,CURLOPT_URL, $auth_url);
	
	curl_setopt( $ch, CURLOPT_USERAGENT, "Unofficial Wedgies API v0.01" );
	curl_setopt( $ch, CURLOPT_COOKIESESSION, true );
	curl_setopt( $ch, CURLOPT_COOKIEJAR, realpath('auth_cookie') );
	curl_setopt( $ch, CURLOPT_COOKIEFILE, realpath('auth_cookie') );
	
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	//execute post
	$result = curl_exec($ch);
	//close connection
	curl_close($ch);
	
	$dom = new DOMDocument;
	$dom->loadHTML($result);
	
	$xp = new DOMXpath($dom);

	$form_action_url = $xp->query('//form[@id="oauth_form"]')->item(0)->getAttribute('action');
		
	$form_values['oauth_token'] = $xp->query('//input[@id="oauth_token"]')->item(0)->getAttribute('value');
	$form_values['authenticity_token'] = $xp->query('//input[@name="authenticity_token"]')->item(0)->getAttribute('value');

	$fields = array(
		'oauth_token' => urlencode($form_values['oauth_token']),
		'authenticity_token' => urlencode($form_values['authenticity_token']),
		'session[username_or_email]' => urlencode($username),
		'session[password]' => urlencode($password)
	);
	
	$fields_string = '';
	
	//url-ify the data for the POST
	foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
	
	$fields_string = rtrim($fields_string, '&');
	
	//open connection
	$pin_request = curl_init();
	
	//set the url, number of POST vars, POST data
	curl_setopt($pin_request,CURLOPT_URL, $form_action_url);
	
	curl_setopt($pin_request,CURLOPT_POST, count($fields));
	curl_setopt($pin_request,CURLOPT_POSTFIELDS, $fields_string);
	
	curl_setopt( $pin_request, CURLOPT_USERAGENT, "Unofficial Wedgies API v0.01" );
	curl_setopt( $pin_request, CURLOPT_COOKIESESSION, true );
	curl_setopt( $pin_request, CURLOPT_COOKIEJAR, realpath('auth_cookie') );
	curl_setopt( $pin_request, CURLOPT_COOKIEFILE, realpath('auth_cookie') );
	
	curl_setopt($pin_request, CURLOPT_RETURNTRANSFER, 1);
	//execute post
	$auth_result = curl_exec($pin_request);
	//close connection
	curl_close($pin_request);
	//Now just need to redirect to oauth callback url, and we should be authed with wedgies!
	
	$dom = new DOMDocument;
	@$dom->loadHTML($auth_result);
	
	$xp = new DOMXpath($dom);

	$oauth_redirect_url = $xp->query('//div[@id="bd"]/div/p/a/@href')->item(0)->nodeValue;
	
	//open connection
	$ch = curl_init();
	
	curl_setopt($ch,CURLOPT_URL, $oauth_redirect_url);
	
	curl_setopt( $ch, CURLOPT_USERAGENT, "Unofficial Wedgies API v0.01" );
	curl_setopt( $ch, CURLOPT_COOKIESESSION, true );
	curl_setopt( $ch, CURLOPT_COOKIEJAR, realpath('auth_cookie') );
	curl_setopt( $ch, CURLOPT_COOKIEFILE, realpath('auth_cookie') );
	
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	//execute post
	$result = curl_exec($ch);
	//close connection
	curl_close($ch);

	
	//#bd a value of href
	
/* 	echo htmlentities($auth_result); */
}

//$wedgies = get_wedgies(1);

/*
$wedgie = get_wedgie('52fd71798e30900200000593');
print_r($wedgie);
*/

/*
$wedgie_resp = add_wedgie('Does Wedgies Need an API?', array('Yes', 'No', 'A-P-What?'));
print_r($wedgie_resp);
*/

/*
$wedgie_edit_resp = edit_wedgie('5301c399bc14120200000308', 'Does Wedgies Need an API?', array('5301cf25bc1412020000031a' => 'Yes', '5301c399bc1412020000030a' => 'Nope', '5301c399bc14120200000309' => 'What is an API?'));
var_dump($wedgie_edit_resp);
*/

//authed wedgie
function edit_wedgie($wedgie_id = null, $question = null, $options = null, $config = null) {
	$post_field_count = 0;
	
	$fields_string = 'question='.urlencode($question).'&';
	$post_field_count++;
		
	$order_field = '';
	
	foreach($options as $id => $option) {
		$fields_string .= 'options'.urlencode('['.$id.']').'='.urlencode($option).'&';
		
		$order_field .= 'order[]='.$id.'&';
		$post_field_count++;
	}

	$order_field = rtrim($order_field, '&');
		
	$fields_string .= 'order='.urlencode($order_field);
	$post_field_count++;
	
	$fields_string = rtrim($fields_string, '&');
	
	$ch = curl_init();
	
	curl_setopt($ch,CURLOPT_URL, 'https://www.wedgies.com/edit/question/'.urlencode($wedgie_id));
	
	curl_setopt($ch,CURLOPT_POST, $post_field_count);
	curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
	
	curl_setopt( $ch, CURLOPT_USERAGENT, "Unofficial Wedgies API v0.01" );
	curl_setopt( $ch, CURLOPT_COOKIESESSION, true );
/* 	curl_setopt( $ch, CURLOPT_COOKIEJAR, realpath('auth_cookie') ); */
	curl_setopt( $ch, CURLOPT_COOKIEFILE, realpath('auth_cookie') );
	
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$result = curl_exec($ch);
	curl_close($ch);
	
	$dom = new DOMDocument;
	@$dom->loadHTML($result);
	
	$xp = new DOMXpath($dom);
	
	if(empty($xp->query('//h1[@class="question-text"]')->item(0)->nodeValue)) {
		return false;
	}
	
	return $wedgie_id;
}

//authed function
function add_wedgie($question = null, $options = null, $config = null) {
	
	$post_field_count = 0;
	
	$fields_string = 'question='.urlencode($question).'&';
	$post_field_count++;
	
	foreach($options as $option) {
		$fields_string .= 'options'.urlencode('[]').'='.urlencode($option).'&';
		$post_field_count++;
	}
	
	$fields_string = rtrim($fields_string, '&');
	
	$ch = curl_init();
	
	curl_setopt($ch,CURLOPT_URL, 'https://www.wedgies.com/create');
	
	curl_setopt($ch,CURLOPT_POST, $post_field_count);
	curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
	
	curl_setopt( $ch, CURLOPT_USERAGENT, "Unofficial Wedgies API v0.01" );
	curl_setopt( $ch, CURLOPT_COOKIESESSION, true );
/* 	curl_setopt( $ch, CURLOPT_COOKIEJAR, realpath('auth_cookie') ); */
	curl_setopt( $ch, CURLOPT_COOKIEFILE, realpath('auth_cookie') );
	
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$result = curl_exec($ch);
	curl_close($ch);
	
	$dom = new DOMDocument;
	@$dom->loadHTML($result);
	
	$xp = new DOMXpath($dom);
	
	if(!preg_match('/\/question\/(.*)[\/]?/', $xp->query('//p[@class="bypass"]/a/@href')->item(0)->nodeValue, $matches)) {
		return false;
	} else {
		$wedgie_id = $matches[1];
	}
	
	return $wedgie_id;
}

//unauthed function
function get_wedgie($wedgie_id) {
	
	$ch = curl_init();
	
	curl_setopt($ch,CURLOPT_URL, 'https://www.wedgies.com/ajax/question/'.urlencode($wedgie_id));
	
	curl_setopt( $ch, CURLOPT_USERAGENT, "Unofficial Wedgies API v0.01" );
	curl_setopt( $ch, CURLOPT_COOKIESESSION, true );
/* 	curl_setopt( $ch, CURLOPT_COOKIEJAR, realpath('auth_cookie') ); */
	curl_setopt( $ch, CURLOPT_COOKIEFILE, realpath('auth_cookie') );
	
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	//execute post
	$result = curl_exec($ch);
	//close connection
	curl_close($ch);
	
	$result_object = json_decode($result);
	
	if(empty($result_object)) {
		return false;
	}
	
	$question = $result_object->text;
	
	foreach($result_object->choices as $choice) {
		$options[] = array('option' => $choice->text, 'votes' => $choice->score, 'active' => $choice->active, 'id' => $choice->_id);
	}
	
	return array('id' => $result_object->_id, 'question' => $question, 'total_votes' => $result_object->totalScore, 'options' => $options);

}

//Authed function
function get_wedgies($page = 1) {
	$ch = curl_init();
	
	curl_setopt($ch,CURLOPT_URL, 'https://www.wedgies.com/user?page='.urlencode($page));
	
	curl_setopt( $ch, CURLOPT_USERAGENT, "Unofficial Wedgies API v0.01" );
	curl_setopt( $ch, CURLOPT_COOKIESESSION, true );
/* 	curl_setopt( $ch, CURLOPT_COOKIEJAR, realpath('auth_cookie') ); */
	curl_setopt( $ch, CURLOPT_COOKIEFILE, realpath('auth_cookie') );
	
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	//execute post
	$result = curl_exec($ch);
	//close connection
	curl_close($ch);
	
	$dom = new DOMDocument;
	@$dom->loadHTML($result);
	
	$xp = new DOMXpath($dom);
	
	$wedgies = false;
	
	foreach($xp->query('//ul[@class="question-list"]/li[@class="question"]/p[@class="question-text"]/a') as $question_item) {
		$wedgie_id = false;
		if(preg_match('/\/question\/(.*?)\//', $question_item->attributes->item(0)->nodeValue, $matches)) {
			$wedgie_id = $matches[1];
		}
		$wedgies[] = array('question' => $question_item->nodeValue, 'id' => $wedgie_id);
	}

/* 	$pages = preg_replace('/Page [0-9] of /', '', $xp->query('//div[@class="pagination"]/span[@class="page"]')->item(0)->nodeValue); */

	return $wedgies;
}

//authed function
function get_amount_of_wedgies_pages() {
	$ch = curl_init();
	
	curl_setopt($ch,CURLOPT_URL, 'https://www.wedgies.com/user');
	
	curl_setopt( $ch, CURLOPT_USERAGENT, "Unofficial Wedgies API v0.01" );
	curl_setopt( $ch, CURLOPT_COOKIESESSION, true );
/* 	curl_setopt( $ch, CURLOPT_COOKIEJAR, realpath('auth_cookie') ); */
	curl_setopt( $ch, CURLOPT_COOKIEFILE, realpath('auth_cookie') );
	
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	//execute post
	$result = curl_exec($ch);
	//close connection
	curl_close($ch);
	
	$dom = new DOMDocument;
	@$dom->loadHTML($result);
	
	$xp = new DOMXpath($dom);

	$pages = preg_replace('/Page [0-9] of /', '', $xp->query('//div[@class="pagination"]/span[@class="page"]')->item(0)->nodeValue);
	
	return $pages;
}