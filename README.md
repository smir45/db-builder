## db-builder
Powerful, Optimized Query Builder For Your PHP projects

## Installing Library Using Composer
`$ composer require ryzen/db-builder`

## Setting Things UP
The very first thing to do is loading the autoload

`include 'vendor/autoload.php';`

Creating New Configuration File

```
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
```
$db  = new \Ryzen\DbBuilder\DbBuilder($config);
```

## Example
##### Select
```
$query = $db->table('users')
            ->select('name, surname, age')
            ->where('user_id', '=', 1)
            ->orderBy('id', 'desc')
            ->limit(20)
            ->getAll();
```
##### Insert
```
$query = $db->table('users')
            ->insert([
            'name'  => 'Hari Bahadut',
            'email' => 'haribahadur@domain.com'
            ]);
```

##### Update
```
$query = $db->table('users')
            ->where('id', 1)
            ->update([
            'name'  => 'Madan Krishna',
            'email' => 'madan@madan.com',
            ]);
```
##### Join
```
$query = $db->table('users')
            ->select('users.name, users.title, users.slug')
            ->join('post', 'users.id', 'users.user_id')
            ->where('users.status', 1)
            ->where('post.status', 1)
            ->orderBy('users.created_at', 'desc')
            ->limit(10)
            ->getAll();
```
##### Delete
```
$query = $db->table('users')
            ->where('type', 'deactivated')
            ->where('status', 0)
            ->delete();
```
