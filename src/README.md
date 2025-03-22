# attendance-manegement(勤怠管理)  


## 環境構築  


**Dockerビルド**  

  1.GitHub からクローン  

    `git@github.com:Blue1127n/attendance-manegement.git`   

  2.プロジェクトディレクトリに移動  

    `cd attendance-manegement`  

  3.DockerDesktopアプリを立ち上げる  

    `docker-compose up -d --build`  


**Laravel環境構築**  


  1.PHPコンテナ内にログイン  

    `docker-compose exec php bash`  

  2.インストール  

    `composer install`  

  3.「.env」ファイルを作成  

    `cp .env.example .env`  

  4..envに以下の環境変数に変更  

    ``` text  
    DB_CONNECTION=mysql
    DB_HOST=mysql
    DB_PORT=3306
    DB_DATABASE=laravel_db
    DB_USERNAME=laravel_user
    DB_PASSWORD=laravel_pass
    ```  

  5.アプリケーションキーの作成  

    `php artisan key:generate`  

  6.マイグレーションの実行  

    `php artisan migrate`  

  7.シーディングの実行  

    `php artisan db:seed`  


## MailHog  

- http://localhost:8025/


## 使用技術(実行環境)  

- PHP8.3.11  
- Laravel8.83.8  
- MySQL8.0.26  


## ER図  

![ER図]  


## URL  

- 開発環境：http://localhost/  
- phpMyAdmin:：http://localhost:8080/  
