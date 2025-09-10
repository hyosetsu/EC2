<?php
$dbh = new PDO('mysql:host=mysql;dbname=example_db', 'root', '');

// 投稿処理
if (isset($_POST['body'])) {
  $image_filename = null;

  if (!empty($_POST['image_base64'])) {
    $base64 = preg_replace('/^data:.+base64,/', '', $_POST['image_base64']);
    $image_binary = base64_decode($base64);

    // 保存先のファイル名生成
    $image_filename = strval(time()) . bin2hex(random_bytes(25)) . '.jpg';
    $filepath = '/var/www/upload/image/' . $image_filename;
    file_put_contents($filepath, $image_binary);
  }

  // DBに保存
  $insert_sth = $dbh->prepare("INSERT INTO bbs_entries (body, image_filename) VALUES (:body, :image_filename)");
  $insert_sth->execute([
    ':body' => $_POST['body'],
    ':image_filename' => $image_filename,
  ]);

  // リダイレクト（二重送信防止）
  header("HTTP/1.1 302 Found");
  header("Location: ./preview.php");
  exit;
}

// 過去の投稿取得
$select_sth = $dbh->prepare('SELECT * FROM bbs_entries ORDER BY created_at DESC');
$select_sth->execute();

// 本文のフィルター関数
function bodyFilter(string $body): string {
  $body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
  $body = nl2br($body);
  $body = preg_replace('/&gt;&gt;(\d+)/', '<a href="#entry$1">&gt;&gt;$1</a>', $body);
  return $body;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>画像投稿できる掲示板</title>
</head>
<body>
  <!-- 投稿フォーム -->
  <form method="POST" action="./preview.php" enctype="multipart/form-data">
    <textarea name="body" required placeholder="本文を入力してください"></textarea>
    
    <div style="margin: 1em 0;">
      <input type="file" accept="image/*" name="image" id="imageInput">
      
      <!-- プレビューエリア -->
      <div id="imagePreviewArea" style="display:none; margin-top:1em;">
        <p>選択中の画像プレビュー：</p>
        <canvas id="imagePreviewCanvas" style="border:1px solid #ccc;"></canvas>
      </div>
    </div>

    <!-- 縮小用canvas（非表示） -->
    <canvas id="imageCanvas" style="display:none;"></canvas>
    <!-- base64データを送信するhidden input -->
    <input type="hidden" name="image_base64" id="imageBase64Input">

    <button type="submit">送信</button>
  </form>

  <hr>

  <!-- 投稿一覧 -->
  <?php foreach($select_sth as $entry): ?>
    <dl style="margin-bottom: 1em; padding-bottom: 1em; border-bottom: 1px solid #ccc;">
      <dt id="entry<?= htmlspecialchars($entry['id']) ?>">ID</dt>
      <dd><?= $entry['id'] ?></dd>
      <dt>日時</dt>
      <dd><?= $entry['created_at'] ?></dd>
      <dt>内容</dt>
      <dd>
        <?= bodyFilter($entry['body']) ?>
        <?php if(!empty($entry['image_filename'])): ?>
          <div>
            <img src="/image/<?= htmlspecialchars($entry['image_filename']) ?>" style="max-height:10em;">
          </div>
        <?php endif; ?>
      </dd>
    </dl>
  <?php endforeach; ?>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const imageInput = document.getElementById("imageInput");
  const previewArea = document.getElementById("imagePreviewArea");
  const previewCanvas = document.getElementById("imagePreviewCanvas");
  const imageBase64Input = document.getElementById("imageBase64Input");
  const canvas = document.getElementById("imageCanvas");

  imageInput.addEventListener("change", () => {
    previewArea.style.display = "none";
    if (imageInput.files.length < 1) return;

    const file = imageInput.files[0];
    if (!file.type.startsWith("image/")) return;

    const reader = new FileReader();
    const image = new Image();

    reader.onload = () => {
      image.onload = () => {
        const originalWidth = image.naturalWidth;
        const originalHeight = image.naturalHeight;
        const maxLength = 2000;

        // 縮小サイズ計算
        if (originalWidth <= maxLength && originalHeight <= maxLength) {
          canvas.width = originalWidth;
          canvas.height = originalHeight;
        } else if (originalWidth > originalHeight) {
          canvas.width = maxLength;
          canvas.height = maxLength * originalHeight / originalWidth;
        } else {
          canvas.width = maxLength * originalWidth / originalHeight;
          canvas.height = maxLength;
        }

        const context = canvas.getContext("2d");
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        imageBase64Input.value = canvas.toDataURL("image/jpeg", 0.9);

        // プレビュー描画
        previewCanvas.height = 200;
        previewCanvas.width = 200 * originalWidth / originalHeight;
        const previewContext = previewCanvas.getContext("2d");
        previewContext.clearRect(0, 0, previewCanvas.width, previewCanvas.height);
        previewContext.drawImage(image, 0, 0, previewCanvas.width, previewCanvas.height);

        previewArea.style.display = "";
      };
      image.src = reader.result;
    };
    reader.readAsDataURL(file);
  });
});
</script>
</body>
</html>

