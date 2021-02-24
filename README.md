# micropub.rocks
Micropub test suite and debugging utility

## Setup
* Install Redis and MySQL
* Install composer https://getcomposer.org/
* Run `composer install` from the project folder
* Copy `lib/config.template.php` to `lib/config.php` and fill in the details for your Redis and MySQL instance
* Run the SQL in `database/schema.sql` and `database/data.sql` to initialize the database
* Run `php -S 127.0.0.1:8080 -t public` to start the built in web server
* Visit http://127.0.0.1:8080

