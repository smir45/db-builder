### db-builder

[![Latest Stable Version](http://poser.pugx.org/ryzen/db-builder/v)](https://packagist.org/packages/ryzen/db-builder) 
[![Total Downloads](http://poser.pugx.org/ryzen/db-builder/downloads)](https://packagist.org/packages/ryzen/db-builder) 
[![Latest Unstable Version](http://poser.pugx.org/ryzen/db-builder/v/unstable)](https://packagist.org/packages/ryzen/db-builder) 
[![License](http://poser.pugx.org/ryzen/db-builder/license)](https://packagist.org/packages/ryzen/db-builder)

##### Powerful, Fast, Efficient and useful Database Query Builder and PDO

### Install

Include following block of code to your compose.json file

```json
{
    "require": {
        "ryzen/db-builder": "^1.0.0"
    }
}
```

Now, run the following command in your Terminal
```
$ composer update
```

Finally, Import the auto loader and create new object with configuration
```php
<?php

include 'vendor/autoload.php';

$config = array(
    'mysql_database_config' =>
        [
            'db_host'       => "YOUR_DATABASE_HOST",
            'db_name'       => "YOUR_DATABASE_NAME",
            'db_user'       => "YOUR_DATABASE_USER",
            'db_pass'       => "YOUR_DATABASE_PASS",
            'db_driver'     => 'mysql',
            'db_charset'    => 'utf8',
            'db_collation'  => 'utf8_general_ci',
            'db_prefix'     => ''
        ],
);

$db =  new \Ryzen\DbBuilder\DbBuilder($config);

# Example to use

print_r($db->table('users')->select('*')->getAll());
```

### Documentation
<a href="https://docs.8beez.com/dbbuilder">db-builder Docs</a>

### Support Center
<a href="https://docs.8beez.com/support">Ry-Zen Support</a>

### Contributors
<a href="https://rajuchoudhary.com.np/"> Raju Choudhary </a> 😎 - Creator and Maintainer
