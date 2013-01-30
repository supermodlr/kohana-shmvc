<?php $controller->view('header'); ?>

<div class='main'>

<?php /* $controller->block(array('uri'=> '/nav', 'referer'=> $controller->request->uri())); */ ?>

<?php $controller->view('nav'); ?>

<?php echo $controller->content(); ?>

<?php $controller->view('footer'); ?>
</div>