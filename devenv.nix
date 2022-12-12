{ pkgs, config, ... }:

{
  languages.php.enable = true;
  languages.php.package = pkgs.php.buildEnv {
    extensions = { all, enabled }: with all; enabled ++ [ redis blackfire ];
    extraConfig = ''
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

  caddy.enable = true;
  caddy.virtualHosts."http://localhost:8000" = {
    extraConfig = ''
      root * shop/public
      php_fastcgi unix/${config.languages.php.fpm.pools.web.socket}
      file_server
    '';
  };

  mysql.enable = true;
  mysql.initialDatabases = [{ name = "shopware"; }];
  mysql.ensureUsers = [
    {
      name = "shopware";
      password = "shopware";
      ensurePermissions = { "shopware.*" = "ALL PRIVILEGES"; };
    }
  ];

  scripts.build.exec = ''
    ${pkgs.php81Packages.box}/bin/box compile
    mkdir -p shop/public
    mv shopware-recovery.phar shop/public/
  '';

  scripts.watch.exec = ''
    ${pkgs.watchexec}/bin/watchexec -e php,js build
  '';
}
