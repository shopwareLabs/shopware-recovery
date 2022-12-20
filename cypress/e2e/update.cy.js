describe('empty spec', () => {
    it('passes', () => {
        // Install Shopware
        cy.exec('rm -rf shop');
        cy.exec('wget -q https://releases.shopware.com/sw6/install_v6.4.17.2_4d2c85fb448571fa4f30edce635f33a67dda1d76.zip -O install.zip');
        cy.exec('unzip -q -o -d shop install.zip');

        cy.exec('cd shop; bin/console system:setup --database-url mysql://root@localhost/shopware --app-url http://localhost:8000 -f -n')
        cy.exec('cd shop; bin/console system:install --basic-setup --create-database --drop-database --force -n');
        cy.exec('cd shop; bin/console system:config:set core.frw.completedAt "2019-10-07T10:46:23+00:00"');

        // Check if the Installation is running
        cy.visit('http://localhost:8000/admin');
        cy.get('#sw-field--username').type('admin');
        cy.get('#sw-field--password').type('shopware');

        cy.get('.sw-login__login-action').click();

        cy.get('.sw-version__info').contains('6.4.17.2');

        // Build recovery
        cy.exec('composer run build-phar');
        cy.exec('mv shopware-recovery.phar shop/shopware-recovery.phar');

        // Configure PHP
        cy.visit('http://localhost:8000/shopware-recovery.phar.php');
        cy.get('.card__title').contains('Configure PHP executable');
        cy.get('.btn-primary').click();

        // Show basic info
        cy.get('.card__title').contains('Updating Shopware to');

        cy.get('.btn-primary').click();

        // wait for /update/_finish ajax call to finish

        cy.intercept('/shopware-recovery.phar.php/update/_finish').as('updateFinish');
        cy.wait('@updateFinish', {timeout: 60000});

        // Shows finish page
        cy.get('.card__title', {timeout: 60000}).contains('Finish');

        cy.get('.btn-primary').click();

        cy.get('.sw-version__info').contains('6.4.18');

        // visit updater and expect 404
        cy.visit('http://localhost:8000/shopware-recovery.phar.php', {failOnStatusCode: false});
        cy.contains('Page not found');
    })
})