Postgres Compatibility for Magento
========

### Motivation
Back in 2010 Magento 1.4->1.6 wa rewritten to have Zend_Select everywhere.\
It was done as a project for compatibility with SQL Server and Oracle.\
Postgres was not in scope at that time. Even after Magento 2 moved to Github, it never came in.\
Adapter implementation is not there because of its low level and complexity.\
So this module provides baseline for further development.

### Current state of implementation

Current state is a `proof of concept`.\
At this moment `bin/magento` works with some commands, \
like `store:list`,`store:website:list`,`config:show`.
Basic CRUD suppose to work fine. 

### Pain points
Indexers and Reports.\
Magento relies on custom implementation of Material Views for Mysql.\
Time Zone, database uses GMT.


### Make it work

1. Use script misc/mysql_to_pgsql.sh as an example for migration of your database.
2. Use patch misc/app_etc_di.patch to patch app/etc/di.xml (until you enable module)
3. Change app/etc/env.php
4. Enjoy.
5. Fix  'Not implemented Morozov\PgCompat\DB\Adapter\Pdo\Postgres...' exception.
6. Go to 4 :)



### License
    Copyright 2021 Kirill Morozov <kir.morozov@gmail.com>

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
