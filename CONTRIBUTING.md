# Contributions

Contributions are encouraged. 

We will review all contributions against the original requirements.

## User Stories
 
- As a developer I want to import a client database into my local environment quickly.
- As a developer I want to import a client database into a staging environment quickly.
- As a company owner I want to limit my exposure to data breaches by preventing sensitive information from entering my networks.
- As a developer I don't want to have to login to a server and create a database backup manually.
- As a lead developer, I don't want to have to grant every developer production SSH access in order for them to get a database.
 
- As a developer I want the tool to be packaged as a phar so that all its dependencies are already available.


## Technical Specification
 
- The tool should upload and download from S3.
- The tool should allow me to download the latest database from S3.
- The tool should take a stripped database snapshot, compress it, then upload to S3.
- The tool should allow for different AWS keys and secrets to be used for different clients.