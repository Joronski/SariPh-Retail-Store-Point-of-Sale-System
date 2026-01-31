<?php
    // Application Configuration Settings
    define('APP_NAME', 'SariPh POS System');
    define('APP_VERSION', '1.0.0');
    define('TIMEZONE', 'Asia/Manila');

    // Server Configuration Settings
    define('APACHE_PORT', '8080');
    define('MYSQL_PORT', '3307');
    define('BASE_URL', 'http://localhost:8080/sariph-pos');

    // Setting timezone configuration
    date_default_timezone_set(TIMEZONE);

    // Session Configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // You should set this to 1 if using HTTPS

    // Discount rates (in percentage)
    define('DISCOUNT_SENIOR', 20);
    define('DISCOUNT_PWD', 20);
    define('DISCOUNT_ATHLETE', 20);
    define('DISCOUNT_SOLO_PARENT', 20);

    // Receipt Settings Configuration
    define('STORE_NAME', 'SariPh Retail Store');
    define('STORE_ADDRESS', 'Katapatan Mutual Homes, Brgy. Banay-banay, City of Cabuyao, Laguna 4025');
    define('STORE_CONTACT', 'Tel: (02) 1234-5678');
    define('STORE_TIN', 'TIN: 123-456-789-000');

    // Pagination Settings Configuration
    define('ITEMS_PER_PAGE', 10);
?>