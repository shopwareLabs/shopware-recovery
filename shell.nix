{ pkgs ? import <nixpkgs> }:
pkgs.mkShell {
  buildInputs = [ 
    pkgs.php81Packages.box pkgs.watchexec
    pkgs.php81
    pkgs.php81Packages.composer
    pkgs.symfony-cli
  ];
}