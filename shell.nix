{ pkgs ? import <nixpkgs> }:
pkgs.mkShell {
  buildInputs = [ pkgs.php81Packages.box pkgs.watchexec ];
}