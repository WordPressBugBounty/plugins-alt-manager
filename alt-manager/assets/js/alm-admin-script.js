jQuery(document).ready(function($){
$('.home-images-alt').select2({
    width: '75%',
    placeholder: 'Select an option'

});
$.switcher('.settings_page_alt-manager input[type=checkbox]');

// $("ul.select2-selection__rendered").sortable({
//     containment: 'parent',
//     start: function() { $("select2-selection__choice").select2("onSortStart"); },
//     update: function() { $("select2-selection__choice").select2("onSortEnd"); }

//   });

$('.home-images-title').select2({
    width: '75%',
    placeholder: 'Select an option'

});
$('.pages-images-alt').select2({
    width: '75%',
    placeholder: 'Select an option'

});
$('.pages-images-title').select2({
    width: '75%',
    placeholder: 'Select an option'

});
$('.post-images-alt').select2({
    width: '75%',
    placeholder: 'Select an option'

});
$('.post-images-title').select2({
    width: '75%',
    placeholder: 'Select an option'

});
$('.product-images-alt').select2({
    width: '75%',
    placeholder: 'Select an option'

});
$('.product-images-title').select2({
    width: '75%',
    placeholder: 'Select an option'

});
$('.cpt-images-alt').select2({
    width: '75%',
    placeholder: 'Select an option'

});
$('.cpt-images-title').select2({
    width: '75%',
    placeholder: 'Select an option'

});


$("select").on("select2:select", function (evt) {
    var element = evt.params.data.element;
    var $element = $(element);
    
    $element.detach();
    $(this).append($element);
    $(this).trigger("change");
  });

});