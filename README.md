# micropub.rocks
Micropub test suite and debugging utility

# Installation

1. Clone repo
2. Run `composer install`
3. Create a new database for the test data: `mysql -u [user] -p[passwd] -e "create database micropubrocks; GRANT ALL PRIVILEGES ON micropubrocks.* TO [micropubrocks_user]@localhost IDENTIFIED BY '[micropubrocks_passwd]'"`.
4. Import the database data:
    ```
    mysql -u [user] -p[pass] micropubrocks < database/schema.sql
    mysql -u [user] -p[pass] micropubrocks < database/data.sql
    ```
5. Copy `lib/config.template.php` to `lib/config.php`
6. Adjust configuration in `lib/config.php` to suit your needs.
7. Create vhost and point the document root to the directory you cloned.  I installed to `${HOME}/Sites/micropub.rocks` so used the following vhost config:

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

8. Create a `.htaccess` in the directory you cloned the repo into and add the following to it:

    ```
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ public/index.php [QSA,L]
    ```
