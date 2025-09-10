document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("form");
  const imageInput = document.getElementById("imageInput");
  const previewArea = document.getElementById("imagePreviewArea"); // プレビュー表示エリア
  const previewCanvas = document.getElementById("imagePreviewCanvas"); // プレビューcanvas
  const previewContext = previewCanvas.getContext("2d");
  const hiddenCanvas = document.getElementById("imageCanvas"); // 縮小処理用canvas
  const imageBase64Input = document.getElementById("imageBase64Input"); // hidden input

  // --- プレビュー処理 ---
  imageInput.addEventListener("change", () => {
    previewArea.style.display = "none"; // いったん非表示

    if (imageInput.files.length < 1) return;

    const file = imageInput.files[0];
    if (!file.type.startsWith("image/")) return;

    const reader = new FileReader();
    const img = new Image();

    reader.onload = () => {
      img.onload = () => {
        // プレビューは最大 400x300 に縮小
        const maxPreviewWidth = 400;
        const maxPreviewHeight = 300;
        let width = img.width;
        let height = img.height;

        if (width > maxPreviewWidth) {
          height *= maxPreviewWidth / width;
          width = maxPreviewWidth;
        }
        if (height > maxPreviewHeight) {
          width *= maxPreviewHeight / height;
          height = maxPreviewHeight;
        }

        // canvasサイズを設定して描画
        previewCanvas.width = width;
        previewCanvas.height = height;
        previewContext.clearRect(0, 0, width, height);
        previewContext.drawImage(img, 0, 0, width, height);

        // 表示
        previewArea.style.display = "";
      };
      img.src = reader.result;
    };

    reader.readAsDataURL(file);
  });

  // --- 送信時の縮小処理 ---
  form.addEventListener("submit", (e) => {
    if (imageInput.files.length < 1) return; // 画像なしなら何もしない

    e.preventDefault();
    const file = imageInput.files[0];
    if (!file.type.startsWith("image/")) {
      form.submit();
      return;
    }

    const reader = new FileReader();
    const img = new Image();

    reader.onload = () => {
      img.onload = () => {
        const maxLength = 2000; // 縦横2000pxまでに縮小
        let width = img.width;
        let height = img.height;

        if (width > height) {
          if (width > maxLength) {
            height *= maxLength / width;
            width = maxLength;
          }
        } else {
          if (height > maxLength) {
            width *= maxLength / height;
            height = maxLength;
          }
        }

        hiddenCanvas.width = width;
        hiddenCanvas.height = height;
        const ctx = hiddenCanvas.getContext("2d");
        ctx.drawImage(img, 0, 0, width, height);

        // JPEGに変換して hidden input にセット
        imageBase64Input.value = hiddenCanvas.toDataURL("image/jpeg", 0.9);

        form.submit();
      };
      img.src = reader.result;
    };

    reader.readAsDataURL(file);
  });
});

