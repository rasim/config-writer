Config Writer for Laravel 4

==================

[![Total Downloads](https://poser.pugx.org/rasim/config/downloads.svg)](https://packagist.org/packages/rasim/config) 


Features by daftspunk/laravel-config-writer with additions by myself.

For Laravel 5 original repo: https://github.com/daftspunk/laravel-config-writer


Installation
----

* In composer.json;

```json
"rasim/config": "dev-master"
```

* In app.php

```php
'Rasim\Config\ConfigWriterServiceProvider',
 ```
 
  
Usage
----

 ```php
Config::write("__FILE.CONFIG__","__VALUE__","__FOLDER__");
 ```
 
  ```shell
  __FOLDER__ (optional)
  default path folder: config
  ```

Single Write
```php
Config::write("app.url","www.domain.com"); // path: config/app.php "url" editing.
Config::write("app.url","www.domain.com","config"); //  path: config/app.php "url" editing.
Config::write("reminders.sent","Sent Password!","lang/en"); // path: lang/en/reminders.php "sent" editing.
```

Multi Write
```php
Config::write(["app.url","app.locale"],["www.domain.com","tr"]);
Config::write(["reminders.sent","app.url"],["Sent Password!","www.domain.com"],["lang/en",""]);
```
