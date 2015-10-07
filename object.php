<?php

$data = array(
	'title' => array(
		'media' => array(
			'url' => "{{ static_url }}/img/face.jpg",
		),
		'text' => array(
	          "headline" => "Experience & Education",
	          "text" => "<p>A visual history of my serpentine career path.  A broad swath of experience that's left me remarkably adaptable, capable, and ready to suceed. </p>",
		)
	),

    foreach($jobs as $job){
    	'events' => array(
    		'media' => array(
                'url' => "",
                'credit' => "",
            ),
            'start_date' => array(
                'month': "",
                'year' : "",
            ),
            'end_date' => array(
                'month': "",
                'year' : "",
            ),
            'text' => array(
                'headline'  : "",
                'text'      : "",
            ),
    	),
    }
);

?> 