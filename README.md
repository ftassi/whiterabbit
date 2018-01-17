A simple time tracking calendar backed up by Redmine

SETUP:
```
composer install
cd web
bower install
cp .env.dist .env
```

.env configuration is shared with lastpass.


TO RUN IT:
```
php -S localhost:8080 -t web web/dev_routing.php
```
And then open http://localhost:8080/index.html


DEPLOY:
```
/vendor/bin/idx deploy --env=prod --go
```
