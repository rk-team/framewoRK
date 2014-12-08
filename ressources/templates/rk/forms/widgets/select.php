<select <?php echo $classAttribute . $disabledAttribute ?> name="<?php echo $name ?>" id="<?php echo $id ?>">
<?php if(!empty($options)): ?>
<?php foreach($options as $optionKey => $optionValue): ?>
	<?php if($value == $optionKey) {
		$selected = ' selected="selected" ';
	} else {
		$selected = '';
	} ?>
	<option value="<?php echo $optionKey ?>" <?php echo $selected ?>><?php echo $optionValue ?></option>
<?php endforeach; ?>
<?php endif; ?>
</select>