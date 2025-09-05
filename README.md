# J-Grants Integration Plugin

WordPress plugin for integrating with J-Grants (Japan Grants) public API to automatically fetch subsidy data and generate high-quality content using Gemini AI.

## 機能 (Features)

- **自動データ取得**: J-Grants公開APIから補助金データを自動取得
- **AI コンテンツ生成**: Gemini AIを使用した高品質なコンテンツ生成
- **カスタム投稿タイプ**: 助成金専用の投稿タイプとタクソノミー
- **一括処理**: 下書きの一括生成と公開機能
- **ヘルスチェック**: システムステータスとプラグインの健全性チェック
- **デバッグモード**: 詳細なログとエラー追跡

## システム要件 (System Requirements)

- WordPress 5.0 以上
- PHP 7.2 以上
- MySQL 5.6 以上
- cURL 拡張モジュール
- JSON 拡張モジュール

## インストール (Installation)

1. プラグインフォルダを `/wp-content/plugins/` ディレクトリにアップロード
2. WordPress管理画面でプラグインを有効化
3. 管理メニューの「JGrants連携」から設定を行う

## 初期設定 (Initial Setup)

1. **API設定**
   - 管理画面 → JGrants連携 → AI設定
   - Gemini API キーを入力

2. **フィールドマッピング**
   - 管理画面 → JGrants連携 → フィールドマッピング
   - APIデータとWordPressフィールドのマッピング設定

3. **デザイン設定**
   - 管理画面 → JGrants連携 → デザイン設定
   - アクセントカラーやバッジカラーの設定

## 使い方 (Usage)

### ショートコード

```
[jgrants_list count="5" category="startup" prefecture="tokyo"]
```

**パラメータ:**
- `count` - 表示件数 (デフォルト: 5)
- `category` - カテゴリースラッグ
- `prefecture` - 都道府県スラッグ
- `tag` - タグスラッグ
- `order` - 並び順 (DESC/ASC)
- `orderby` - 並び替え基準 (date/title)

### Gutenbergブロック

ブロックエディタで「JGrants List」ブロックを追加

## トラブルシューティング (Troubleshooting)

### プラグインが有効化できない場合

1. **システムステータスの確認**
   - 管理画面 → JGrants連携 → システムステータス
   - エラーや警告をチェック
   - 「自動修復を実行」ボタンをクリック

2. **デバッグモードの有効化**
   - `wp-config.php` に以下を追加:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

3. **ログファイルの確認**
   - `/wp-content/plugins/jgrants-integration/logs/` フォルダ内のログファイルを確認

### よくある問題と解決方法

- **「Class not found」エラー**: ファイルの権限を確認し、読み取り可能にする
- **API接続エラー**: サーバーのcURL設定とSSL証明書を確認
- **メモリ不足エラー**: PHPメモリ制限を増やす (最小128MB推奨)

## 開発者向け情報 (Developer Information)

### フック

**アクション:**
- `jgrants_before_sync` - 同期処理の前
- `jgrants_after_sync` - 同期処理の後
- `jgrants_post_created` - 投稿作成後

**フィルタ:**
- `jgrants_api_data` - APIデータのフィルタリング
- `jgrants_ai_content` - AI生成コンテンツのフィルタリング
- `jgrants_post_data` - 投稿データのフィルタリング

### デバッグ

デバッグクラスを使用したログ記録:

```php
JGrants_Debug::info('Information message');
JGrants_Debug::error('Error message');
JGrants_Debug::log_api('endpoint', $request, $response);
```

## ライセンス (License)

GPL v2 or later

## サポート (Support)

問題が解決しない場合は、以下の情報と共にサポートにお問い合わせください：
- WordPressバージョン
- PHPバージョン
- エラーメッセージ
- デバッグ情報（システムステータスページからエクスポート）

## 更新履歴 (Changelog)

### Version 1.0.0
- 初回リリース
- J-Grants API統合
- Gemini AI統合
- カスタム投稿タイプとタクソノミー
- ヘルスチェック機能
- デバッグモード