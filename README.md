# Magedbm - Magento Database Backup Manager

Provides a simpler way for developers to retrieve sanitised Magento database backups without requiring server access.

It's design to run on production boxes to create database dumps and upload to Amazon S3 buckets. 

It can then be run on local development machines to get latest or specific backup for a project and automatically import it.

## Motivation

Working on Magento client sites typically requires a fresh copy of the production database in order reduce discrepencies later in the development cycle due to differences between environments.  This can be difficult to achieve a number of reasons and either way will slow down development process.

## Compatibility

Compatible with PHP 5.3+.
Uses a PHP alternative for creating database backups when exec is disabled. 

## Installation

Download the phar.

```
wget https://s3-eu-west-1.amazonaws.com/magedbm-releases/magedbm.phar
```

Make sure it's executable

```
chmod +x ./magedbm.phar
```

Copy to /usr/local/bin/ to make it available system-wide.

```
sudo cp ./magedbm.phar /usr/local/bin/
```

## Setup

Login credentials for Amazon S3 will need to be set up to provide and maintain access to buckets and files. 

To give you an example of possible setup, the following is provided which avoids the need to have numerous buckets 
which would then need to be specified on every command.

Create a bucket, e.g. 'magedbm'.  All developers should have access to this bucket.
 
For each production box that is creating backups a new S3 user will need to be created with the "IAM Management Console".  We create a policy
 which enables them to list contents of the bucket but only download files that they have access to.  That means the only information leak
 is the names of files (client names).  This is the policy that we use:
 
```
 {
     "Version": "2012-10-17",
     "Statement": [
         {
             "Effect": "Allow",
             "Action": "s3:ListAllMyBuckets",
             "Resource": "arn:aws:s3:::*"
         },
         {
             "Sid": "Stmt1441615435000",
             "Effect": "Allow",
             "Action": [
                 "s3:ListBucket"
             ],
             "Resource": [
                 "arn:aws:s3:::magedbm"
             ]
         },
         {
             "Effect": "Allow",
             "Action": [
                 "s3:*"
             ],
             "Resource": [
                 "arn:aws:s3:::magedbm/clientname*"
             ]
         }
     ]
 }
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
magedbm get [--file="..."] [-f|--force] [-d|--drop-tables] [-r|--region="..."] [-b|--bucket="..."] name
```

### Delete Database Backups

Delete old backups from S3.

```
magedbm rm [-r|--region="..."] [-b|--bucket="..."] name file
```


## Development

### Packaging Phar

We use [box project](https://github.com/box-project/box2) for creating a phar. The configuration for which is found in 
`box.json`. 

To build, change to the project root and run:

```
box build
```

If you run into the following [error](https://github.com/box-project/box2/issues/80):

```
PHP Fatal error:  Uncaught exception 'ErrorException' with message 'proc_open(): unable to create pipe Too many open files'
```


Then increasing the limit by running this seems to be a solid workaround: 

```
ulimit -Sn 4096
```

### Creating Release

After creating a release according to [meanbee standards](http://standards.meanbee.com/tools.html), the phar can be attached to the release on github. 

It also needs to be uploaded to the magedbm-releases bucket in Meanbee's S3 account. We upload one version as `magedbm.phar` and another with the version in it `magedbm-1.3.0.phar`.

In order for the self-update command to know about the new release, the manifest.json file (also in the magedbm-releases bucket) requires update. Create a new entry in the releases array to point to the latest version.  To create the SHA which is used for valiation upon update run this command:

```
shasum -a 1 ./magedbm.phar
```

For any files uploaded, make sure that they're made public by clicking on Properties so that external uses to Meanbee can access.



