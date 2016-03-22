<!-- Block combinationdropbox -->
{if !empty($combinationdropbox)}
  <div class="combinationdropbox">
  <div class="cdb_section individual">
    <div class="select">{$combinationdropbox.individual.section_name}
    </div>
    <div class="dropbox">
      <ul>
        {foreach $combinationdropbox.individual.groups as $c}
          <label><input type="checkbox" class="selected" data-group="{$c.gr}" data-yes="{$c['Yes']}" data-no="{$c['No']}" data-name="{$c.name}">{$c.lang}<span class="price">{$c.price}$</span></label><div class="clr"></div>
        {/foreach}
      </ul>
      <div class="mid-divider"></div>
      <div class="helper_text">Please select the part/ parts you require. All parts are injection moulded with OEM grade ABS Plastic and pre drilled for precision fitment.<br/>
        All individual parts are unpainted, please select paint in the next selection box.</div>
    </div>
  </div>
  <div class="cdb_section painted">
    <div class="select">
      {$combinationdropbox.painted.section_name}
    </div>
    <div class="dropbox">
      <ul>
        <li class="selected" data-group="{$combinationdropbox.painted.gr}" data-attr="{$combinationdropbox.painted['No'].attr}">Unpainted</li>
        <li  data-group="{$combinationdropbox.painted.gr}" data-attr="{$combinationdropbox.painted['Painted'].attr}">Painted<span class="price">{$combinationdropbox.painted.price}$</span> </li>
      </ul>
      <div class="mid-divider"></div>
      <div class="helper_text">Please choose either painted or unpainted, if painted is chosen, please send good quality photos of the design that you require to the email address below with your Order ID in the description.</div>
    </div>
  </div>
  <div style="float: none; clear: both"></div>
  </div>
  <script type="text/javascript">
    {literal}
    $(document).ready(function(){
      setTimeout(function() {
        //убираем выбор со всех полей дропбокса
        $('.cdb_section input[data-no]').removeClass('selected').removeAttr('checked');
        $('.cdb_section input[data-no]').parent().removeClass('checked');
        $('#attributes').find('fieldset li input').each(function (i, e) {
          var attr = $(e).val();
          /*//Убираем выбор на включённом аттрибуте
          if($('.cdb_section li[data-yes=' + attr + ']').length) {
            $(e).parent().removeClass('checked');
          } else { // Ставим галку на отключенном атрибуте
            $(e).parent().addClass('checked');
          }*/
           //Код для выставления выбраных атрибутов в дропбокс
          var checked = $(e).parent().hasClass('checked');
          if (checked) {
            $('.cdb_section input[data-yes=' + attr + ']').addClass('selected').attr('checked','checked');
            $('.cdb_section input[data-yes=' + attr + ']').parent().addClass('checked');
            $('.cdb_section input[data-no=' + attr + ']').removeClass('selected').removeAttr('checked');
            $('.cdb_section input[data-no=' + attr + ']').parent().removeClass('checked');
          }
        });
//        setTimeout(function() {$($('.cdb_section.painted li')[0]).click(); }, 1000);
        $($('.cdb_section.painted li')[0]).click();
      }, 1500 );
      {/literal}
      {foreach $combinationdropbox.individual.groups as $c }
      {literal}
      $('input[name=group_{/literal}{$c.gr}{literal}]').parents('.attribute_fieldset').hide();

      {/literal}
      {/foreach}
      {literal}

      $('input[name=group_{/literal}{$combinationdropbox.painted.gr}{literal}]').parents('.attribute_fieldset').hide();
      $('.cdb_section.individual input').click(function(e){
        var $this = $(this);
        var gr = $this.data('group');
        var y = $this.data('yes');
        var n = $this.data('no');
        $this.toggleClass('selected');
//        var selected = $this.hasClass('selected');
        var selected = !$this.parent().hasClass('checked');
        $('input[name=group_'+gr+']').removeAttr('checked');
        $('input[name=group_'+gr+']').parent().removeClass('checked');
        if(selected) {
          $('input[value=' + y + ']').parent().addClass('checked');
          $('input[value=' + y + ']').attr('checked','checked');
          findCombination();
        } else {
          $('input[value=' + n + ']').parent().addClass('checked');
          $('input[value=' + n + ']').attr('checked','checked');
          findCombination();
        }
      });

      $('.cdb_section.painted li').click(function(e) {
        var $this = $(this);
        var gr = $this.data('group');
        var attr = $this.data('attr');
//        $this.toggleClass('selected');
        $('.cdb_section.painted li').removeClass('selected');
        $this.addClass('selected');
        $('input[name=group_' + gr + ']').parent().removeClass('checked');
        $('input[value=' + attr + ']').parent().addClass('checked');
        findCombination();
      });
      $('.cdb_section.individual .select').click(function(e){
        $('.cdb_section.painted .dropbox').slideUp('fast');
        var $this = $(this);
        $this.siblings('.dropbox').slideToggle();
      });
      $('.cdb_section.painted .select').click(function(e){
        $('.cdb_section.individual .dropbox').slideUp('fast');
        var $this = $(this);

        $this.siblings('.dropbox').slideToggle();
      });
      $('body').click(function(e){
        if(!$('.cdb_section').find(e.target).length) {
          $('.cdb_section .dropbox').slideUp('fast');
        }
      });
    });
    {/literal}

  </script>
  <style rel="stylesheet">

  </style>
{/if}

<!-- /Block combinationdropbox -->
