<!doctype html>
<html lang="<?php echo $language ?>">
<head>
  <meta charset="utf-8">
  <title><?php echo $title ?></title>
  
<?php echo $jsContent ?>
<?php echo $cssContent ?>
  
</head>
<body>
	<div id="wrapper">
		<div id="header">
		</div>
		
		<div id="mainContainer">
			<?php if(!empty($h1)): ?>
			<div class="mainContainerTitle"><h1><?php echo $h1 ?></h1></div>
			<?php endif; ?>
			<div class="rkContent">
				<?php echo $content ?>
			</div>
		</div>
		
		<div id="footer">
		</div>
	</div>
	<script type="text/javascript">
	rk.box.manage().addLinksHandler();
	<?php echo \rk\webLogger::getLogsJSOutput(); ?>
	</script>
</body>
</html>
