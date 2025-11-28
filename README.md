# 今日のご飯は？ – Restaurant Roulette App

「今日のご飯は何にしよう？」を楽しく解決する  
**レストラン・カテゴリ別ルーレット Web アプリ** です。  
ゲストでも使えるカテゴリルーレット、会員限定の検索ルーレット、  
お気に入り機能や履歴、管理者パネルまで実装しています。

---

## 🚀 Features（主な機能）

### 🎡 1. カテゴリルーレット（ゲスト可）
- 誰でもすぐ回せるルーレット
- カテゴリの ON/OFF をユーザー設定として保存
- 非同期更新、即時反映

### 🔍 2. 検索ルーレット（会員限定）
- Google Maps API を利用した飲食店検索
- 現在地・住所入力で検索可能
- 半径（距離）フィルタ
- 絞り込み条件を使って候補店をルーレットで選択

### ⭐ 3. お気に入り機能（非同期）
- 店舗詳細からワンクリックでお気に入り登録
- 非同期通信（fetch API）でページ遷移なしで更新
- お気に入り一覧画面も実装

### 📜 4. 履歴機能
- 回したルーレットの結果を自動保存
- 非同期削除
- 履歴一覧画面あり

### 👑 5. 管理者パネル（Admin）
- ユーザー一覧 / 削除一覧
- 検索・フィルタリング
- Excel エクスポート機能（Maatwebsite/Laravel-Excel）
- Soft Delete 対応

---

## 🛠 Tech Stack（使用技術）

### Backend
- PHP 8.x
- Laravel 10.x
- Eloquent ORM
- Laravel Breeze（認証）

### Frontend
- Tailwind CSS
- Alpine.js
- JavaScript（Fetch API 非同期処理）
- Blade Templates

### Infrastructure
- Docker / Docker Compose  
- MySQL 8.x  
- phpMyAdmin  
- Nginx or Apache（環境に応じて）

### Others
- Google Maps JavaScript API
- CSRF 対策
- キャッシュバスティング（?v=ファイルmtime）
- GitHub

---
