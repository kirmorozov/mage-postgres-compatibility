Postgres Compatibility for Magento
========

### Motivation
Back in 2010 Magento 1.4->1.6 was rewritten to have Zend_Select everywhere.\
It was done as a project for compatibility with SQL Server and Oracle.\
Postgres was not in scope at that time. Even after Magento 2 moved to Github, it never came in.\
Adapter implementation is not there because of its low level and complexity.\
So this module provides baseline for further development.\

There were updates to Magento 2 that made some part incompatible but legacy of 2010 is still there.

### Current state of implementation

Current state is a `proof of concept` and add to cart works!!!.\
At this moment `bin/magento` works with some commands, \
like `store:list`,`store:website:list`,`config:show`.\
Login into admin works, homepage works, CMS Editing works.\
Visiting orders, invoices, shipments, settings.\
Catalog and Cart Rules, visited, but did not check in action.

Basic CRUD suppose to work fine. 

### Pain points
[ ] DDL Cache (It used Redis) \ 
[x] Identities and autoincrements.(script in misc)\
[ ] Insert on duplicate.\
[x] Fancy catalog collection::getSize(); (`compatibility patch`)
[x] EAV Unions. (`compatibility patch`) \
[ ] Indexers and Reports.\
[ ] Magento relies on custom implementation of Material Views for Mysql.\
[ ] Time Zone, database uses GMT.\
[ ] Functions that do not exist in Postgres. added `getFieldSql()` with `compatibility patch` for sort calls.\


### Make it work

0. Prepare working Magento with MySQL database.
1. Use script misc/mysql_to_pgsql.sh as an example for migration of your database.
2. Use script `php convert_sequences.php` to restore identities/auto-increments for primary keys.
3. Apply some patches to the core from [Postgres Compatibility Branch](https://github.com/kirmorozov/magento2/tree/2.4-postgres-compatibility)
4. Change app/etc/env.php
5. Enjoy.
6. Fix  'Not implemented Morozov\PgCompat\DB\Adapter\Pdo\Postgres...' exception.
7. Go to 5 :)


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
