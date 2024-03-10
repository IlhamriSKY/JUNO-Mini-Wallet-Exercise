<br/>

<p align="center">
  <h1 align="center">Mini Wallet Exercise</h1>
  <h4 align="center">📦 REST API starter kit powered by Laravel, OpenAPI, Sanctum.</h4>

## Documentation

-   [Documentation](https://documenter.getpostman.com/view/1766937/2sA2xh3YNf)
-   [Postman Collection](https://arifszn.github.io/pandora/docs/api-documentation/swagger-ui)

## Installation

**Prerequisite**

-   PHP 8.2

To setup this API, first clone the project and change the directory.

```sh
git clone https://github.com/IlhamriSKY/JUNO-Mini-Wallet-Exercise
cd JUNO-Mini-Wallet-Exercise
```

Then follow the process using either **[Docker](#with-docker-sail)** or **[Without Docker](#without-docker)** and you will have a fully running Laravel installation with Sanctum, all configured.

### With Docker (Sail)

[Laravel Sail](https://github.com/laravel/sail) is a light-weight command-line interface for interacting with Laravel's default Docker development environment.

1. Copy `.env.example` to `.env`:

    ```shell
    cp .env.example .env
    ```

2. Install the dependencies:

    ```shell
    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v $(pwd):/var/www/html \
        -w /var/www/html \
        laravelsail/php82-composer:latest \
        composer install --ignore-platform-reqs
    ```

3. Run the containers:

    ```shell
    ./vendor/bin/sail up -d
    ```

4. Generate application key:

    ```shell
    ./vendor/bin/sail artisan key:generate
    ```

5. Run database migration with seeder:

    ```shell
    ./vendor/bin/sail artisan migrate --seed
    ```

To learn more about Sail, visit the [official Doc](https://laravel.com/docs/9.x/sail).

### Without Docker

1. Copy `.env.example` to `.env`:

    ```shell
    cp .env.example .env
    ```

2. Install the dependencies:

    ```shell
    composer install
    ```

3. Generate application key:

    ```shell
    php artisan key:generate
    ```

4. Run database migration with seeder:

    ```shell
    php artisan migrate --seed
    ```

5. Start the local server:

    ```shell
    php artisan serve
    ```
