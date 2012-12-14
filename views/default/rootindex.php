<!DOCTYPE <?php echo $controller->doctype(); ?>>
<html<?php echo $controller->htmlattrs(); ?>>
	<head>
		<title><?php echo $controller->title(); ?></title>
		<?php echo $controller->metatags(); ?>
		<?php echo $controller->headertags(); ?>
		<?php echo $controller->css(); ?>
		<?php echo $controller->get_js('headertags'); ?>
		<?php echo $controller->get_js('headerinline'); ?>
		<?php echo $controller->get_js('readyinline'); ?>		
		<?php echo $controller->get_js('loadinline'); ?>				
	</head>
	<body<?php echo $controller->bodyattrs(); ?>>
		<?php echo $controller->get_js('bodytags'); ?>
		<?php echo $controller->get_js('bodyinline'); ?>
		<?php echo $controller->body(); ?>
		<?php echo $controller->get_js('footertags'); ?>
		<?php echo $controller->get_js('footerinline'); ?>
	</body>
</html>
