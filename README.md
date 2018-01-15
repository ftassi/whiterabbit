A simple time tracking calendar backed up by Redmine

SETUP:
```
composer install
cd web
bower install
git-crypt unlock <cryptkey_file>
```

TO RUN IT:
```
php -S localhost:8080 -t web web/dev_routing.php
```
And then open http://localhost:8080/index.html


DEPLOY:
```
/vendor/bin/idx deploy --env=prod --go
```
