#!/bin/bash

# shellcheck disable=SC2016
echo "drop database m2_core_std; create database m2_core_std;" | mysql -h127.0.0.1 -u root -p123123qa
mysqldump -h 127.0.0.1 -u root -p123123qa m2_core | \
    sed 's/ unsigned / /g' | \
    sed 's/ tinyint(1) / tinyint /g' | \
    sed 's/KEY `EAV_ATTRIBUTE_FRONTEND_INPUT_ENTITY_TYPE_ID_IS_USER_DEFINED/KEY `EAV_ATTR_FRONTEND_INPUT_IS_USER_DEFINED/g' | \
    sed 's/KEY `EAV_ATTRIBUTE_GROUP_ATTRIBUTE_/KEY `EAV_ATTR_GRP_ATTR_/g' | \
    sed 's/KEY `CMS_PAGE_TITLE_META_KEYWORDS_META_DESCRIPTION_IDENTIFIER_CONTENT/KEY `CMS_PAGE_TITLE_META_DESCR_ID_CONTENT/g' | \
    sed 's/KEY `CATALOG_CATEGORY_ENTITY_/KEY `CCE_/g' | \
    sed 's/KEY `CATALOG_PRODUCT_ENTITY_/KEY `CPE_/g' | \
    sed 's/KEY `CATALOG_PRODUCT_FRONTEND_ACTION_/KEY `CPFA_/g' | \
    sed 's/KEY `CAT_CTGR_PRD_IDX_STORE/KEY `CCPIS/g' | \
    sed 's/KEY `CAT_PRD_ENTT_MDA_GLR_VAL_/KEY `CPEMGV_/g' | \
    sed 's/KEY `CUSTOMER_ADDRESS_ENTITY_/KEY `CAE_/g' | \
    sed 's/KEY `CATALOGSEARCH_RECOMMENDATIONS_RELATION_/KEY `CSRR_/g' | \
    mysql -h 127.0.0.1 -u root -p123123qa m2_core_std

echo "alter table captcha_log modify type smallint default 0 not null comment 'Type';" | mysql -h127.0.0.1 -u root -p123123qa m2_core_std
echo "alter table ui_bookmark alter column current set default 0;" | mysql -h127.0.0.1 -u root -p123123qa m2_core_std

docker run --rm --name pgloader dimitri/pgloader:latest \
pgloader mysql://root:123123qa@172.17.0.2:3306/m2_core_std postgresql://m2_core:123123qa@172.17.0.5:5432/m2_core
