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
				<div><?php echo i18n($oneError, array(), array('htmlentities' => true)); ?></div>
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
</table>