<div class="panel-group" id="accordion">
  <?php if( have_rows('faqs','option') ) : $i = 0; while ( have_rows('faqs','option') ) : the_row();$i++;?>
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $i;?>">
        <?php the_sub_field('question');?></a>
      </h4>
    </div>
    <div id="collapse<?php echo $i;?>" class="panel-collapse collapse">
      <div class="panel-body"><?php the_sub_field('answer');?></div>
    </div>
  </div>
  <?php endwhile;endif;?>
</div>