# Symfony Notify App

Application for sending notification using queue.

Not Implemented Yet
1) Clients list is not paginated 
2) Notifications list is not paginated
3) Notifications list filter by client
4) Create multiple notifications (only one per request)

## Requirements

* [Docker](https://docs.docker.com/engine/install/)
* [Docker Compose](https://docs.docker.com/compose/install/)

## Docker Services

* Nginx
* PostgreSQL 13
* PHP 7.4
* RabbitMQ
* MailHog

## Setup

Open directory after cloning.

### Configuration (Optional)

You can change all variables and ports defined in the utils/docker/.env file.

### Installation

#### 1) Build & Run Docker

Navigate to docker folder
```bash
cd utils/docker
```

Build & run docker
```
docker-compose build
docker-compose up -d
```

#### 2) Install Symfony Dependencies & Init Database

Open bash inside Docker container. Your path will be `var/www/` where Symfony is installed
```bash
docker-compose run --rm php bash
```

Execute composer install command
```
composer install
```

Initialize Database: 
```bash
symfony console doctrine:migrations:migrate
symfony console doctrine:fixtures:load
```

#### 3) Start Queue Worker (Message Consumer)
```bash
symfony console messenger:consume async -vv
```

## Exposed Services

### Swagger API Documentation

http://127.0.0.1:8181/api/doc

Use following key for `Private API` group routes
```
d692dfe7657f7994c9eafe3b7914d252
```

### RabbitMQ Manager

http://127.0.0.1:15672

Login: `guest`, Password: `guest`

### MailHog UI

http://127.0.0.1:8025

## Running Automated Tests

Prepare Test Database
```bash
APP_ENV=test symfony console doctrine:database:create
APP_ENV=test symfony console doctrine:migrations:migrate
APP_ENV=test symfony console doctrine:fixtures:load
```

Run tests

```bash
APP_ENV=test symfony php bin/phpunit tests/Controller/ClientControllerTest.php
APP_ENV=test symfony php bin/phpunit tests/Controller/NotificationControllerTest.php
```

------

### Additional Commands

Dump ENV vars
```bash
symfony console debug:container --env-vars
```

Reset DB
```bash
symfony console doctrine:database:drop --force
```

Create DB
```bash
symfony console doctrine:database:create
```

Stop Docker
```bash
docker-compose down --remove-orphans
```

Stop Docker & Remove Volumes
```bash
docker-compose down --remove-orphans --volumes
```
