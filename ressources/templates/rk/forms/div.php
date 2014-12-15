<form class="<?php echo $className ?>" data-type="<?php echo $dataType ?>" method="<?php echo $method ?>" action="<?php echo $destination ?>" id="<?php echo $formId ?>" <?php if($hasFiles) echo ' enctype="multipart/form-data" '?>>
		<?php if(!empty($params['formTitle'])): ?>
		<div class="title"><?php echo i18n($params['formTitle']) ?></div>
		<?php endif; ?>
		<?php if(!empty($successMessage)): ?>
		<div class="success"><?php echo i18n($successMessage); ?></div>
		<?php endif; ?>
		<?php if(!empty($errors)): ?>
		<div class="error">
		<?php foreach($errors as $oneError): ?>
			<div><?php echo i18n($oneError); ?></div>
		<?php endforeach; ?>
		</div>
		<?php endif; ?>
		<?php $hiddens = array(); ?>
		<?php foreach($widgets  as $oneWidget): ?>
		<?php if(!$oneWidget instanceof \rk\form\widget\hidden): ?>
		<div class="widget">
			<span class="label"><?php echo $oneWidget->getLabelOutput() ?></span>
			<span class="widget"><?php echo $oneWidget->getWidgetOutput() ?></span>
			<span class="error"><?php echo $oneWidget->getErrorOutput() ?></span>
		</div>
		<?php else: ?>
		<?php $hiddens[] = $oneWidget; ?>
		<?php endif; ?>
		<?php endforeach; ?>
		
		<?php if(!empty($hiddens)): ?>
		<span>
			<?php foreach($hiddens as $oneHidden): ?>
			<?php echo $oneHidden->getWidgetOutput() ?> 
			<?php endforeach; ?>
		</span>
		<?php endif; ?>
		
		<?php if(empty($subFormsOutput)): // no submit button here if there are subForms ?>
		<div class="buttons">
			<input type="submit" class="button submit" value="<?php echo i18n($submitName, array(), array('htmlentities' => true)) ?>" />
		</div>
		<?php endif; ?>
	
	<?php if(!empty($subFormsOutput)): ?>
	<?php echo $subFormsOutput ?>
	<div class="buttons">
		<input type="submit" class="button submit" value="<?php echo i18n($submitName, array(), array('htmlentities' => true)) ?>"/>
	</div>
	<?php endif; ?>
</form>

<?php if(!empty($JSONParams)): ?>
<script type="text/javascript">
rk.util.form.getInstance(<?php echo $JSONParams ?>);
</script>
<?php endif; ?>
