jQuery(document).ready(function($) {
    let cart = [];

    $('#btn-search').on('click', function() {
        let query = $('#rockola-search-input').val();
        if(query.length < 3) return;

        $.post(rockola_vars.ajax_url, {
            action: 'rockola_search',
            nonce: rockola_vars.nonce,
            query: query
        }, function(res) {
            if(res.success) {
                let html = '';
                res.data.tracks.items.forEach(track => {
                    let image = track.album.images[2] ? track.album.images[2].url : '';
                    html += `
                    <div class="track-item">
                        <img src="${image}" alt="cover">
                        <div class="track-info">
                            <strong>${track.name}</strong><br>
                            <small>${track.artists[0].name}</small>
                        </div>
                        <button class="btn-add-track" 
                            data-id="${track.id}" 
                            data-uri="${track.uri}" 
                            data-name="${track.name}" 
                            data-artist="${track.artists[0].name}" 
                            data-artist-id="${track.artists[0].id}" 
                            data-album="${track.album.name}" 
                            data-image="${image}">
                            +
                        </button>
                    </div>`;
                });
                $('#search-results').html(html);
            }
        });
    });

    $(document).on('click', '.btn-add-track', function(e) {
        e.preventDefault();
        if(cart.length >= 3) {
            alert('Máximo 3 canciones.');
            return;
        }

        let track = $(this).data();
        cart.push(track);
        updateCart();
    });

    function updateCart() {
        $('#cart-count').text(cart.length);
        let html = '';
        cart.forEach((t, index) => {
            html += `<li>${t.name} - ${t.artist} <span style="color:red; cursor:pointer;" onclick="removeTrack(${index})">X</span></li>`;
        });
        $('#cart-list').html(html);
        $('#rockola-cart').show();
    }

    window.removeTrack = function(index) {
        cart.splice(index, 1);
        updateCart();
    }

    $('#btn-submit-rockola').on('click', function() {
        let form = $('#rockola-user-form');
        if(!form[0].checkValidity()) {
            form[0].reportValidity();
            return;
        }

        let formData = {
            name: $('input[name="name"]').val(),
            email: $('input[name="email"]').val(),
            whatsapp: $('input[name="whatsapp"]').val(),
            birthday: $('input[name="birthday"]').val()
        };

        $(this).text('Enviando...').prop('disabled', true);

        $.post(rockola_vars.ajax_url, {
            action: 'rockola_submit',
            nonce: rockola_vars.nonce,
            ...formData,
            tracks: cart
        }, function(res) {
            if(res.success) {
                $('#rockola-container').html('<h2>¡Gracias! Tus canciones sonarán pronto.</h2>');
            } else {
                alert(res.data.message);
                $('#btn-submit-rockola').text('Enviar').prop('disabled', false);
            }
        });
    });
});