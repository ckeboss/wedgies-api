Wedgies API
======

You can use this api to interact with wedgies (https://www.wedgies.com) programmatically.

This is unofficial, and can break at any time. It's not fully featured, but has most of what people need.

Currently, it only support twitter login. If you would like different authentication types (facebook), please post an issue, or a pull request.

- get_wedgie($wedgie_id). Returns wedgie question, and an array of options including votes for those options, and total votes
- get_wedgies($page = 1). You must be logged in for this. Returns list of wedgies for the current logged in account. 10 per page.
- get_amount_of_wedgies_pages(). You must be logged in for this. Returns list of wedgies for the current logged in account. 10 per page.
- add_wedgie($question = null, $options = null, $config = null). You must be logged in for this. Pass params of question, and an array of options. Returns created wedgie id
- edit_wedgie($wedgie_id = null, $question = null, $options = null, $config = null). You must be logged in for this. Pass params of wedgie_id, updated question name, and a key => value array of options. To add options, just leave the key blank. Recommend using this in conjonction with read wedgie to get the data you need.

Constuctor is
		$wedgies_api = new WedgiesAPI('username', 'password', 'custom_user_agent', 'relative/cookie_file_location <- make sure it is writable');

Examples
-----
		try {
			$wedgies_api = new WedgiesAPI('username', 'password', 'My App v0.01', 'cookies/auth_cookie');
			
			//Add a wedgie
			$wedgie_resp = $wedgies_api->add_wedgie('Should wedgies have an offical API?', array('Yes', 'No'));
			
			//Edit a wedgie
			$wedgie_edit_resp = $wedgies_api->edit_wedgie('wedgie_id', 'qustion text', array('option_id' => 'option_text', 'option_id' => 'option_text', 'New option'));
			
			//Get page 1 of wedgies
			$wedgies = $wedgies_api->get_wedgies(1);
	
			//Get number of pages of wedgies
			$page_count = $wedgies_api->get_amount_of_wedgies_pages();
			
			//Get single wedgie
			$wedgie = $wedgies_api->get_wedgie('52fd71798e30900200000593');
		} catch (Exception $e) {
			echo $e->getMessage();
		}

Improvements
-----
Let me know if you would like to see any additional features. Feel free to submit a pull request