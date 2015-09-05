# Magedbm - Magento Database Backup Manager

Provides a simpler way for developers to retrieve sanitised Magento database backups without requiring server access.

## About

Working on Magento client sites typically requires a fresh copy of the production database in order to minimise differences
within local environment.  This is a pain for numerous reason:

- Requires developer to have access to SSH

- Staging is regularly out of date as well 


## Installation



## Usage

### Configure

`bin/magedbm configure [-f|--force] key secret region bucket`

### Upload Database

`bin/magedbm put [-r|--region="..."] [-b|--bucket="..."] name`

### List Available Databases

`bin/magedbm ls [-r|--region="..."] [-b|--bucket="..."] name`

### Download & Import Database

`bin/magedbm get [-d|--drop-tables] [-r|--region="..."] [-b|--bucket="..."] name file`

### Detele Database Backups

`bin/magedbm rm [-r|--region="..."] [-b|--bucket="..."] name file`