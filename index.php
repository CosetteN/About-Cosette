<?php
	require 'vendor/autoload.php';
	require 'db.php';
	date_default_timezone_set('America/New_York');

	/**
	* Calls the Namespace Slim & the class slim from vendor/slim/slim/Slim then 
	* passes teh array of Twig views in to override the Slim view. Make pretty
	* Urls.
	*/
	$app = new \Slim\Slim( array('view' => new \Slim\Views\Twig()));

	/* Allow Twig options */
	$view = $app->view();
	$view->parserOptions = array('debug' => true,);

	/* 
	* enable Slim's middleware to allow for Flash.  Takes place of PHP 
	* Session_startS $app->add(new \Slim\Middleware\SessionCookie()); 
	*/
	$app->add(new \Slim\Middleware\SessionCookie());

	/*
	* Allows Twig helpers for internal & html routing.  urlFor, siteUrl, baseUrl
	* & currentUrl
	*/
	$view->parserExtensions = array(new \Slim\Views\TwigExtension(),);

	/* pull in database credentials */
	$dsn = 'mysql:dbname=' . DB_NAME . ';host=' . DB_HOST;
	$db = new PDO($dsn, DB_USER, DB_PASSWORD);

	/*
	* Path for the home page.  So it doesn't read index.php.
	*/
	$app->get('/', function() use($app){$app->render('cosette.twig');})
		->name('home');

	/* 
	* Path for the contact page, database call to insert random quote at bottom
	* of contact form. 
	*/
	$app->get('/contact', function() use($app, $db){
		$quote_id = rand(1, 16);

		$results = $db->query(
			"SELECT quote, author 
				FROM quotes 
				WHERE quote_id 	= '$quote_id'"
		);

		$app->render('contact.twig', array(
			'quote'=> $results['0'],
			'author' => $results['1'])
		);
	})->name('contact');

	/* Path for the details page.  Build object to become timeline. */
	$app->get('/details', function() use($app){$app->render('details.twig');})
		->name('details');
	$data = array();

	// /* 
	// * Timeline has static title info and dynamic 
	// * (from database) job details. (Store image path, not images in database. 
	// */
	// $data['title'] = array(
	// 	'blah' =>'blah',
	// );

	// $data['events'] = array();

	// //db stuff

	// $foreach ($results as $result) {
	// 	//build array
	// 	$data['events'][] = array();

	// }
	// /* turn object into a string for timeline */
	// $timeline = json__encode($data);

	// Get post data from the contact form //
	$app->post('/contact', function() use($app){
		$name = $app->request->post('name');
		$email = $app->request->post('email');
		$msg = $app->request->post('msg');

		if(!empty($name) && !empty($email) && !empty($msg)){
			$cleanName = filter_var($name, FILTER_SANITIZE_STRING);
			$cleanEmail = filter_var($email, FILTER_SANITIZE_EMAIL);
			$cleanMsg = filter_var($msg, FILTER_SANITIZE_STRING);

		// } else if(empty($name) && !empty($email) && !empty($msg)) {
		// 	/*
		// 	* send message about incomplete fields and send back to contact form.
		// 	* Flash message info is in flash.twig
		// 	* {% include 'flash.twig' %} is in main.twig at start of body.
		// 	*/
		// 	$app->flash('fail', 'Anonymous is for hacking, not making connections.' 
		// 		'Your name please. ');
		// 	$app->redirect('contact');
		
		// } else if(!empty($name) && empty($email) && !empty($msg)) {
		// 	$app->flash('fail', "I'll need an actual email address to answer you.");
		// 	$app->redirect('contact');

		// } else if(!empty($name) && !empty($email) && empty($msg)) {
		// 	$app->flash('fail', "You forgot your words of wisdom!  Fill in the "
		// 	. "message field please!" );
		// 	$app->redirect('contact');

		} else {
			$app->flash('fail', 'Little trigger happy with the go button there '
				. "buddy?  Try again. Fill all the fields please.");
		$app->redirect('contact');
		}

		/* details for email to be sent */
		$transport = Swift_SmtpTransport::newInstance(
			'smtp.gmail.com', 
			465, 
			'ssl'
			)
				->setUsername('elladelille')
				->setPassword('nepeeykxfldupduk');
		$mailer = Swift_Mailer::newInstance($transport);

		$message = Swift_Message::newInstance()
			->setSubject('Email from Cosette Website')
			->setFrom(array($cleanEmail => $cleanEmail))
			->setTo(array('elladelille@gmail.com'))
			->setBody ($cleanMsg);

		// send message & ensure it was sent //
		$result = $mailer->send($message);

		if($result > 0) {
			
			// Send message confirming success & route back to about page //
			$app->flash('success', "Thanks. Can't wait to read it!");
			$app->redirect(' ');
		} else {
			// Send a message that email failed to send & log as error //
			$app->flash('fail', "Something went wrong and your message didn't "
				. "send.  Please try again later.");
			$app->redirect('contact');	
		}
	});		

$app->run();
