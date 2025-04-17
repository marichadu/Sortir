@echo off
echo Installing Symfony packages...
composer require symfony/orm-pack
composer require symfony/twig-pack
composer require symfony/security-bundle
composer require symfony/form
composer require symfony/validator
composer require symfony/maker-bundle
composer require symfony/asset
echo Installation complete!
pause 