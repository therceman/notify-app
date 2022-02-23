# Symfony 5.4 Notify App

Symfony API Service for sending email/sms notifications using RabbitMQ

# Requirements

* [Docker](https://docs.docker.com/engine/install/)
* [Docker Compose](https://docs.docker.com/compose/install/)

## Docker Services

* php - [Symfony Alpine Docker](https://github.com/therceman/symfony-alpine-docker) with Apache/2.4.52 and PHP 7.4.27
* db - PostgreSQL (13)
* rabbitmq - RabbitMQ (3-management)
* mailhog - MailHog (latest)

## Demonstration Video

[![Watch the video](https://github.com/therceman/notify-app/blob/master/app/public/home_task_youtube_preview.png)](https://www.youtube.com/watch?v=PuEPw3PEklE)

## Setup

#### 1) Initialization

Clone repository
```bash
git clone https://github.com/therceman/notify-app.git
```

Navigate to docker folder
```bash
cd notify-app/utils/docker
```

#### 2) Configuration (Optional)

* Apache, PostgreSQL, RabbitMQ and MailHog can be configured in `utils/docker/.env` file
* CORS Allowed Origins can be configured in `app/.env` file

#### 3) Start

Build & Run Docker
```bash
docker-compose up -d --build
```

Connect to Docker container
```bash
docker-compose run --rm php bash
```

Install Symfony Dependencies
```
composer install
```

Migrate & Fill Database: 
```bash
symfony console doctrine:migrations:migrate --no-interaction
```
```bash
symfony console doctrine:fixtures:load --no-interaction
```

Start Queue Worker (Message Consumer)
```bash
symfony console messenger:consume async -vv
```

## Exposed Services

### Swagger API Documentation (NelmioApiDocBundle)

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
APP_ENV=test symfony console doctrine:database:create --no-interaction
```
```bash
APP_ENV=test symfony console doctrine:migrations:migrate --no-interaction
```
```bash
APP_ENV=test symfony console doctrine:fixtures:load --no-interaction
```

Run tests

```bash
APP_ENV=test symfony php bin/phpunit tests/Controller/ClientControllerTest.php
```
```bash
APP_ENV=test symfony php bin/phpunit tests/Controller/NotificationControllerTest.php
```
