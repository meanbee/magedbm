# Magedbm - Magento Database Backup Manager

Provides a simpler way for developers to retrieve sanitised Magento database backups without requiring server access.

Run on production boxes to save database backups to Amazon S3.
Run on local development machines to get latest backups

## About

Working on Magento client sites typically requires a fresh copy of the production database in order to minimise differences
within local environment.  This is a pain for numerous reason:

- Requires developer to have access to SSH

- Staging is regularly out of date as well 


## Installation



## Usage

### Configure

Configure with AWS credentials. 

`bin/magedbm configure [-f|--force] key secret region bucket`

### Upload Database

Dump database with customer and sales information stripped and upload to S3.

`bin/magedbm put [-r|--region="..."] [-b|--bucket="..."] name`

### List Available Databases

List database backups available for project.

`bin/magedbm ls [-r|--region="..."] [-b|--bucket="..."] name`

### Download & Import Database

Download chosen or latest database and import into Magento instance.

`bin/magedbm get [--file="..."] [-d|--drop-tables] [-r|--region="..."] [-b|--bucket="..."] name`

### Delete Database Backups

Delete old backups from S3.

`bin/magedbm rm [-r|--region="..."] [-b|--bucket="..."] name file`