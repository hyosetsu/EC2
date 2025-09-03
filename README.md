# 実装手順
## EC2インスタンスに接続
ssh ec2-user@{IPアドレス} -i C:\Users\ktc\Desktop\{秘密鍵ファイルのパス}
とpowershellに入れる
Last login: Wed Sep  3 00:30:32 2025 from 160.86.244.53
[ec2-user@ip-172-31-28-24 ~]$
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
### まずは設定ファイルから
- compose.ymlはhttps://github.com/hyosetsu/EC2/blob/main/compose.ymlから
- Dockerfileはhttps://github.com/hyosetsu/EC2/blob/main/Dockerfileから
- nginx/conf.d/default.confはhttps://github.com/hyosetsu/EC2/blob/main/nginx/conf.d/default.confから
  作成



