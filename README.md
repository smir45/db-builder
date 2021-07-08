## db-builder
Powerful, Optimized Query Builder For Your PHP projects

## Installing Library Using Composer
`$ composer require ryzen/db-builder`

## Setting Things UP
The very first thing to do is loading the autoload

`include 'vendor/autoload.php';`

Creating New Configuration File

```php
$config = array(
    'mysql_database_config' =>
        [
            'db_host'       => "YOUR_DATABASE_HOST",
            'db_name'       => "YOUR_DATABASE_NAME",
            'db_user'       => "YOUR_DATABASE_USER",
            'db_pass'       => "YOUR_DATABASE_PASS",
            'db_driver'	    => 'mysql',
            'db_charset'    => 'utf8',
            'db_collation'  => 'utf8_general_ci',
            'db_prefix'	    => ''
        ],
);
```

## The Final Step
```php
$db  = new \Ryzen\DbBuilder\DbBuilder($config);
```
## Query Builders
##### Select
```php
$db->table('users')->select('name, age')->getAll();
$db->select('title AS t, content AS c')->table('test')->getAll();
```
##### Select with (min, max, sum, avg, count)
```php
$db->table('test')->max('price')->get();
$db->table('test')->count('id', 'total_row')->get();
```
##### Select With Where Clause
```php
# where in Array
# select * from test where id = 1 AND status = 0;
$where = ['id' => 1,'status' => 0];
$db->table('test')->where($where)->get();

# where
# select * from test where active = 1;
$db->table('test')->where('active', 1)->getAll();

# with operator
# select * from test where salary >= 18;
$db->table('test')->where('salary', '>=', 18)->getAll();

# Custom Where
# select * from test where age = 18 OR age = 20;
$db->table('test')->where('age = ? OR age = ?', [18, 20])->getAll();

# notWhere
# 'Select * from test where active = 1 AND NOT auth = 1';
$db->table('test')->where('active', 1)->notWhere('auth', 1)->getAll();

# orWhere
# select * from test where age = 20 OR age > 25;
$db->table('test')->where('age', 20)->orWhere('age', '>', 25)->getAll();

# whereNotNull
# select * from test where email IS NOT NULL;
$db->table('test')->whereNotNull('email')->getAll();
```
##### Grouped
```php
# SELECT * FROM users WHERE (country='TURKEY' OR country='ENGLAND') AND status ='1'
$db->table('users')->grouped(function($q) {
		$q->where('country', 'TURKEY')->orWhere('country', 'ENGLAND');
	})->where('status', 1)->getAll();
```
##### In
```php
# Use Cases 'in' 'notIn' 'orIn';
# SELECT * FROM test WHERE active = '1' AND id IN ('1', '2', '3')
$db->table('test')->where('active', 1)->in('id', [1, 2, 3])->getAll();
```
##### Between
```php
# Use Cases 'between' 'orBetween' 'notBetween';
# SELECT * FROM test WHERE active = '1' AND age BETWEEN '18' AND '25'
$db->table('test')->where('active', 1)->between('age', 18, 25)->getAll();
```

##### Like
```php
# Use Cases 'like' 'orlike' 'notLike';
# "SELECT * FROM test WHERE title LIKE '%php%';
$db->table('test')->like('title', "%php%")->getAll();
```
##### Group By 
```php
# SELECT * FROM test WHERE status = '1' GROUP BY cat_id;
$db->table('test')->where('status', 1)->groupBy('cat_id')->getAll();
```

##### Having
```php
# SELECT * FROM test WHERE status='1' GROUP BY city HAVING COUNT(person) > '100'
$db->table('test')->where('status', 1)->groupBy('city')->having('COUNT(person)', 100)->getAll();
```

##### OrderBy
```php
# orderBy('id') default ASC;
# SELECT * FROM test WHERE status='1' ORDER BY id desc
$db->table('test')->where('status', 1)->orderBy('id','desc')->getAll();
```
##### LIMIT
```php
# SELECT * FROM test LIMIT 10
$db->table('test')->limit(10)->getAll();

# SELECT * FROM test LIMIT 10, 20
$db->table('test')->limit(10, 20)->getAll();

# SELECT * FROM test LIMIT 10 OFFSET 10
$db->table('test')->limit(10)->offset(10)->getAll();
```
##### Insert
```php
# INSERT INTO users (name,email) VALUES ('Hari Bahadut', 'haribahadur@domain.com')
$query = $db->table('users')->insert([
            'name'  => 'Hari Bahadut',
            'email' => 'haribahadur@domain.com'
            ]);
```
##### Update
```php
# UPDATE users SET `name` = 'Madan Krishna',`email` = 'madan@madan.com' WHERE `id` = 1;
$query = $db->table('users')->where('id', 1)->update([
            'name'  => 'Madan Krishna',
            'email' => 'madan@madan.com',
            ]);
```
##### Delete
```php
# DELETE FROM users WHERE type = 'deactivated';
$query = $db->table('users')->where('type', 'deactivated')->delete();
```
##### Transaction Query
```php
# Initialize transaction 
$db->transaction();

# Process
$data = ['title' => 'new title','status' => 2];
$db->table('test')->where('id', 10)->update($data);

# Perform
$db->commit();  /*OR*/ $db->rollBack();
```
##### Analyze
```php
# ANALYZE TABLE users"
db->table('users')->analyze();
```

##### Check
```php
# CHECK TABLE users, pages
$db->table(['users', 'pages'])->check();
```

##### Checksum
```php
# CHECKSUM TABLE users, pages
$db->table(['users', 'pages'])->checksum();
```

##### Optimize
```php
# OPTIMIZE TABLE users, pages
$db->table(['users', 'pages'])->optimize();
```

##### Repair
```php
# REPAIR TABLE users, pages
$db->table(['users', 'pages'])->repair();
```

##### LastInsertID
```php
# Gives the last inserted `primary Key`;
$db->insertId();
```

##### Num of Rows
```php
# Counts Number of Rows
$db->numRows();
```
##### Error
```php
# Error
$db->error();
```

##### Cache
```php
# cache time: 60 seconds
$db->table('pages')->where('something', 'something')->cache(10)->get(); 
```
##### QueryCount
```php
# The number of all SQL queries on the page until the end of the beginning.
$db->queryCount(); 
```

##### LastSqlQuery
```php
# Last SQL Query.
$db->getQuery(); 
```