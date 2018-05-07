DeepRecordCopy
=========================

Copy record from one database to another database.

Features
--------

* PSR-4 autoloading compliant structure
* Unit-Testing with PHPUnit
* Comprehensive Guides and tutorial
* Easy to use to any framework or even a plain php file


Base usage 
----------
```php
<?php
    
    $var = new \ParisEngineers\DeepRecordCopy\Copy();

    $var->setFrom('localhost', 'db1', 'user', 'password');
    $var->setTo('localhost', 'db2', 'user', 'password');
    $var->copy('table', 'id', 1);

```
    

