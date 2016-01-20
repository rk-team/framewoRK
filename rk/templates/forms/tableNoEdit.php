<table class="formNoEdit"  data-type="<?php echo $dataType ?>">
	<?php if(!empty($params['formTitle'])): ?>
	<caption><?php echo i18n($params['formTitle'], array(), array('htmlentities' => true)) ?></caption>
	<?php endif; ?>
	<?php foreach($widgets  as $oneWidget): ?>
	<?php if(!$oneWidget instanceof \rk\form\widget\hidden): ?>
	<tr>
		<td><?php echo $oneWidget->getLabelOutput() ?></td>
		<td data-col="<?php echo $oneWidget->getBaseName() ?>"><?php echo $oneWidget->getDisplayValue() ?></td>
	</tr>
	<?php endif; ?>
	<?php endforeach; ?>
</table>
<?php if(!empty($subFormsOutput)): ?>
<?php echo $subFormsOutput ?>
<?php endif; ?>