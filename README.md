# micropub.rocks
Micropub test suite and debugging utility

## Installation

**⚠️ This works in my env on macOS. Your experience may vary. Don't come 😭 to me if something breaks ⚠️**

These instructions assume you're using the OS-provided Apache.

### Automatically-ish

I'm lazy, so I've created a script in `script/bootstrap` which does most of the manual steps automatically for you.

1. Clone repo
2. Copy `lib/config.template.php` to `lib/config.php` and customise to suit your needs.
3. Run `script/bootstrap` - this will prompt you for your MySQL root user password as we need to create the micropubrocks DB and users.
4. Create vhost and point the document root to the directory you cloned as shown in the output from `script/bootstrap`.

You can run this script every time you update this repo but be warned, it is destructive as the upstream repo doesn't provide database migrations yet.

### Manually

1. Clone repo
2. Run `composer.phar install`
3. Copy `lib/config.template.php` to `lib/config.php` and customise to suit your needs
4. Create the new micropubrocks user: `mysql -u [user] -p[passwd] -e "create user if not exists 'micropubrocks'@'localhost' identified by 'micropubrocks_passwd'"`
5. Create a new database for the test data: `mysql -u [user] -p[passwd] -e "create database micropubrocks; GRANT ALL PRIVILEGES ON micropubrocks.* TO [micropubrocks_user]@localhost IDENTIFIED BY '[micropubrocks_passwd]'"`.
6. Import the database data:

    ```console
    mysql -u [user] -p[pass] micropubrocks < database/schema.sql
    mysql -u [user] -p[pass] micropubrocks < database/data.sql
    ```

7. Create vhost and point the document root to the directory you cloned, replacing the variables to suit your env (you set most of this in the `lib/config.php`):

    ```
    <VirtualHost *:$PORT>
      DocumentRoot "$BASE_PATH/public"
      ServerName $SITENAME
      <Directory $BASE_PATH>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
      </Directory>
    </VirtualHost>
    ```

8. Create a `.htaccess` in the directory you cloned the repo into and add the following to it:

    ```htaccess
    <IfModule mod_rewrite.c>
      RewriteEngine On
      RewriteBase /
      RewriteRule ^index\.php$ - [L]
      RewriteCond %{REQUEST_FILENAME} !-f
      RewriteCond %{REQUEST_FILENAME} !-d
      RewriteRule . /index.php [L]
    </IfModule>
    ```
