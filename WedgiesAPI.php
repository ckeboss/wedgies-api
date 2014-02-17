<?php
include('oocurl/OOCurl.php');

class WedgiesAPI {
	
	public $user_agent = 'Unofficial Wedgies API v0.01';
	
	protected $cookie_file = null;
	protected $username = null;
	protected $password = null;
	
	public function __construct($username = null, $password = null, $user_agent = null, $cookie_file = null) {
		$this->username = $username;
		$this->password = $password;
		
		if($user_agent !== null) {
			$this->user_agent = $user_agent;
		}
		
		$this->cookie_file = $cookie_file;
	}
	
	public function is_authed() {
		$curl = new Curl('https://www.wedgies.com/user');
		
		if(!$curl) {
			throw new Exception('Could not create Curl object.');
		}
		
		$curl->useragent = $this->user_agent;
		$curl->cookiesession = true;
		$curl->cookiefile = realpath($this->cookie_file);
		$curl->followlocation = false;
		$curl->header = true;
		
		$is_auth_resp = $curl->exec();
		
		$http_code = substr($is_auth_resp, 9, 3);
		$curl->close();
		
		if($http_code != '200') {
			return false;
		}
		
		return true;
	}

	protected function auth() {
		$curl = new Curl('https://www.wedgies.com/auth/twitter');
		
		if(!$curl) {
			throw new Exception('Could not create Curl object.');
		}
		
		$curl->useragent = $this->user_agent;
		$curl->cookiesession = true;
		$curl->cookiejar = realpath($this->cookie_file);
		$curl->cookiefile = realpath($this->cookie_file);
		$curl->followlocation = true;
		
		$twitter_resp = $curl->exec();
		$curl->close();
		
		$dom = new DOMDocument;
		$dom->loadHTML($twitter_resp);
		
		$xp = new DOMXpath($dom);
		
		$form_action_url = $xp->query('//form[@id="oauth_form"]')->item(0)->getAttribute('action');
			
		$form_values['oauth_token'] = $xp->query('//input[@id="oauth_token"]')->item(0)->getAttribute('value');
		$form_values['authenticity_token'] = $xp->query('//input[@name="authenticity_token"]')->item(0)->getAttribute('value');
		
		$twitter_auth_fields = array(
			'oauth_token' => urlencode($form_values['oauth_token']),
			'authenticity_token' => urlencode($form_values['authenticity_token']),
			'session[username_or_email]' => urlencode($this->username),
			'session[password]' => urlencode($this->password)
		);
		
		$twitter_auth_fields_string = '';
		
		//url-ify the data for the POST
		foreach($twitter_auth_fields as $key=>$value) { $twitter_auth_fields_string .= $key.'='.$value.'&'; }
		
		$twitter_auth_fields_string = rtrim($twitter_auth_fields_string, '&');
		
		$curl = new Curl($form_action_url);

		$curl->post = count($twitter_auth_fields);
		$curl->postfields = $twitter_auth_fields_string;
		$curl->useragent = $this->user_agent;
		$curl->cookiesession = true;
		$curl->cookiejar = realpath($this->cookie_file);
		$curl->cookiefile = realpath($this->cookie_file);
		
		$auth_result = $curl->exec();
		$curl->close();
		
		//Now just need to go to oauth callback url, and we should be authed with wedgies!
		$dom = new DOMDocument;
		@$dom->loadHTML($auth_result);
		
		$xp = new DOMXpath($dom);
	
		$oauth_redirect_url = $xp->query('//div[@id="bd"]/div/p/a/@href')->item(0)->nodeValue;
		
		$curl = new Curl($oauth_redirect_url);
		
		$curl->useragent = $this->user_agent;
		$curl->cookiesession = true;
		$curl->cookiejar = realpath($this->cookie_file);
		$curl->cookiefile = realpath($this->cookie_file);
		
		$oauth_callback_result = $curl->exec();
		$curl->close();
		
		//TODO test auth, see if we are really logged in
		return true;
	}
	
	//Authed request.
	public function edit_wedgie($wedgie_id = null, $question = null, $options = null, $config = null) {
		
		if(!$this->is_authed()) {
			$this->auth();
		}
		
		$curl = new Curl('https://www.wedgies.com/edit/question/'.urlencode($wedgie_id));
		
		if(!$curl) {
			throw new Exception('Could not create Curl object.');
		}
		
		$post_field_count = 0;
	
		$edit_wedgie_fields_string = 'question='.urlencode($question).'&';
		$post_field_count++;
			
		$order_field = '';
		
		foreach($options as $id => $option) {
			$edit_wedgie_fields_string .= 'options'.urlencode('['.$id.']').'='.urlencode($option).'&';
			$post_field_count++;
			
			$order_field .= 'order[]='.$id.'&';
		}
	
		$order_field = rtrim($order_field, '&');
			
		$edit_wedgie_fields_string .= 'order='.urlencode($order_field);
		$post_field_count++;
		
		$edit_wedgie_fields_string = rtrim($edit_wedgie_fields_string, '&');
		
		$curl->post = $post_field_count;
		$curl->postfields = $edit_wedgie_fields_string;
		$curl->useragent = $this->user_agent;
		$curl->cookiesession = true;
		$curl->cookiefile = realpath($this->cookie_file);
		$curl->followlocation = true;
		
		$edit_resp = $curl->exec();
		$curl->close();
		
		$dom = new DOMDocument;
		@$dom->loadHTML($edit_resp);
		
		$xp = new DOMXpath($dom);
		
		if(empty($xp->query('//h1[@class="question-text"]')->item(0)->nodeValue)) {
			throw new Exception('Error creating wedgie');
		}
		
		return $wedgie_id;		
	}
	
	//Authed request.
	public function add_wedgie($question = null, $options = null, $config = null) {
		
		if(!$this->is_authed()) {
			$this->auth();
		}
		
		$curl = new Curl('https://www.wedgies.com/create');
		
		if(!$curl) {
			throw new Exception('Could not create Curl object.');
		}
		
		$post_field_count = 0;
		
		$add_wedgie_fields_string = 'question='.urlencode($question).'&';
		$post_field_count++;
		
		foreach($options as $option) {
			$add_wedgie_fields_string .= 'options'.urlencode('[]').'='.urlencode($option).'&';
			$post_field_count++;
		}
		
		$add_wedgie_fields_string = rtrim($add_wedgie_fields_string, '&');
		
		$curl->post = $post_field_count;
		$curl->postfields = $add_wedgie_fields_string;
		$curl->useragent = $this->user_agent;
		$curl->cookiesession = true;
		$curl->cookiefile = realpath($this->cookie_file);
		$curl->followlocation = true;
		
		$add_resp = $curl->exec();
		$curl->close();
		
		$dom = new DOMDocument;
		@$dom->loadHTML($add_resp);
		
		$xp = new DOMXpath($dom);
		
		if(!preg_match('/\/question\/(.*)[\/]?/', $xp->query('//p[@class="bypass"]/a/@href')->item(0)->nodeValue, $matches)) {
			throw new Exception('Could not create wedgie');
		}
		$wedgie_id = $matches[1];
		
		return $wedgie_id;
	}
	
	//UnAuthed request
	public function get_wedgie($wedgie_id) {
		
		$curl = new Curl('https://www.wedgies.com/ajax/question/'.urlencode($wedgie_id));
		
		if(!$curl) {
			throw new Exception('Could not create Curl object.');
		}
		
		$curl->useragent = $this->user_agent;
		$curl->followlocation = true;
		
		$wedgie_resp = $curl->exec();
		$curl->close();
		
		$result_object = json_decode($wedgie_resp);
		
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
	public function get_wedgies($page = 1) {
		
		if(!$this->is_authed()) {
			$this->auth();
		}
	
		$curl = new Curl('https://www.wedgies.com/user?page='.urlencode($page));
		
		if(!$curl) {
			throw new Exception('Could not create Curl object.');
		}
		
		$curl->useragent = $this->user_agent;
		$curl->cookiesession = true;
		$curl->cookiefile = realpath($this->cookie_file);
		$curl->followlocation = true;
		
		$wedgies_resp = $curl->exec();
		$curl->close();
		
		$dom = new DOMDocument;
		@$dom->loadHTML($wedgies_resp);
		
		$xp = new DOMXpath($dom);
		
		$wedgies = false;
		
		foreach($xp->query('//ul[@class="question-list"]/li[@class="question"]/p[@class="question-text"]/a') as $question_item) {
			$wedgie_id = false;
			if(preg_match('/\/question\/(.*?)\//', $question_item->attributes->item(0)->nodeValue, $matches)) {
				$wedgie_id = $matches[1];
			}
			$wedgies[] = array('question' => $question_item->nodeValue, 'id' => $wedgie_id);
		}
	
		return $wedgies;
	}
	
	//Authed request
	public function get_amount_of_wedgies_pages() {
		
		if(!$this->is_authed()) {
			$this->auth();
		}
	
		$curl = new Curl('https://www.wedgies.com/user');
		
		if(!$curl) {
			throw new Exception('Could not create Curl object.');
		}
		
		$curl->useragent = $this->user_agent;
		$curl->cookiesession = true;
		$curl->cookiefile = realpath($this->cookie_file);
		$curl->followlocation = true;
				
		$wedgie_pages_resp = $curl->exec();
		$curl->close();
				
		$dom = new DOMDocument;
		@$dom->loadHTML($wedgie_pages_resp);
		
		$xp = new DOMXpath($dom);
	
		$pages = preg_replace('/Page [0-9] of /', '', $xp->query('//div[@class="pagination"]/span[@class="page"]')->item(0)->nodeValue);
		
		if(empty(trim($pages))) {
			return false;
		}
		
		return $pages;
	}

}