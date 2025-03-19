(function($) {
    'use strict';

    $(document).ready(function() {
        // 로딩 표시 함수
        function showLoading() {
            $('.rocket-sourcer-loading').show();
        }

        // 로딩 숨김 함수
        function hideLoading() {
            $('.rocket-sourcer-loading').hide();
        }

        // 에러 메시지 표시 함수
        function showError(message) {
            $('.rocket-sourcer-error')
                .text(message)
                .show();
        }

        // 성공 메시지 표시 함수
        function showSuccess(message) {
            $('.rocket-sourcer-success')
                .text(message)
                .show();
        }

        // API 키 설정 저장
        $('#rocket-sourcer-settings-form').on('submit', function(e) {
            e.preventDefault();
            showLoading();

            var data = {
                action: 'save_rocket_sourcer_settings',
                api_key: $('#rocket-sourcer-api-key').val(),
                api_secret: $('#rocket-sourcer-api-secret').val(),
                nonce: $('#rocket_sourcer_nonce').val()
            };

            $.post(ajaxurl, data, function(response) {
                hideLoading();
                if (response.success) {
                    showSuccess('설정이 저장되었습니다.');
                } else {
                    showError(response.data.message || '설정 저장에 실패했습니다.');
                }
            }).fail(function() {
                hideLoading();
                showError('서버 오류가 발생했습니다.');
            });
        });

        // 키워드 분석 폼 제출
        $('#rocket-sourcer-keyword-form').on('submit', function(e) {
            e.preventDefault();
            showLoading();

            var data = {
                action: 'analyze_keyword',
                keyword: $('#rocket-sourcer-keyword').val(),
                category: $('#rocket-sourcer-category').val(),
                nonce: $('#rocket_sourcer_nonce').val()
            };

            $.post(ajaxurl, data, function(response) {
                hideLoading();
                if (response.success) {
                    // 결과 표시 로직
                    $('#rocket-sourcer-results').html(response.data.html);
                } else {
                    showError(response.data.message || '키워드 분석에 실패했습니다.');
                }
            }).fail(function() {
                hideLoading();
                showError('서버 오류가 발생했습니다.');
            });
        });

        // 제품 분석 폼 제출
        $('#rocket-sourcer-product-form').on('submit', function(e) {
            e.preventDefault();
            showLoading();

            var data = {
                action: 'analyze_product',
                product_url: $('#rocket-sourcer-product-url').val(),
                nonce: $('#rocket_sourcer_nonce').val()
            };

            $.post(ajaxurl, data, function(response) {
                hideLoading();
                if (response.success) {
                    // 결과 표시 로직
                    $('#rocket-sourcer-results').html(response.data.html);
                } else {
                    showError(response.data.message || '제품 분석에 실패했습니다.');
                }
            }).fail(function() {
                hideLoading();
                showError('서버 오류가 발생했습니다.');
            });
        });
    });
})(jQuery); 