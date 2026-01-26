# Twitter風SNSアプリ

Firebase認証とLaravel APIを組み合わせた、  
**投稿・いいね・コメント機能を備えた Twitter風SNSアプリ**です。

![トップ画面（投稿一覧）](docs/images/top.png)

---

## 作成した目的

- Firebase認証とLaravel APIを組み合わせた実践的な構成を理解するため
- SPA（Single Page Application）の実装経験を積むため
- バリデーション・認証・CRUD処理を一通り自力で実装できるようになるため
- フロントとバックエンドを分離した設計に慣れるため

---

## アプリケーションURL

※ 現在は **ローカル環境での開発のみ**  

### ローカルURL
- Front（Nuxt）：`http://localhost:3000`
- API（Laravel）：`http://localhost:8000/api`

---

## 他のリポジトリ

本リポジトリは **フロントエンド・バックエンドを1つのリポジトリで管理**しています。


---

## 機能一覧

### 認証機能（Firebase Authentication）
- 新規登録
- ログイン / ログアウト
- 表示名（ユーザー名）の保存

### 投稿機能
- 投稿一覧表示
- 新規投稿
- 投稿削除（自分の投稿のみ）

### いいね機能
- いいね追加 / 解除
- いいね数のリアルタイム反映

### コメント機能
- コメント一覧表示
- コメント投稿
- 入力バリデーション（必須 / 120文字以内）

---

## 使用技術（実行環境）

### フロントエンド
- JavaScript（Nuxt 3）
- TypeScript
- VeeValidate / Yup（バリデーション）
- Firebase Authentication（認証）

### バックエンド
- PHP（Laravel）
- Laravel API
- Firebase ID Token 認証

### その他
- Docker（Laravel側）
- Git / GitHub

---

## テーブル設計
本アプリでは、Firebase Authentication を用いた認証を前提とし、
**ユーザー情報は最小限のみDBに保持**する構成としています。

投稿・いいね・コメント機能を実現するため、
以下の4テーブルで設計しています。

### テーブル概要

| テーブル名 | 説明 |
|-----------|------|
| users     | Firebase UID を基準にしたユーザー情報（表示名・メール） |
| posts     | 投稿データ（本文・投稿者・表示名スナップショット） |
| likes     | 投稿へのいいね情報（post × user の中間テーブル） |
| comments  | 投稿へのコメント情報 |

※ 投稿・コメントの表示名は、投稿時点の表示名を保持するため  
`user_name` をスナップショットとして保存しています。

---

## ER図
![ER Diagram](docs/er/er_diagram.png)

---

## 環境構築

### 1. リポジトリをクローン
```bash
# HTTPS
git clone https://github.com/kie-okamoto/twitter-sns.git

# SSH
git clone git@github.com:kie-okamoto/twitter-sns.git

cd twitter-sns
```
### 2. フロントエンドのセットアップ
```
cd front
npm install
npm run dev
```

### 3. フロントエンド環境変数を設定
`front/.env` を作成し、以下を記載します。

```env
NUXT_PUBLIC_API_BASE=http://localhost:8000/api
NUXT_PUBLIC_FIREBASE_API_KEY=xxxx
NUXT_PUBLIC_FIREBASE_AUTH_DOMAIN=xxxx
NUXT_PUBLIC_FIREBASE_PROJECT_ID=xxxx
NUXT_PUBLIC_FIREBASE_APP_ID=xxxx
NUXT_PUBLIC_FIREBASE_STORAGE_BUCKET=xxxx
NUXT_PUBLIC_FIREBASE_MESSAGING_SENDER_ID=xxxx
```

### 4. API(Laravel)のセットアップ
```
cd api
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```
