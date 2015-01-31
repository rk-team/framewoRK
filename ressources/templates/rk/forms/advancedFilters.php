<form class="form advancedFilters <?php echo $className ?>"  data-type="<?php echo $dataType ?>" method="<?php echo $method ?>" action="<?php echo $destination ?>" id="<?php echo $formId ?>" <?php if($hasFiles) echo ' enctype="multipart/form-data" '?>>

	<?php if(!empty($errors)): ?>
	<div class="error">
	<?php foreach($errors as $oneError): ?>
		<div><?php echo i18n($oneError, array(), array('htmlentities' => true)); ?></div>
	<?php endforeach; ?>
	</div>
	<?php endif; ?>
		
	<?php $hiddens = array(); ?>
	<div class="filters">
	<?php foreach($widgets  as $oneWidget): ?>
		<?php if(!$oneWidget instanceof \rk\form\widget\hidden): ?>
		<?php
		$operators = $oneWidget->getParam('operator');
		$opAttr = implode(',', $operators);
		$opAttr = ' data-operators="' . $opAttr . '" '; 
		?>
		<div class="widget">
			<span class="label"><?php echo $oneWidget->getLabelOutput() ?></span>
			<span class="filter" <?php echo $opAttr?>><?php echo $oneWidget->getWidgetOutput() ?></span>
		</div>
		<?php else: ?>
		<?php $hiddens[] = $oneWidget; ?>
		<?php endif; ?>
	<?php endforeach; ?>
	</div>
	
	<?php if(!empty($hiddens)): ?>
		<?php foreach($hiddens as $oneHidden): ?>
		<?php echo $oneHidden->getWidgetOutput() ?> 
		<?php endforeach; ?>
	<?php endif; ?>
	<?php if(empty($subFormsOutput)): // no submit button here if there are subForms ?>
	<div class="buttons">
		<input type="submit" class="button submit" value="<?php echo i18n($submitName, array(), array('htmlentities' => true)) ?>" />
		<input type="reset" class="button reset" value="<?php echo i18n($resetName, array(), array('htmlentities' => true)) ?>" />
				
		<?php if(!empty($params['presets'])): ?>
		<div class="presets">
			<select>
				<?php foreach($params['presets'] as $key => $value): ?>
				<option value="<?php echo $key ?>"><?php echo i18n($key) ?></option>
				<?php endforeach; ?>
			</select>
			<button type="button" class="button load"><?php echo i18n('form.presets_load') ?></button>
		</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>
	
	<?php if(!empty($subFormsOutput)): ?>
	<?php echo $subFormsOutput ?>
	<input type="submit" class="button submit" value="<?php echo i18n($submitName, array(), array('htmlentities' => true)) ?>" />
	<?php endif; ?>
</form>

<?php if(!empty($JSONParams)): ?>
<script type="text/javascript">
rk.util.advancedFilters.getInstance(<?php echo $JSONParams ?>);
</script>
<?php endif; ?>
