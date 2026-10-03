# micropub.rocks
Micropub test suite and debugging utility

## Setup
* Requires PHP 8.2 or newer, Redis and MySQL
* Install composer https://getcomposer.org/
* Run `composer install` from the project folder
* Copy `lib/config.template.php` to `lib/config.php` and fill in the details for your Redis and MySQL instance
* Run the SQL in `database/schema.sql` and `database/data.sql` to initialize the database
* Run `php -S localhost:8080 public/index.php` to start the built in web server
* Visit http://localhost:8080

## Signing in

micropub.rocks uses passkeys to sign in. Passkeys are bound to the hostname in `Config::$base`, and only work when it's `https://` or `http://localhost`. Changing the hostname later means everyone has to register new passkeys. To run somewhere passkeys can't work, set `$skipauth` to sign in as any email address.

Accounts that signed in with an emailed link before the move to passkeys can keep doing so until `Config::$email_login_ends` (March 1, 2027), and are asked to add a passkey when they do. After that date email sign-in turns off automatically. Sending those links uses the Mailgun settings in the config.

## Upgrading an existing install

Apply the numbered migrations in `database/` that you haven't run yet. `0001.sql` adds the tables for passkey login.

## Removing unused accounts

`php scripts/delete-junk-users.php` reports users with no endpoints, clients or passkeys who haven't signed in for 30 days. Run it with `--delete` to delete them, or `--days=N` to change the grace period.
