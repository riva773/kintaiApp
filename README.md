# 勤怠管理アプリ

## 環境構築

```
cp src/.env.example src/.env
# 以降の「.env 設定」を参考に、DB とメール(MailHog)の値を確認/調整してください

docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

## .env 設定

```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass

# MailHog
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="no-reply@example.local"
MAIL_FROM_NAME="Kintai App"
```

## 使用技術

- Laravel 8.83.8
- PHP 8.1
- Nginx 1.21.1
- Docker / Docker Compose
- MySQL 8.0.26
- MailHog

## URL 一覧

一般ユーザー画面

- 会員登録画面 /register
- ログイン画面 /login
- 勤怠登録画面 /attendance
- 勤怠一覧画面 /attendance/list
- 勤怠詳細画面 /attendance/detail/{id}
- 申請一覧画面 /stamp_correction_request/list

管理者画面

- ログイン画面 /admin/login
- 勤怠一覧画面 /admin/attendance/list
- 勤怠詳細画面 /admin/attendance/{id}
- スタッフ一覧画面 /admin/staff/list
- スタッフ別勤怠一覧画面 /admin/attendance/staff/{id}
- 申請一覧画面 /stamp_correction_request/list
- 修正申請承認画面 /stamp_correction_request/approve/{attendance_correct_request}
