# Symfony 5.4 Notify App

API Service for sending notifications using RabbitMQ with Swagger API Documentation (NelmioApiDocBundle) built on Symfony 5.4

## Requirements

* [Docker](https://docs.docker.com/engine/install/)
* [Docker Compose](https://docs.docker.com/compose/install/)

## Docker Services

* php - [Symfony Alpine Docker](https://github.com/therceman/symfony-alpine-docker) with Apache/2.4.52 and PHP 7.4.27
* db - PostgreSQL (13)
* rabbitmq - RabbitMQ (3-management)
* mailhog - MailHog (latest)

## Demonstration Video

[![Watch the video](https://github.com/therceman/notify/blob/master/home_task_youtube_preview.png)](https://www.youtube.com/watch?v=PuEPw3PEklE)

## Setup

Clone repository and navigate to its folder
```bash
git clone https://github.com/therceman/notify-app.git
```

### Configuration (Optional)

You can change all variables and ports defined in the `utils/docker/.env` file.

### Installation

#### 1) Build & Run Docker

Navigate to docker folder
```bash
cd notify-app/utils/docker
```

Build & Run Docker Image
```bash
docker-compose up -d --build
```

#### 2) Install Symfony Dependencies & Init Database

Connect to Docker container. Root path will be `var/www/` (Symfony root folder)
```bash
docker-compose run --rm php bash
```

Install Dependencies
```
composer install
```

Initialize Database: 
```bash
symfony console doctrine:migrations:migrate --no-interaction
```
```bash
symfony console doctrine:fixtures:load --no-interaction
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
