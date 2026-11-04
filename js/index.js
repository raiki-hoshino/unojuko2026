document.addEventListener('DOMContentLoaded', function () {
  const input    = document.querySelector('.password-input');
  const eyeClose = document.querySelector('.eye-close'); // 斜線の目（今は隠れている状態）
  const eyeOpen  = document.querySelector('.eye-open');  // 開いた目（今は見えている状態）

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


// $(function() {
// 	// 表示する
// 	if(eyeClose.on('click', function() {
// 		eyeClose.css('display', 'none');
// 		eyeOpen.show();
// 		input.attr('type','text');
// 	}));
	
//     // 非常時にする
// 	if($eyeOpen.on('click', function() {
// 		eyeOpen.css('display', 'none');
// 		eyeClose.show();
// 		input.attr('type','password');
// 	}));
//  });
});
