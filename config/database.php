<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */
	
    'connections' => [
		
		#八方
		'BFPosErp' => [
            'driver' => 'sqlsrv',
            #'url' => env('DATABASE_URL'),
            'host' => env('BF_DB_HOST', '65.52.163.96'),
            'port' => env('BF_DB_PORT', '1433'),
            'database' => env('BF_DB_DATABASE', 'poserp'),
            'username' => env('BF_DB_USERNAME', 'sa'),
            'password' => env('BF_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'encrypt' => env('DB_ENCRYPT', 'yes'),
            'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'true'),
			'collation' => 'Chinese_PRC_CI_AS', 
        ],
		#梁社漢
		'BGPosErp' => [
            'driver' => 'sqlsrv',
            #'url' => env('DATABASE_URL'),
            'host' => env('BG_DB_HOST', '65.52.163.96'),
            'port' => env('BG_DB_PORT', '1433'),
            'database' => env('BG_DB_DATABASE', 'poserp_chop'),
            'username' => env('BG_DB_USERNAME', 'sa'),
            'password' => env('BG_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'encrypt' => env('DB_ENCRYPT', 'yes'),
            'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'true'),
			'collation' => 'Chinese_PRC_CI_AS', 
        ],
		#芳珍
		'FJPosErp' => [
            'driver' => 'sqlsrv',
            #'url' => env('DATABASE_URL'),
            'host' => env('FJ_DB_HOST', '65.52.163.96'),
            'port' => env('FJ_DB_PORT', '1433'),
            'database' => env('FJ_DB_DATABASE', 'poserp_Vegetable'),
            'username' => env('FJ_DB_USERNAME', 'sa'),
            'password' => env('FJ_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'encrypt' => env('DB_ENCRYPT', 'yes'),
            'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'true'),
			'collation' => 'Chinese_PRC_CI_AS', 
        ],
		
		'SalesDashboard' => [
            'driver' => 'mysql',
            'host' => env('LSD_DB_HOST', '127.0.0.1'),
            'port' => env('LSD_DB_PORT', '3306'),
            'database' => env('LSD_DB_DATABASE', 'staging_sales_dashboard'),
            'username' => env('LSD_DB_USERNAME', 'staging_sales_dashboard'),
            'password' => env('LSD_DB_PASSWORD', ''),
            'fetch' => PDO::FETCH_ASSOC,
        ],
		
		#Local pos order
		'PosStatistics' => [
            'driver' => 'mysql',
            'host' => env('LPOS_DB_HOST', '127.0.0.1'),
            'port' => env('LPOS_DB_PORT', '3306'),
            'database' => env('LPOS_DB_DATABASE', 'pos_statistics'),
            'username' => env('LPOS_DB_USERNAME', 'salesdashboard'),
            'password' => env('LPOS_DB_PASSWORD', ''),
            'fetch' => PDO::FETCH_ASSOC,
        ],
		
		'RemoteSaleDashboard' => [
            'driver' => 'sqlsrv',
            'host' => env('SD_DB_HOST', '192.168.1.237'),
            'port' => env('SD_DB_PORT', '1433'),
            'database' => env('SD_DB_DATABASE', 'SaleDashbaord'),
            'username' => env('SD_DB_USERNAME', 'sa'),
            'password' => env('SD_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
			'fetch' => PDO::FETCH_ASSOC,
        ],
		#舊訂貨系統
		'OrderTP' => [
            'driver' => 'sqlsrv',
            #'url' => env('DATABASE_URL'),
            'host' => env('OTP_DB_HOST', '34.80.39.82'),
            'port' => env('OTP_DB_PORT', '1433'),
            'database' => env('OTP_DB_DATABASE', 'OrderTP'),
            'username' => env('OTP_DB_USERNAME', 'OrderSystem'),
            'password' => env('OTP_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'encrypt' => env('DB_ENCRYPT', 'yes'),
            'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'true'),
        ],
		'OrderKH' => [
            'driver' => 'sqlsrv',
            #'url' => env('DATABASE_URL'),
            'host' => env('OKH_DB_HOST', '34.80.39.82'),
            'port' => env('OKH_DB_PORT', '1433'),
            'database' => env('OKH_DB_DATABASE', 'OrderKH'),
            'username' => env('OKH_DB_USERNAME', 'OrderSystem'),
            'password' => env('OKH_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'encrypt' => env('DB_ENCRYPT', 'yes'),
            'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'true'),
        ],
		'OrderTS' => [
            'driver' => 'sqlsrv',
            #'url' => env('DATABASE_URL'),
            'host' => env('OTS_DB_HOST', '34.80.39.82'),
            'port' => env('OTS_DB_PORT', '1433'),
            'database' => env('OTS_DB_DATABASE', 'OrderTS'),
            'username' => env('OTS_DB_USERNAME', 'OrderSystem'),
            'password' => env('OTS_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'encrypt' => env('DB_ENCRYPT', 'yes'),
            'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'true'),
        ],
		'OrderRL' => [
            'driver' => 'sqlsrv',
            #'url' => env('DATABASE_URL'),
            'host' => env('ORL_DB_HOST', '34.80.39.82'),
            'port' => env('ORL_DB_PORT', '1433'),
            'database' => env('ORL_DB_DATABASE', 'OrderRL'),
            'username' => env('ORL_DB_USERNAME', 'OrderSystem'),
            'password' => env('ORL_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'encrypt' => env('DB_ENCRYPT', 'yes'),
            'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'true'),
        ],
		'NewOrder' => [
            'driver' => 'sqlsrv',
            'host' => env('NO_DB_HOST', 'bafang-prod-failover.database.windows.net'),
			'port' => env('NO_DB_PORT', '1433'),
            'database' => env('NO_DB_DATABASE', '8way-order-system-prod'),
            'username' => env('NO_DB_USERNAME', 'baway-usertristan'),
            'password' => env('NO_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'trust_server_certificate' => env('NO_DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],
		
		#八方點
		'QuickOrder' => [
            'driver' => 'sqlsrv',
            'host' => env('QO_DB_HOST', 'bafangquickorder-mssql-server.database.windows.net'),
			'port' => env('QO_DB_PORT', '1433'),
            'database' => env('QO_DB_DATABASE', 'QuickerOrder'),
            'username' => env('QO_DB_USERNAME', 'quickerorder-user'),
            'password' => env('QO_DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'trust_server_certificate' => env('NO_DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],
		
		#春節預購更新car no(暫時放在此, 不設env)
		'LunarCarNo' => [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
			'database' => env('CARNO_DB_DATABASE', 'lunar_year_carno'),
            'username' => env('CARNO_DB_USERNAME', 'new_year_car_temp'),
            'password' => env('CARNO_DB_PASSWORD', 'ukLP]PLCQc8nfEuO'),
            'fetch' => PDO::FETCH_ASSOC,
        ],
		
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],
/*
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],*/

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
            'persistent' => env('REDIS_PERSISTENT', false),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
