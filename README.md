# Image Manipulation w/ Intervention Image

A REST API application manipulates an image using Intervention Image. It allows you to make POST requests to add filter and/or watermark to an image. https://ebrukye.github.io/image-manipulation/

**Technologies used;**
- Laravel 5.7.27 as framework
- Composer for dependency management
- MySQL 8.0.15 for database management
- Intervention Image for manipulating the image
- Laravel Passport for authentication

![Scheme](public/assets/image-manipulation.jpg)

## Installation
* Clone the repository and go to project directory.

`git clone https://github.com/ebrukye/image-manipulation.git`

`cd image-manipulation`

* Create the .env file as a copy from the example file provided.

`cp .env.example .env`

* Connect to MySQL and create a database. You can find the sample terminal command below.

`mysql -u root -p`

mysql> ``create database `image-manipulation`; ``


* Update the .env file with database connection details.

```
DB_DATABASE=image-manipulation
DB_USERNAME={username}
DB_PASSWORD={password}
```

* After setting the environment, run the build script.

`./build.sh`

or the equivalent commands below.

```
composer install
php artisan key:generate
php artisan migrate
php artisan passport:install
php artisan storage:link
php artisan serve
```

...and you're all done! You have started the server on http://localhost:8000

## Sending Requests
You can send POST requests using tools like **Postman.** 
With the link below, you can directly import a test environment with endpoints provided.

`https://www.getpostman.com/collections/def75a3e6929031b3a8d`

**Base URL:**
http://localhost:8000/api

## **1. create api token**
Returns an api token to send authenticated requests while registering the user.

**Reguest**

| Method  | URL            |
| --------|----------------|
| POST    | /register      |

| Type    | Params                 | Values        |
| --------|------------------------|---------------|
| POST    | name                   | String        |
| POST    | email                  | String        |
| POST    | password               | String        |

**Response**

```
{
    "token": "<api_token>"
}
```

## **2. manipulate an image**
Returns the details of manipulated image that applied filter/watermark.

**Reguest**

| Method  | URL            |
| --------|----------------|
| POST    | /images        |

| Header         | Value                  |
| ---------------|------------------------|
| Accept         | application/json       |
| Authorization  | Bearer <api_token>     |

| Type    | Params                 | Values        |
| --------|------------------------|---------------|
| POST    | image_file             | File          |
| POST    | filter_name            | String        |
| POST    | watermark_text         | String        |
| POST    | watermark_image        | File          |

**image_file** is required.
At least a filter/watermark should be applied. 

**Response**

```
{
    "image": {
        "hash_name": "JLBuGm5EfSKHrxv12P3idc6HAULIx5M64jPj6ACA.jpeg",
        "original_path": "/storage/images/original/JLBuGm5EfSKHrxv12P3idc6HAULIx5M64jPj6ACA.jpeg",
        "modified_path": "/storage/images/modified/JLBuGm5EfSKHrxv12P3idc6HAULIx5M64jPj6ACA.jpeg",
        "applied": {
            "filter": {
                "name": "blur"
            },
            "watermark": {
                "text": "The quick brown fox jumps over the lazy dog.",
                "image": {
                    "hash_name": "VmYJoLABPSiGdsVrpcE5m5ctOFMaE69x6KoN3zD2.png",
                    "path": "/storage/images/watermarks/VmYJoLABPSiGdsVrpcE5m5ctOFMaE69x6KoN3zD2.png"
                }
            }
        }
    }
}
```