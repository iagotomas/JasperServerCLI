JasperServerCLI
===============

JasperServer CLI tools

Command line tools to work with JasperServer repository. The tools are written in PHP and use SOAP to communicate with JasperServer.

To use you must configure **jasperserver.php $config** with your setup and use the bash scripts providen as follows:


List a uri from the repository:
`jslist.sh <uri>`


Retrieve a resource from the repository (full URI to resource)
`jsget.sh <uri>`


Create a folder in the repository (full URI to new desired folder)
`jscreate.sh <uri>`


Delete a folder and all its contents:
`jsdelete.sh <uri>`
