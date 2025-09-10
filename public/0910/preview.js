document.addEventListener('DOMContentLoaded', function () {
  const form = document.querySelector('form');
  const fileInput = document.getElementById('imageInput');
  const previewCanvas = document.getElementById('previewCanvas');
  const ctxPreview = previewCanvas.getContext('2d');

  // --- プレビュー処理 ---
  fileInput.addEventListener('change', function () {
    if (fileInput.files.length === 0) {
      ctxPreview.clearRect(0, 0, previewCanvas.width, previewCanvas.height);
      return;
    }

    const file = fileInput.files[0];
    if (!file.type.startsWith('image/')) return;

    const reader = new FileReader();
    reader.onload = function (event) {
      const img = new Image();
      img.onload = function () {
        const maxWidth = 400, maxHeight = 300;
        let width = img.width, height = img.height;
        if (width > maxWidth) {
          height *= maxWidth / width;
          width = maxWidth;
        }
        if (height > maxHeight) {
          width *= maxHeight / height;
          height = maxHeight;
        }
        previewCanvas.width = width;
        previewCanvas.height = height;
        ctxPreview.clearRect(0, 0, width, height);
        ctxPreview.drawImage(img, 0, 0, width, height);
      };
      img.src = event.target.result;
    };
    reader.readAsDataURL(file);
  });

  // --- 送信時の縮小処理 ---
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
        let width = img.width, height = img.height;

        if (width > height) {
          if (width > maxSize) { height *= maxSize / width; width = maxSize; }
        } else {
          if (height > maxSize) { width *= maxSize / height; height = maxSize; }
        }

        canvas.width = width; canvas.height = height;
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
