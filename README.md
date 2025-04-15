# attendance-manegement(勤怠管理)  


## 環境構築  


**Dockerビルド**  

  1.GitHub からクローン  

    `git@github.com:Blue1127n/attendance-manegement.git`   

  2.プロジェクトディレクトリに移動  

    `cd attendance-manegement`  

  3.リモートURLを変更  

    `git remote set-url origin <新しいリポジトリURL>`  

  4.初回のmainブランチをプッシュ  

    `git push origin main`  

  5.DockerDesktopアプリを立ち上げる  

    `docker-compose up -d --build`  
    `code .`  


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


**MailHog環境構築**  

  1.docker-compose.ymlに追加  
    注意：他のサービス（php, nginx, mysqlなど）と同じインデント階層に追加  

    ``` text  
    services:
      mailhog:
        image: mailhog/mailhog
        ports:
          - "1025:1025"
          - "8025:8025"
    ```  

  2.MailHogのセットアップ  

    `docker-compose up -d mailhog`  

  3.「.env」の設定  

    ``` text  
    MAIL_MAILER=smtp
    MAIL_HOST=mailhog
    MAIL_PORT=1025
    MAIL_USERNAME=null
    MAIL_PASSWORD=null
    MAIL_ENCRYPTION=null
    MAIL_FROM_ADDRESS="noreply@example.com"
    MAIL_FROM_NAME="勤怠管理"
    ```  

  4.キャッシュクリア  

    `php artisan config:clear`  
    `php artisan cache:clear`  
    `php artisan serve`  
    `docker-compose restart

以下のリンクは認証メールの遷移先です。<br>
- http://localhost:8025/


## 使用技術(実行環境)  

- PHP8.3.11  
- Laravel8.83.8  
- MySQL8.0.26  


## ER図  

![ER図](src/public/images/ER図.svg)  


## URL  

- 開発環境：http://localhost/  
- phpMyAdmin:：http://localhost:8080/  
