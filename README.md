reckoner-php
============

a light php rest micro framework,
now with groups!

this is an attempt of making a faster, identical php "slim" router
you can also set custom http methods thru some php magic!

example:
    $app->get('/hi', function() {
        echo 'hello world';
    });
    
    // custom MYOPTIONS method
    $app->myoptions('/api', function() {});
    
    $app->group('/group', function() use ($app) {
        echo 'i am in a group!';
        $app->get('/hello', function() {
            echo "hello in group!";
        });
    });