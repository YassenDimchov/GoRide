Local development is done on Windows using XAMPP with Apache and MySQL.
The Laravel backend is served via an Apache Virtual Host pointing to the backend/public directory and accessed at http://goride.local.
To shorten the URL, a local domain is created by adding a Virtual Host in Apache and mapping it in the system hosts file.
This allows the application to be accessed without including /public in the URL.
The MySQL database go_ride is configured in the Laravel .env file, while sessions and cache are stored using the file driver.