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
$select_sth = $dbh->prepare('SELECT * FROM bbs_entries ORDER BY created_at DESC');
$select_sth->execute();
?>

<title>画像投稿掲示板</title>

<!-- フォームのPOST先はこのファイル自身にする -->
<form method="POST" action="./bbsimagetest.php" enctype="multipart/form-data">
  <textarea name="body" required></textarea>
  <div style="margin: 1em 0;">
    <input type="file" accept="image/*" name="image">
  </div>
  <button type="submit">送信</button>
</form>

<script>
  document.getElementById('imageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
      alert('5MBを超えるファイルはアップロードできません。');
      e.target.value = ''; // ファイル選択を解除
    }
  });
</script>

<!-- エラーメッセージ -->
<?php if (isset($_GET['error'])): ?>
  <?php if ($_GET['error'] === 'invalid_filetype'): ?>
    <p style="color:red;">アップロードできるのは画像ファイル（JPEG, PNG, GIF, WebP）のみです。</p>
  <?php elseif ($_GET['error'] === 'too_large'): ?>
    <p style="color:red;">5MBを超える画像ファイルはアップロードできません。</p>
  <?php endif ?>
<?php endif ?>

<hr>

<?php foreach($select_sth as $entry): ?>
  <dl style="margin-bottom: 1em; padding-bottom: 1em; border-bottom: 1px solid #ccc;">
    <dt>ID</dt>
    <dd><?= htmlspecialchars($entry['id']) ?></dd>
    <dt>日時</dt>
    <dd><?= htmlspecialchars($entry['created_at']) ?></dd>
    <dt>内容</dt>
    <dd>
      <?= nl2br(htmlspecialchars($entry['body'])) // 必ず htmlspecialchars() すること ?>
      <?php if(!empty($entry['image_filename'])): // 画像がある場合は img 要素を使って表示 ?>
      <div>
        <img src="/image/<?= htmlspecialchars($entry['image_filename']) ?>" style="max-height: 10em;">
      </div>
      <?php endif; ?>
    </dd>
  </dl>
<?php endforeach ?>
