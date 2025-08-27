<?php
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['body'])) {
  $image_filename = null;

  if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    // 5MB制限チェック
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

    // ファイルの拡張子を決定（mimeタイプから決定する）
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

  // データベースに登録
  $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (body, image_filename) VALUES (:body, :image_filename)");
  $insert_sth->execute([
    ':body' => $_POST['body'],
    ':image_filename' => $image_filename,
  ]);

  header("HTTP/1.1 302 Found");
  header("Location: ./bbsimagetest.php");
  exit;
}

// 投稿一覧を取得
$select_sth = $dbh->prepare('SELECT * FROM bbs_entries ORDER BY created_at DESC');
$select_sth->execute();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>画像付き掲示板</title>
  <script>
    // JavaScriptで5MB超過チェック
    document.addEventListener('DOMContentLoaded', function () {
      const fileInput = document.querySelector('input[type="file"]');
      fileInput.addEventListener('change', function () {
        const maxSize = 5 * 1024 * 1024; // 5MB
        if (fileInput.files.length > 0 && fileInput.files[0].size > maxSize) {
          alert('5MBを超える画像ファイルはアップロードできません。');
          fileInput.value = ''; // ファイル選択解除
        }
      });
    });
  </script>
</head>
<body>
  <h1>画像付き掲示板</h1>

  <!-- エラーメッセージ表示 -->
  <?php if (isset($_GET['error'])): ?>
    <?php if ($_GET['error'] === 'invalid_filetype'): ?>
      <p style="color:red;">アップロードできるのは画像ファイル（JPEG, PNG, GIF, WebP）のみです。</p>
    <?php elseif ($_GET['error'] === 'too_large'): ?>
      <p style="color:red;">5MBを超える画像ファイルはアップロードできません。</p>
    <?php endif ?>
  <?php endif ?>

  <!-- フォーム -->
  <form method="POST" action="./bbsimagetest.php" enctype="multipart/form-data">
    <textarea name="body" required placeholder="本文を入力してください" rows="4" cols="40"></textarea>
    <div style="margin: 1em 0;">
      <input type="file" accept="image/*" name="image">
    </div>
    <button type="submit">送信</button>
  </form>

  <hr>

  <!-- 投稿一覧 -->
  <?php foreach($select_sth as $entry): ?>
    <dl style="margin-bottom: 1em; padding-bottom: 1em; border-bottom: 1px solid #ccc;">
      <dt>ID</dt>
      <dd><?= htmlspecialchars($entry['id']) ?></dd>
      <dt>日時</dt>
      <dd><?= htmlspecialchars($entry['created_at']) ?></dd>
      <dt>内容</dt>
      <dd>
        <?= nl2br(htmlspecialchars($entry['body'])) ?>
        <?php if (!empty($entry['image_filename'])): ?>
          <div>
            <img src="/image/<?= htmlspecialchars($entry['image_filename']) ?>" style="max-height: 10em;">
          </div>
        <?php endif; ?>
      </dd>
    </dl>
  <?php endforeach ?>
</body>
</html>
