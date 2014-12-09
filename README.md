reckoner-php
============

a light php rest micro framework,
now with groups!

this is an attempt of making a faster, identical php "slim" router
you can also set custom http methods thru some php magic!

example:

	// parameter support
	$app->get('/hello/:name', function($name) {
	    echo 'hello ' . $name;
	});

	// faster parameter support
	$app->get_p('/hello/for/:p', function($name) {
	    echo 'hello ' . $name;
	});
    
	// custom MYOPTIONS method
	$app->myoptions('/api', function() {});

	// groups
	$app->group('/group', function() use ($app) {
		echo 'i am in a group!';
		$app->get('/hello', function() {
		    echo "hello in group!";
		});
	});