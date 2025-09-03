# 実装手順
## EC2インスタンスに接続
<<<<<<< HEAD
```
ssh ec2-user@{IPアドレス} -i C:\Users\ktc\Desktop\{秘密鍵ファイルのパス}
```
とpowershellに入れる
```
Last login: Wed Sep  3 00:30:32 2025 from 160.86.244.53
[ec2-user@ip-172-31-28-24 ~]$
```
と表示されて接続完了

- EC2インスタンスにvimをインストール
```
sudo yum install vim -y
```
でインストール

- screenをインストール
```
sudo yum install screen -y
```
でインストール

- Docker系のインストール
```
sudo yum install -y docker
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -a -G docker ec2-user
```
dockerをインストールしdockerグループに追加
usermodを反映するために一度ログアウトする

- Docker Composeのインストール
```
sudo mkdir -p /usr/local/lib/docker/cli-plugins/
sudo curl -SL https://github.com/docker/compose/releases/download/v2.36.0/docker-compose-linux-x86_64 -o /usr/local/lib/docker/cli-plugins/docker-compose
sudo chmod +x /usr/local/lib/docker/cli-plugins/docker-compose
```
インストールできたかの確認
```
docker compose version
```

## 準備はできたので構築していく
### 配信するファイルを置くディレクトリを作成
```
mkdir public
mkdir public/0730
```

### 設定ファイルを作る
- compose.ymlはhttps://github.com/hyosetsu/EC2/blob/main/compose.yml から
- Dockerfileはhttps://github.com/hyosetsu/EC2/blob/main/Dockerfile から
- nginx/conf.d/default.confはhttps://github.com/hyosetsu/EC2/blob/main/nginx/conf.d/default.conf から

### ファイルを作る
```
vim public/0730/bbsimagetest.php
```
でファイルを編集する
https://github.com/hyosetsu/EC2/blob/main/public/0730/bbsimagetest.php
を書く

```
vim public/0730/style.css
```
でcssファイルを編集する
https://github.com/hyosetsu/EC2/blob/main/public/0730/style.css
を書く

### データベースを作る
```
docker compose exec mysql mysql example_db
```
でmysqlに接続
example_dbがすでに選ばれているので
```
CREATE TABLE IF NOT EXISTS bbs_entries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  body TEXT NOT NULL,
  image_filename VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  CREATE TABLE bbs_entries (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  body TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```
でテーブルを作る
