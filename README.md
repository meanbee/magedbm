# Magedbm - Magento Database Backup Manager

Provides a simpler way for developers to retrieve sanitised Magento database backups without requiring server access.

It's design to run on production boxes to create database dumps and upload to Amazon S3 buckets. 

It can then be run on local development machines to get latest or specific backup for a project and automatically import it. 


## Motivation

Working on Magento client sites typically requires a fresh copy of the production database in order reduce discrepencies later in the development cycle due to differences between environments.  This can be difficult to achieve a number of reasons and either way will slow down development process. 


## Installation

Download the phar.

```
wget https://github.com/meanbee/magedbm/releases/download/v1.2.0/magedbm.phar
```

Make sure it's executable

```
chmod +x ./magedbm.phar
```

Copy to /usr/local/bin/ to make it available system-wide.

```
sudo cp ./magedbm.phar /usr/local/bin/
```


## Usage

### Configure

Configure with AWS credentials. 

```
 magedbm configure [-k|--key="..."] [-s|--secret="..."] [-r|--region="..."] [-b|--bucket="..."] [-f|--force]
```

### Upload Database

Dump database with customer and sales information stripped and upload to S3.

```
magedbm  put [-s|--strip[="..."]] [--no-clean] [--history-count="..."] [-r|--region="..."] [-b|--bucket="..."] name
```

### List Available Databases

List database backups available for project.

```
magedbm ls [-r|--region="..."] [-b|--bucket="..."] name
```

### Download & Import Database

Download chosen or latest database and import into Magento instance.

```
magedbm get [--file="..."] [-d|--drop-tables] [-r|--region="..."] [-b|--bucket="..."] name
```

### Delete Database Backups

Delete old backups from S3.

```
magedbm rm [-r|--region="..."] [-b|--bucket="..."] name file
```
