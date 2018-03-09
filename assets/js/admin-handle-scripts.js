(function ($, undefined) {
  $(document).ready(function () {

    $('.send_fastery_order').live('click', function (event) {

      order_id = $(this).data('id');
      action   = $(this).data('action');
      $(this).prop('disabled', true);
      $('.ignet-loader').css({'display': 'block'});
      $('.fastery-block-data').css({'opacity': '0.5'});

      // Отправим запрос
      $.ajax({
        url: adminHandle.url,
        type: 'POST',
        data: {
          'action'         : adminHandle.action,
          'order_id'       : order_id,
          'fastery_action' : action,
        },
        success: function (respond, textStatus, jqXHR) {

          $('.ignet-loader').css({'display': 'none'});
          $('.fastery-block-data').css({'opacity': '1'});
          $(this).prop('disabled', false);
          $('#fastery_meta_box .inside').html(respond);
        }
      });
    });

    $('#woocommerce_fastery_clear_fastery_cashe').css({
      'width'  : '150px',
      'cursor' : 'pointer'
    });
    $('#woocommerce_fastery_clear_fastery_cashe').val('Очистить кеш');

    $('#woocommerce_fastery_clear_fastery_cashe').on('click', function () {

      $(this).prop('disabled', true);
      $.ajax({
        url: adminHandle.url,
        type: 'POST',
        data: {
          'action' : 'clear_cashe'
        },
        success: function (respond, textStatus, jqXHR) {

          $('#woocommerce_fastery_clear_fastery_cashe').prop('disabled', false);
        }
      });
    });



  });
})(jQuery);