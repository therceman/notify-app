## Requirements
--------------------------------------------

1) PHP `7.3.5` with extension: `pdo_pgsql`

## Tests
--------------------------------------------

### Prepare Test Database

1) `APP_ENV=test symfony console doctrine:database:create`
2) `APP_ENV=test symfony console doctrine:migrations:migrate`
3) `APP_ENV=test symfony console doctrine:fixtures:load`

### Run Tests

1) `APP_ENV=test symfony php bin/phpunit tests/Controller/ClientControllerTest.php`
1) `APP_ENV=test symfony php bin/phpunit tests/Controller/NotificationControllerTest.php`

## Commands
--------------------------------------------

Run Docker: `docker-compose up -d`

DUMP ENV VARS: `symfony console debug:container --env-vars`

Reset DB: `symfony console doctrine:database:drop --force`

Create DB : `symfony console doctrine:database:create`

Make DB Migration: `symfony console make:migration`

Migrate Tables to DB: `symfony console doctrine:migrations:migrate`

Create Fixtures: `symfony console doctrine:fixtures:load`

List Containers (for tests): `symfony console debug:container`


// not needed
Start WEB Server: `symfony server:start -d`