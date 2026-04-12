jQuery(document).ready(function($){

    $(document).on('click', '.wpfc-optimize-single', function(e){
        e.preventDefault();

        let btn = $(this);
        let id = btn.data('id');
        let nonce = btn.data('nonce');

        // 🔥 Optimize başlarken EXCLUDE butonunu kaldır
        btn.closest('td')
           .find('.wpfc-exclude-image')
           .remove();
       
        btn.prop('disabled', true).text('0%');

        function updateColumn(id) {
            $.post(WPFC_SINGLE_OPTIMIZE.ajaxurl, {
                action: 'wpfc_get_optimisation_meta',
                id: id,
                nonce: WPFC_SINGLE_OPTIMIZE.nonce
            }, function(response){

                if (response.success) {

                    let col = $("button[data-id='" + id + "']")
                                .closest('td.column-wpfc_image_optimizer, td.field, div.wpfc-attachment-optimizer');
                    
                    // Backend HTML'ini bas
                    col.html(response.data.html);

                    // 🔥 Optimize sonrası otomatik Revert + Exclude butonları ekle
                    col.append(`
                        <button class="button wpfc-revert-image" 
                            data-id="${id}">
                            Revert
                        </button>
                    `);

                } else {
                    console.warn("Meta alınamadı:", response.data);
                }

            });
        }


        function checkProgress() {

            $.post(WPFC_SINGLE_OPTIMIZE.ajaxurl, {
                action: 'wpfc_optimize_single_image',
                id: id,
                nonce: nonce
            }, function(response){

                if (!response.success) {
                    btn.prop('disabled', false).text('Optimize Et');
                    alert(response.data.msg || 'Optimize edilemedi');
                    return;
                }

                let p = parseFloat(response.data.progress);

                btn.text(p + '%');

                if (p < 100) {
                    setTimeout(checkProgress, 500);
                } else {

                    btn.text('✓ Optimize Edildi');
                    btn.css({ background:'#46b450', color:'#fff' });

                    updateColumn(id); // %100 → meta çek → kolon bas
                }
            });

        }

        checkProgress();
    });

});



jQuery(document).ready(function($){
    // sadece bir kez çalışsın: eğer zaten server seçildiyse atla
    var already = false;


    // Trigger determination
    $.ajax({
        url: WPFC_SINGLE_OPTIMIZE.ajaxurl,
        method: 'POST',
        data: {
            action: 'wpfc_determine_server',
            nonce: WPFC_SINGLE_OPTIMIZE.nonce
        },
        success: function(response){
            if (response && response.success) {
                console.log('[WPFC] Nearest server determined:', response.data.url, 'ping:', response.data.ping);
                // isterseniz kullanıcıya görsel bildirim ekleyebilirsiniz
            } else {
                console.warn('[WPFC] Server determination failed:', response && response.data ? response.data : response);
            }
        },
        error: function(xhr){
            console.warn('[WPFC] AJAX error determining server:', xhr.status, xhr.responseText);
        }
    });
});





jQuery(document).ready(function ($) {

    function restoreButtons(col, id) {

        // Kolonun sonuna Optimize + Exclude butonlarını ekle
        col.append(`
            <button class="button wpfc-optimize-single" 
                data-id="${id}" 
                data-nonce="${WPFC_SINGLE_OPTIMIZE.nonce}">
                Optimize
            </button>
        `);

        col.append(`
            <button class="button wpfc-exclude-image" 
                data-id="${id}" 
                data-nonce="${WPFC_SINGLE_OPTIMIZE.nonce}">
                Exclude
            </button>
        `);
    }


    $(document).on("click", ".wpfc-revert-image", function (e) {
        e.preventDefault();

        let btn = $(this);
        let id = btn.data("id");

        btn.text("Reverting...");
        btn.prop("disabled", true);

        $.ajax({
            type: "GET",
            url: WPFC_SINGLE_OPTIMIZE.ajaxurl,
            dataType: "json",
            data: {
                action: "wpfc_revert_image_ajax_request",
                id: id
            },
            cache: false,
            success: function (data) {

                if (data.success == "true") {

                    // Kolonu bul
                    let col = btn.closest('td.column-wpfc_image_optimizer, td.field, div.wpfc-attachment-optimizer');

                    // Var olan HTML'i temizle
                    col.html("");

                    // 🔥 Revert sonrası Optimize + Exclude tuşlarını ekle
                    restoreButtons(col, id);

                } else {
                    alert("Revert failed");
                    btn.prop("disabled", false).text("Revert");
                }
            }
        });

    });

});





jQuery(document).ready(function($){

    // === EXCLUDE ===
    $(document).on("click", ".wpfc-exclude-image", function(e){
        e.preventDefault();

        let btn = $(this);
        let id  = btn.data("id");
        let nonce = btn.data("nonce");

        $.post(ajaxurl, {
            action: "wpfc_exclude_image_ajax",
            id: id,
            nonce: nonce
        }, function(res){
            if(res.success){
                // Optimize + Exclude → Include butonuna dönüşecek
                btn.closest("td").html(
                    `<button class="button wpfc-include-image" 
                        data-id="${id}" 
                        data-nonce="${nonce}">Include</button>`
                );
            }
        });
    });


    // === INCLUDE ===
    $(document).on("click", ".wpfc-include-image", function(e){
        e.preventDefault();

        let btn = $(this);
        let id  = btn.data("id");
        let nonce = btn.data("nonce");

        $.post(ajaxurl, {
            action: "wpfc_include_image_ajax",
            id: id,
            nonce: nonce
        }, function(res){
            if(res.success){
                // Sadece Include → Optimize + Exclude geri gelsin
                btn.closest("td").html(`
                    <button class="button wpfc-optimize-single" 
                        data-id="${id}" data-nonce="${nonce}">
                        Optimize
                    </button>
                    <button class="button wpfc-exclude-image" 
                        data-id="${id}" data-nonce="${nonce}">
                        Exclude
                    </button>
                `);
            }
        });
    });

});
