# Shopware Recovery

## Experiment

This is an experimental project to build an simple recovery tool for Shopware 6 to install or update Shopware 6.

## Idea

Built an simple Symfony Application which Installs / Updates Shopware using Composer in the Web.

Workflow:

- Shopware 6 downloads this Tool as **Phar** and unpacks it to the public folder
- Redirects the User to it
- This application updates the Shopware 6 using Composer
- Runs the Migrations
- Redirects to Shopware 6 and nukes this Tool

