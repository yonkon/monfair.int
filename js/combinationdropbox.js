/**
 * Created by Vlaimip on 29.01.2016.
 */
$('.combinationdropbox_pr_buy').click(function(e) {
  var $form = $(this).parents('form');
  $.ajax({
    url : '/',
    data : $form.serialize()
  })
    .success(function(data){})
    .error(function(data, data2){
      console.dir(data);
    })
});
