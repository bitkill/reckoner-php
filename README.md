reckoner-php
============

a light php rest micro router class,
now with groups!

this is an attempt of making a faster, identical php "slim" router
you can also set custom http methods thru some php magic!

example:

	<?php
	// parameter(s) support
	$app->get('/hello/:name', function($name) {
	    echo 'hello ' . $name;
	});

	// fast parameter(s) support
	$app->get_p('/hello/for/:p(/:p)', function($name, $surname ="") {
	    echo 'hello ' . $name . ' ' . $surname;
	});
    
	// custom MYOPTIONS method
	$app->myoptions('/api/:var', function($var) {});

	// groups
	$app->group('/group', function() use ($app) {
		echo 'i am in a group!';
		$app->get('/hello', function() {
		    echo "hello in group!";
		});
	});