<form class="<?php echo $className ?>"  data-type="<?php echo $dataType ?>" method="<?php echo $method ?>" action="<?php echo $destination ?>" id="<?php echo $formId ?>" <?php if($hasFiles) echo ' enctype="multipart/form-data" '?>>
	<table>
		<?php if(!empty($params['formTitle'])): ?>
		<caption><?php echo i18n($params['formTitle'], array(), array('htmlentities' => true)) ?></caption>
		<?php endif; ?>
		<?php if(!empty($successMessage)): ?>
		<tr>
			<td colspan="3">
				<div class="success"><?php echo i18n($successMessage, array(), array('htmlentities' => true)); ?></div>
			</td>
		</tr>
		<?php endif; ?>
		<?php if(!empty($errors)): ?>
		<tr>
			<td colspan="3">
				<div class="error">
				<?php foreach($errors as $oneError): ?>
					<div><?php echo $oneError ?></div>
				<?php endforeach; ?>
				</div>
			</td>
		</tr>
		<?php endif; ?>
		<?php $hiddens = array(); ?>
		<?php foreach($widgets  as $oneWidget): ?>
		<?php if(!$oneWidget instanceof \rk\form\widget\hidden): ?>
		<tr>
			<td><?php echo $oneWidget->getLabelOutput() ?></td>
			<td><?php echo $oneWidget->getWidgetOutput() ?></td>
			<td><?php echo $oneWidget->getErrorOutput() ?></td>
		</tr>
		<?php else: ?>
		<?php $hiddens[] = $oneWidget; ?>
		<?php endif; ?>
		<?php endforeach; ?>
		
		<?php if(!empty($hiddens)): ?>
		<tr>
			<td colspan="3">
			<?php foreach($hiddens as $oneHidden): ?>
			<?php echo $oneHidden->getWidgetOutput() ?> 
			<?php endforeach; ?>
			</td>
		</tr>
		<?php endif; ?>
		<?php if(empty($subFormsOutput)): // no submit button here if there are subForms ?>
		<tr>
			<td colspan="3"><input type="submit" class="button submit" value="<?php echo i18n($submitName, array(), array('htmlentities' => true)) ?>" /></td>
		</tr>
		<?php endif; ?>
	</table>
	<?php if(!empty($subFormsOutput)): ?>
	<?php echo $subFormsOutput ?>
	<input type="submit" class="button submit" value="<?php echo i18n($submitName, array(), array('htmlentities' => true)) ?>"/>
	<?php endif; ?>
</form>

<?php if(!empty($JSONParams)): ?>
<script type="text/javascript">
rk.util.form.getInstance(<?php echo $JSONParams ?>);
</script>
<?php endif; ?>
