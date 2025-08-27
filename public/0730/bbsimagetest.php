<?php
$dbh = new PDO('mysql:host=mysql;dbname=example_db;charset=utf8mb4', 'root', '');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['body'])) {
  // POSTで送られてくるフォームパラメータ body がある場合

  $image_filename = null;

  if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    // 5MB超過チェックを追加
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($_FILES['image']['size'] > $max_size) {
      header("HTTP/1.1 302 Found");
      header("Location: ./bbsimagetest.php?error=too_large");
      exit;
    }
    
    $tmp_path = $_FILES['image']['tmp_name'];

    // mime_content_typeで実際のファイルタイプをチェック
    $mime_type = mime_content_type($tmp_path);
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!in_array($mime_type, $allowed_types, true)) {
      // 画像以外は弾く
      header("HTTP/1.1 302 Found");
      header("Location: ./bbsimagetest.php?error=invalid_filetype");
      exit;
    }

    // ファイルの拡張子を決定（信頼せずmimeタイプから決定する）
    $extension_map = [
      'image/jpeg' => 'jpg',
      'image/png' => 'png',
      'image/gif' => 'gif',
      'image/webp' => 'webp'
    ];
    $extension = $extension_map[$mime_type];

    // ファイル名生成（時間 + ランダム）
    $image_filename = time() . '_' . bin2hex(random_bytes(10)) . '.' . $extension;
    $filepath = '/var/www/upload/image/' . $image_filename;

    move_uploaded_file($tmp_path, $filepath);
  }

  // insertする
  $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (body, image_filename) VALUES (:body, :image_filename)");
  $insert_sth->execute([
    ':body' => $_POST['body'],
    ':image_filename' => $image_filename,
  ]);

  // リダイレクト
  header("HTTP/1.1 302 Found");
  header("Location: ./bbsimagetest.php");
  exit;
}

// いままで保存してきたものを取得
$select_sth = $dbh->prepare('SELECT * FROM bbs_entries ORDER BY created_at ASC');
$select_sth->execute();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>画像投稿掲示板</title>
  <link rel="stylesheet" href="../css/bbs.css">
</head>
<body>
  <!-- フォームのPOST先はこのファイル自身にする -->
  <form method="POST" action="./bbsimagetest.php" enctype="multipart/form-data" id="postForm">
    <textarea name="body" required placeholder="本文を入力してください"></textarea>
    <div style="margin: 1em 0;">
      <input type="file" accept="image/*" name="image" id="imageInput">
    </div>
    <button type="submit">送信</button>
  </form>

  <!-- エラーメッセージ -->
  <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_filetype'): ?>
    <p class="error">アップロードできるのは画像ファイル（JPEG, PNG, GIF, WebP）のみです。</p>
    <?php elseif (isset($_GET['error']) && $_GET['error'] === 'too_large'): ?>
      <p class="error">5MBを超える画像ファイルはアップロードできません。</p>
  <?php endif ?>

  <hr>

  <!-- 表示部分 -->
  <?php foreach($select_sth as $entry): ?>
    <div class="entry" id="entry-<?= htmlspecialchars($entry['id']) ?>">
      <div class="meta">
        <span class="id">#<?= htmlspecialchars($entry['id']) ?></span>
        <span class="date"><?= htmlspecialchars($entry['created_at']) ?></span>
      </div>
      <div class="body">
        <?= nl2br(preg_replace('/&gt;&gt;(\d+)/', '<a href="#entry-$1">&gt;&gt;$1</a>', htmlspecialchars($entry['body']))) ?>
      </div>
      <?php if (!empty($entry['image_filename'])): ?>
        <div class="image">
          <img src="../image/<?= htmlspecialchars($entry['image_filename']) ?>" alt="投稿画像">
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach ?>

  <script>
  // --- 画像を自動縮小して送信 ---
  document.getElementById('postForm').addEventListener('submit', async function(e) {
    const fileInput = document.getElementById('imageInput');
    if (fileInput.files.length === 0) return; // 画像なし

    const file = fileInput.files[0];
    if (!file.type.startsWith('../image/')) return;

    const img = await createImageBitmap(file);
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    // 縮小比率 (最大幅・高さを1000px)
    const maxSize = 1000;
    let { width, height } = img;
    if (width > maxSize || height > maxSize) {
      if (width > height) {
        height = Math.round(height * maxSize / width);
        width = maxSize;
      } else {
        width = Math.round(width * maxSize / height);
        height = maxSize;
      }
    }

    canvas.width = width;
    canvas.height = height;
    ctx.drawImage(img, 0, 0, width, height);

    // Blobに変換（JPEG品質0.85）
    const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.85));
    const newFile = new File([blob], file.name, { type: 'image/jpeg' });

    // FormDataを作り直す
    const formData = new FormData(this);
    formData.set('image', newFile);

    e.preventDefault();
    fetch(this.action, {
      method: this.method,
      body: formData
    }).then(() => location.reload());
  });
  </script>
</body>
</html>
