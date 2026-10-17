-- PHPUnit 用のテスト専用データベース
-- 開発用DB(ats_php)とは分け、テスト実行のたびに作り直しても開発データが消えないようにする
CREATE DATABASE IF NOT EXISTS ats_testing
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- アプリ用ユーザーにテスト用DBの権限を付与する
GRANT ALL PRIVILEGES ON ats_testing.* TO 'ats_user'@'%';
