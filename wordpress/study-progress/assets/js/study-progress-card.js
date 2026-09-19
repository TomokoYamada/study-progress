(function () {
	'use strict';

	// 学習状況カードと閉じるボタンを取得する
	var card = document.querySelector('[data-study-progress-card]');
	var closeButton = document.querySelector('[data-study-progress-close]');

	// カードが表示されていないページでは何もしない
	if (!card || !closeButton) {
		return;
	}

	// 閉じるボタンが押されたら、このページ上だけカードを非表示にする
	closeButton.addEventListener('click', function () {
		card.classList.add('is-hidden');
	});
}());
