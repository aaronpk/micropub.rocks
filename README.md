# micropub.rocks
Micropub test suite and debugging utility

# Installation

## Automatically-ish

I'm lazy, so I've created a script in `script/bootstrap` which does most of the manual steps automatically for you.

1. Clone repo
2. Copy `lib/config.template.php` to `lib/config.php` and customise to suit your needs.
3. Run `script/bootstrap` - this will prompt you for your MySQL root user password as we need to create the micropubrocks users.
4. Create vhost and point the document root to the directory you cloned as shown in the output from `script/bootstrap`.

You can run this script every time you update this repo but be warned, it is destructive as the upstream repo doesn't provide database migrations yet.

## Manually

1. Clone repo
2. Run `composer install`
3. Copy `lib/config.template.php` to `lib/config.php`
4. Adjust configuration in `lib/config.php` to suit your needs.
5. Create the new micropubrocks user: `mysql -u [user] -p[passwd] -e "create user if not exists 'micropubrocks'@'localhost' identified by 'micropubrocks_passwd'"`
6. Create a new database for the test data: `mysql -u [user] -p[passwd] -e "create database micropubrocks; GRANT ALL PRIVILEGES ON micropubrocks.* TO [micropubrocks_user]@localhost IDENTIFIED BY '[micropubrocks_passwd]'"`.
7. Import the database data:
    ```
    mysql -u [user] -p[pass] micropubrocks < database/schema.sql
    mysql -u [user] -p[pass] micropubrocks < database/data.sql
    ```
8. Create vhost and point the document root to the directory you cloned.  I installed to `${HOME}/Sites/micropub.rocks` so used the following vhost config:

    ```
    <VirtualHost *:8088>
      DocumentRoot "/Users/lildude/Sites/micropub.rocks/public"
      ServerName micropubrocks.dev
      <Directory /Users/lildude/Sites/micropub.rocks/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
      </Directory>
    </VirtualHost>
    ```

9. Create a `.htaccess` in the directory you cloned the repo into and add the following to it:

    ```
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ public/index.php [QSA,L]
    ```
