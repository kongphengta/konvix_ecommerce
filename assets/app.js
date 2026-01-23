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
