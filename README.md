A simple but powerful database frontend

INSTALLATION
============
```sh
git clone https://github.com/plepe/database-frontend.git
cd database-frontend
git submodule init
git submodule update
cp conf.php-dist conf.php
$EDITOR conf.php
chmod o+w db # or change path in conf.php to a different writeable directory
```

UPDATING
========
```sh
git pull
git submodule update
```
