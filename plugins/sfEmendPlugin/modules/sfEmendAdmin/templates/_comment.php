<?php $comment = $sf_emend_comment ?>
<?php use_helper('Date') ?>
<div<?php if(!$comment->getIsPublic()): ?> class="moderated"<?php endif; ?>>
  <h4><?php echo $comment->getTitle() ?></h4>
  
  <?php echo $comment->getBody() ?>
  <br />
  <i><?php echo __('Posted on %2%, by %1%', array(
    '%1%'  => $comment->getAuthorName(),
    '%2%'  => format_date($comment->getCreatedAt('U'))
    )) ?>
  </i>
  <br/>
  link al <a href="http://<?php echo sfConfig::get('sf_site_url') ?>/<?php echo join('/', split('_', $comment->getUrl())) ?>">documento</a>
  <br />
  <?php echo __('Selection string: ' . $comment->getSelection()) ?>
</div>
