document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.password-wrap').forEach(function(wrap){
    const input    = wrap.querySelector('.password-input');
    const eyeClose = wrap.querySelector('.eye-close'); // 斜線の目（今は隠れている状態）
    const eyeOpen  = wrap.querySelector('.eye-open');  // 開いた目（今は見えている状態）

    if (!input || !eyeClose || !eyeOpen) return;
    
    // 表示する
    eyeClose.addEventListener('click', function () {
      input.type = 'text';
      eyeClose.style.display = 'none';
      eyeOpen.style.display = 'block';
    });

    // 隠す
    eyeOpen.addEventListener('click', function () {
      input.type = 'password';
      eyeOpen.style.display = 'none';
      eyeClose.style.display = 'block';
    });
  });
});
