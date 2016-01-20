<?php if(!empty($active)): ?>
<span><?php echo $text ?></span>
<?php else: 
$class = '';
if(!empty($class)) {
	$class = ' class="' . $class . '" ';
}
?>
<a <?php echo $class ?> href="<?php echo htmlentities($URL) ?>"><?php echo $text ?></a>
<?php endif; ?>