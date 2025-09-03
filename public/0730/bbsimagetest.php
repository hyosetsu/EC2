<?php
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['body'])) {
  $image_filename = null;

  if (isset($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($_FILES['image']['size'] > $max_size) {
      header("HTTP/1.1 302 Found");
      header("Location: ./bbsimagetest.php?error=too_large");
      exit;
    }

    $tmp_path = $_FILES['image']['tmp_name'];
    $mime_type = mime_content_type($tmp_path);
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!in_array($mime_type, $allowed_types, true)) {
      header("HTTP/1.1 302 Found");
      header("Location: ./bbsimagetest.php?error=invalid_filetype");
      exit;
    }

    $extension_map = [
      'image/jpeg' => 'jpg',
      'image/png'  => 'png',
      'image/gif'  => 'gif',
      'image/webp' => 'webp'
    ];
    $extension = $extension_map[$mime_type];

    $image_filename = time() . '_' . bin2hex(random_bytes(10)) . '.' . $extension;
    $filepath = '/var/www/upload/' . $image_filename;

    move_uploaded_file($tmp_path, $filepath);
  }

  $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (body, image_filename) VALUES (:body, :image_filename)");
  $insert_sth->execute([
    ':body' => $_POST['body'],
    ':image_filename' => $image_filename,
  ]);

  header("HTTP/1.1 302 Found");
  header("Location: ./bbsimagetest.php");
  exit;
}

$select_sth->bindValue(':offset', $offset, PDO::PARAM_INT);
$select_sth->execute();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>画像付き掲示板</title>
  <link rel="stylesheet" href="style.css">
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    const fileInput = document.querySelector('input[type="file"]');

    form.addEventListener('submit', function (e) {
      if (fileInput.files.length === 0) return;

      const file = fileInput.files[0];
      if (!file.type.startsWith('image/')) return;

      e.preventDefault();
      const reader = new FileReader();
      reader.onload = function (event) {
        const img = new Image();
        img.onload = function () {
          const canvas = document.createElement('canvas');
          const maxSize = 1920;
          let width = img.width;
          let height = img.height;

          if (width > height) {
            if (width > maxSize) {
              height *= maxSize / width
              width = maxSize;
            }
          } else {
            if (height > maxSize) {
              width *= maxSize / height;
              height = maxSize;
            }
          }

          canvas.width = width;
          canvas.height = height;
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0, width, height);

          canvas.toBlob(function (blob) {
            if (blob.size > 5 * 1024 * 1024) {
              alert("5MB以下に縮小できませんでした。");
              return;
            }

            const newFile = new File([blob], file.name, { type: file.type });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(newFile);
            fileInput.files = dataTransfer.files;

            form.submit();
          }, file.type, 0.85);
        };
        img.src = event.target.result;
      };
      reader.readAsDataURL(file);
    });
  });
  </script>
</head>
<body>
  <h1>画像付き掲示板</h1>

  <?php if (isset($_GET['error'])): ?>
    <?php if ($_GET['error'] === 'invalid_filetype'): ?>
      <p style="color:red;">アップロードできるのは画像ファイル（JPEG, PNG, GIF, WebP）のみです。</p>
    <?php elseif ($_GET['error'] === 'too_large'): ?>
      <p style="color:red;">5MBを超える画像ファイルはアップロードできません。</p>
    <?php endif ?>
  <?php endif ?>

  <form method="POST" action="./bbsimagetest.php" enctype="multipart/form-data">
    <textarea name="body" required placeholder="本文を入力してください" rows="4" cols="40"></textarea>
    <div style="margin: 1em 0;">
      <input type="file" accept="image/*" name="image">
    </div>
    <button type="submit">送信</button>
  </form>

  <hr>

  <?php foreach($select_sth as $entry): ?>
    <div id="post-<?= htmlspecialchars($entry['id']) ?>" class="post">
      <dl>
        <dt>No.</dt>
        <dd><?= htmlspecialchars($entry['id']) ?></dd>
        <dt>日時</dt>
        <dd><?= htmlspecialchars($entry['created_at']) ?></dd>
        <dt>内容</dt>
        <dd>
          <?php
            $body = htmlspecialchars($entry['body'], ENT_QUOTES, 'UTF-8');
            $body = preg_replace_callback('/&gt;&gt;(\d+)/', function($m) {
              $num = $m[1];
              return '<a href="#post-' . $num . '">&gt;&gt;' . $num . '</a>';
            }, $body);
            echo nl2br($body);
          ?>
          <?php if (!empty($entry['image_filename'])): ?>
            <div>
              <img src="/image/<?= htmlspecialchars($entry['image_filename']) ?>" style="max-height: 10em;">
            </div>
          <?php endif; ?>
        </dd>
    </div>
  <?php endforeach ?>

</body>
</html>
