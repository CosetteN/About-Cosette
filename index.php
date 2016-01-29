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

	/**
	* enable Slim's middleware to allow for Flash.  Takes place of PHP
	* Session_startS $app->add(new \Slim\Middleware\SessionCookie());
	*/
	$app->add(new \Slim\Middleware\SessionCookie());

	/**
	* Allows Twig helpers for internal & html routing.  urlFor, siteUrl, baseUrl
	* & currentUrl
	*/
	$view->parserExtensions = array(new \Slim\Views\TwigExtension(),);

	/* pull in database credentials */
	$dsn = 'mysql:dbname=' . DB_NAME . ';host=' . DB_HOST;
	$db = new PDO($dsn, DB_USER, DB_PASSWORD);

	/* Path for the home page.  So it doesn't read index.php. */
	$app->get('/', function() use($app){$app->render('cosette.twig');})
		->name('home');

		/* Path for the details page.  Build object to become timeline. */
	$app->get('/details', function() use($app, $db)
	{
		$events = array();

		if ($db) {
			$query = $db->prepare(
				"SELECT *
					FROM resume"
			);

			if($query->execute())
			{
				$rows = $query->fetchALL();

				/* Pull in resume details from the website. */
				foreach($rows as $row) {
					$start 		= $row['start'];
					$end 		= $row['end'];
					$time 		= $row['time_spent'];
					$headline 	= $row['position'];
					$company 	= $row['company'];
					$image 		= $row['image'];
					$text1 		= $row['detail_1'];
					$text2 		= $row['detail_2'];
					$text3 		= $row['detail_3'];
					$text4 		= $row['detail_4'];

					$events[] = array(
							'media' => array( 'url' => $image ),
					        'start_date' => array( 'year' => $start ),
					        'end_date' => array( 'year' => $end ),
					        'text' => array(
					            'headline'  => $headline . " at " . $company,
					            'text'      => $text1 . "</br> " . $text2 . "</br> "
					            	. $text3 . "</br> " . $text4,
					        ),
					);
				}
		    }
		/* Title and 'fixed' portion of json object for timeline. */
		$data = array(
			'title' => array(
				'media' => array(
					'url' => "img/face.jpg",
				),
				'text' => array(
			          "headline" => "Experience & Education",
			    )
			),
			'events' => $events,
		);

		/* Turn resume array into a json string. */
		$timeline = json_encode($data);
	}

	$app->render('details.twig', array('timeline' => $timeline));
	})->name('details');

	/**
	* Path for the contact page, database call to insert random quote at bottom
	* of contact form.
	*/
	$app->get('/contact', function() use($app, $db)
	{
		$quote_id = rand(1, 16);

		if ($db) {
			$query = $db->prepare(
				"SELECT quote, author
					FROM quotes
					WHERE quote_id = :ID"
			);

			$query->bindParam(":ID", $quote_id);

			if($query->execute())
			{
				$row = $query->fetch();
				$quote = $row['quote'];
				$author = $row['author'];

			}
		}

	$app->render('contact.twig', array(
			'quote' => '"' . $quote . '"',
			'author' => $author
	));

	})->name('contact');

	// Get post data from the contact form //
	$app->post('/contact', function() use($app, $db){
		$name = trim($app->request->post('name'));
		$email = trim($app->request->post('email'));
		$msg = trim($app->request->post('msg'));

		/* If every field was filled, sanitize them all. */
		if(!empty($name) && !empty($email) && !empty($msg)){
			$cleanName = filter_var($name, FILTER_SANITIZE_STRING);
			$cleanEmail = filter_var($email, FILTER_SANITIZE_EMAIL);
			$cleanMsg = filter_var($msg, FILTER_SANITIZE_STRING);

			if ($db) {
				$query = $db->prepare(
					"INSERT INTO contacts (name, email, message)
						VALUES (:name, :email, :msg);"
				);
			}
			/* Insert details into contacts database as back up to email sent. */
			$query->bindParam(':name', $cleanName);
			$query->bindParam(':email', $cleanEmail);
			$query->bindParam(':msg', $cleanMsg);

			$query->execute();

		/* If any field is blank*/
		} else {
			/*
			* send message about incomplete fields and send back to contact form.
			* Flash message info is in flash.twig
			* {% include 'flash.twig' %} is in main.twig at start of body.
			*/
			$app->flash('fail', "Your name, a complete email address, and
				the reason for your reaching out are all required for us to make a
				meaingful connection");
			$app->redirect('contact');
		}

		/* details for email to be sent */
		$transport = Swift_SmtpTransport::newInstance('smtp.gmail.com', 465, 'ssl')
				->setUsername('placeholder')
				->setPassword('placeholder');
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
			$app->flash('fail', "Something went wrong and your message didn't send. "
				. "Please try again later.");
			$app->redirect('contact');
		}
	});

$app->run();
