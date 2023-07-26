<p align="center">
  <img src="https://optimajasa.co.id/img/logo-POJ.png?raw=true" alt="Pesona Optima Jasa Logo"/>
</p>

<div align="center">

[![Laravel](https://img.shields.io/badge/Laravel-v9.19-red)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-v8.0-blue)](https://www.php.net/releases/8.0/en.php)
[![VUE](https://img.shields.io/badge/Vue-v3.0-green)](https://vuejs.org/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-13-blue)](https://www.postgresql.org/)

</div>

### Requirements:

1. [PHP > 8.1](https://www.php.net/releases/8.1/en.php)
2. [Composer](https://getcomposer.org/)
3. [Node > 14.0](https://nodejs.org/es/blog/release/v14.17.3)
4. [PostgreSQL > 13.0](https://www.postgresql.org/download/)

### Installation:

#### First Step :
1. Install Git using this documentation [Docs](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)
2. Install Composer using this documentation [Docs](https://www.hostinger.com/tutorials/how-to-install-composer)
3. Install PostgresSQL using this documentation
   - Windows [Docs](https://phoenixnap.com/kb/install-postgresql-windows)
   - Linux [Docs](https://www.cherryservers.com/blog/how-to-install-and-setup-postgresql-server-on-ubuntu-20-04)
   - Mac [Docs](https://www.postgresqltutorial.com/postgresql-getting-started/install-postgresql-macos/)
4. Install Node using this documentation [Docs](https://kinsta.com/blog/how-to-install-node-js/)

#### Fast Use :
1. Clone this repository 
   ```shell
    # using git (make sure to add your ssh key to gitlab)
    git clone git@cicd.optimajasa.co.id:danudenny/poj-web.git
   
    # using https (using your gitlab username and password)
    git clone https://cicd.optimajasa.co.id/danudenny/poj-web.git
    ```
2. Install dependencies `composer install`
3. Copy `.env.example` to `.env` and configure your database credentials
4. Run Artisan Commands
    ```shell
    # Generate application key
    php artisan key:generate
   
    # Run migrations
    php artisan migrate
   
    # Run seed
    php artisan db:seed
   
    # Run optimize
    php artisan optimize
   
    # Run queue
    php artisan queue:work

    # Run development server
    php artisan serve
    ```
5. Install node dependencies `npm install`
6. Run development server for Client Web `npm run dev`
7. Open your browser and go to `http://localhost:8000`

#### Other / 3rd Dependencies :
1. Minio as Object Storage located on 192.168.100.73:9000 (Install via [docker](https://docs.min.io/docs/minio-docker-quickstart-guide.html))
2. Telescope as Debugging Tool located on <host&port>/telescope (Install via [composer](https://laravel.com/docs/9.x/telescope#installation))
3. Latitude & Longitude converter to Timezone located on 192.168.100.73:2004 (Install via [docker](https://github.com/noandrea/geo2tz))
4. ERP to app syncronization located on 192.168.100.73:8282 -> ([Golang](https://cicd.optimajasa.co.id/danudenny/poj_odoo_sync))
5. Face Recognition API located on 192.168.100.73:5555 -> ([Python Flask](https://cicd.optimajasa.co.id/danudenny/face_recongition))

#### API Documentation :

1. [Postman](https://documenter.getpostman.com/view/13588891/Tz5qZK7z)
2. Download collection [here](https://drive.google.com/drive/u/0/folders/1v9B738_8mRKYOiFaSM6nVajgGmKYt3OY)
3. Import collection (Attendance POJ Services.postman_collection.json) to your postman.
4. Import environment (ATT_POJ_ENV.postman_environment.json) to your postman.

#### Troubleshooting :
1. If you get error `SQLSTATE[HY000] [2002] No such file or directory` when running `php artisan migrate` you can use this command `php artisan migrate --env=local`
2. If you get error `SQLSTATE[HY000] [2002] Connection refused` when running `php artisan migrate` you can use this command `php artisan migrate --env=local`
3. If you get error `The "https://repo.packagist.org/packages.json" file could not be downloaded: failed to open stream: Connection refused` when running `composer install` you can use this command `composer install --ignore-platform-reqs`
4. If you get error `npm ERR! code ERR_SOCKET_TIMEOUT` when running `npm install` you can use this command `npm install --ignore-scripts`
5. If you get error `npm ERR! code ERR_SOCKET_TIMEOUT` when running `npm run dev` you can use this command `npm run dev --ignore-scripts`
