{ pkgs, config, ... }:

{
  languages.php.enable = true;
  languages.php.package = pkgs.php.buildEnv {
    extraConfig = ''
      pdo_mysql.default_socket=''${MYSQL_UNIX_PORT}
      mysqli.default_socket=''${MYSQL_UNIX_PORT}
      memory_limit = 2G
    '';
  };

  languages.php.fpm.pools.web = {
    settings = {
      "clear_env" = "no";
      "pm" = "dynamic";
      "pm.max_children" = 10;
      "pm.start_servers" = 2;
      "pm.min_spare_servers" = 1;
      "pm.max_spare_servers" = 10;
    };
  };

  services.caddy.enable = true;
  services.caddy.virtualHosts."http://localhost:8000" = {
    extraConfig = ''
      root * shop/public
      php_fastcgi unix/${config.languages.php.fpm.pools.web.socket}
      file_server
    '';
  };

  services.mysql.enable = true;
  services.mysql.initialDatabases = [{ name = "shopware"; }];
  services.mysql.ensureUsers = [
    {
      name = "shopware";
      password = "shopware";
      ensurePermissions = { "shopware.*" = "ALL PRIVILEGES"; };
    }
  ];

  scripts.build-phar.exec = ''
    ${pkgs.php81Packages.box}/bin/box compile
    mkdir -p shop/public
    mv shopware-recovery.phar shop/public/shopware-recovery.phar.php
  '';

  processes.watch-phar.exec = ''
    ${pkgs.watchexec}/bin/watchexec -e php,js,yml,twig,css build-phar
  '';
}
