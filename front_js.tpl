{combine_script id='jquery.touchSwipe' load='footer' require='jquery' path='plugins/photo_quick_validation/jquery.touchSwipe.js'}

{footer_script}
var swipeDir;

jQuery(function() {
  function qselect_confirm(direction) {
    if (jQuery('.fotorama--fullscreen .confirm').size() == 0) {
      jQuery('.fotorama--fullscreen').append('<a class="confirm"></a>');
    }

    var status = jQuery('.fotorama').data('fotorama').activeFrame['pqv_validated'];
    var qselect_class = '';

    if (direction == 'up') {
      if (typeof status == 'undefined' || status === true) {
        qselect_class = 'qselect-icon-thumbs-up';
      }
    }
    if (direction == 'down') {
      if (typeof status == 'undefined' || status === false) {
        qselect_class = 'qselect-icon-thumbs-down';
      }
    }

    jQuery('.confirm')
      .removeClass()
      .addClass(qselect_class+' confirm')
      .show()
      .delay(500)
      .fadeOut()
    ;

    var image_id = jQuery('.fotorama').data('fotorama').activeFrame['image_id'];

    jQuery.ajax({
      url: "ws.php?format=json&method=pwg.pqv.update",
      type:"POST",
      data: {
        image_id:image_id,
        action:direction
      },
      success:function(data) {
        var data = jQuery.parseJSON(data);

        if (typeof data.result.pqv_validated !== 'undefined') {
          qselect_class = '';

          if (data.result.pqv_validated == 'null') {
            jQuery('.fotorama').data('fotorama').activeFrame['pqv_validated'] = undefined;
            qselect_class = '';
          }

          if (data.result.pqv_validated == 'true') {
            jQuery('.fotorama').data('fotorama').activeFrame['pqv_validated'] = true;
            qselect_class = 'qselect-icon-thumbs-up';
          }

          if (data.result.pqv_validated == 'false') {
            jQuery('.fotorama').data('fotorama').activeFrame['pqv_validated'] = false;
            qselect_class = 'qselect-icon-thumbs-down';
          }

          jQuery('.fotorama__pqv-icon')
            .removeClass()
            .addClass(qselect_class+' fotorama__pqv-icon')
          ;
        }
        else {
          alert('problem on update: '+data.message);
        }
      },
      error:function(XMLHttpRequest, textStatus, errorThrows) {
        alert('(i) problem on update');
      }
    });
  }

  jQuery('.fotorama__stage, #theImage').swipe({
    threshold: 0,

    swipeStatus: function(event, phase, direction, distance, duration, fingerCount){

       var fotorama = jQuery('.fotorama').data('fotorama')
         , active;

       // When direction changes = toggle options.swipe on fotorama instance
       if(direction !== swipeDir){
         swipeDir = direction;
         active = (!(direction == 'up' || direction == 'down'));
         fotorama.setOptions({ swipe: active });
       }

    },

    swipe:function(event, direction, distance, duration, fingerCount, fingerData) {
      if (direction == 'up' || direction == 'down') {
        // console.log('qselect_confirm()', direction);
        qselect_confirm(direction);
      }
    }
  });

  document.onkeydown = function(e) {
    if (e.keyCode == 80) { // "p"
      qselect_confirm('up');
      // return false;
      e.preventDefault();
    }

    if (e.keyCode == 77) { // "m"
      qselect_confirm('down');
      // return false;
      e.preventDefault();
    }

  }
});
{/footer_script}
