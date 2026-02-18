// Animation et feedback pour la wishlist (page Mes Favoris)
import { Toast } from 'bootstrap';

$(document).ready(function () {
    // Animation de suppression de favori
    $('.wishlist-remove-form').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $card = $form.closest('.wishlist-card');
        var productId = $form.data('product-id');
        // Animation CSS
        $card.addClass('wishlist-removing');
        // Envoi du POST via AJAX
        $.post($form.attr('action'), $form.serialize(), function () {
            setTimeout(function () {
                $card.fadeOut(400, function () {
                    $card.parent().remove();
                    // Afficher le toast Bootstrap
                    var toastEl = document.getElementById('wishlist-toast');
                    if (toastEl) {
                        var toast = Toast.getOrCreateInstance(toastEl);
                        toast.show();
                    }
                });
            }, 200);
        });
    });

    // ...existing code...
});
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import 'bootstrap/dist/css/bootstrap.min.css';
import './styles/app.css';
import './styles/profile.css';
import $ from 'jquery';
global.$ = global.jQuery = $;

// Chart.js pour les graphiques statistiques
import Chart from 'chart.js/auto';
window.Chart = Chart;
console.log('Webpack Encore is running!');

$(document).ready(function () {
    // Miniature click: change main image and update border
    $('.thumb-img').on('click', function () {
        var newSrc = $(this).attr('src');
        $('#mainProductImage').attr('src', newSrc);
        $('.thumb-img').css('border', '2px solid #eee').removeClass('border-primary');
        $(this).css('border', '2px solid #0072ff').addClass('border-primary');
    });
});
