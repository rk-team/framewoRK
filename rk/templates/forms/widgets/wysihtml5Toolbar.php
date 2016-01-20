<div class="wysihtml5ToolBar">
	<?php if (!empty($toolButtons['bold'])): ?>
		<div class="wysihtml5ToolButton" data-wysihtml5-command="bold">
			<?php echo i18n('form.wysiwyg.bold')?> 
		</div>
	<?php endif;?>
	
	<?php if (!empty($toolButtons['italic'])): ?>
		<div class="wysihtml5ToolButton" data-wysihtml5-command="italic">
			<?php echo i18n('form.wysiwyg.italic')?> 
		</div>
	<?php endif;?>
	
	<?php if (!empty($toolButtons['justifyCenter'])): ?>
		<div class="wysihtml5ToolButton" data-wysihtml5-command="justifyCenter">
			<?php echo i18n('form.wysiwyg.justify_center')?> 
		</div>
	<?php endif;?>
	
	<?php if (!empty($toolButtons['justifyLeft'])): ?>
		<div class="wysihtml5ToolButton" data-wysihtml5-command="justifyLeft">
			<?php echo i18n('form.wysiwyg.justify_left')?> 
		</div>
	<?php endif;?>
	
	<?php if (!empty($toolButtons['justifyRight'])): ?>
		<div class="wysihtml5ToolButton" data-wysihtml5-command="justifyRight">
			<?php echo i18n('form.wysiwyg.justify_right')?>
		</div>
	<?php endif;?>
	
	<?php if (!empty($toolButtons['orderedList'])): ?>
		<div class="wysihtml5ToolButton" data-wysihtml5-command="insertOrderedList">
			<?php echo i18n('form.wysiwyg.ordered_list')?> 
		</div>
	<?php endif;?>
	
	<?php if (!empty($toolButtons['unorderedList'])): ?>
		<div class="wysihtml5ToolButton" data-wysihtml5-command="insertUnorderedList">
			<?php echo i18n('form.wysiwyg.unordered_list')?> 
		</div>
	<?php endif;?>
	
	<?php if (!empty($toolButtons['tagName'])): ?>
		<?php foreach ($toolButtons['tagName'] as $oneTagName):?>
			<div class="wysihtml5ToolButton" data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="<?php echo $oneTagName ?>">
				<?php echo i18n('doc.wysiwyg.' . $oneTagName) ?>
			</div>
		<?php endforeach;?>
	<?php endif;?>
			
	<?php if (!empty($toolButtons['nbColors'])): ?>
		<?php for ($i=1; $i<=$toolButtons['nbColors']; $i++):?>
			<div class="wysihtml5ToolButton <?php echo 'wysiwyg-color-' . $i ?>" data-wysihtml5-command="foreColor" data-wysihtml5-command-value="<?php echo $i?>">
				<?php echo i18n('form.wysiwyg.color.' . $i)?>
			</div>
		<?php endfor;?>
	<?php endif;?>
	
	<?php if (!empty($toolButtons['code'])): ?>
		<?php foreach ($toolButtons['code'] as $oneClass):?>
			<div class="wysihtml5ToolButton" data-wysihtml5-command="addTagWithClass" data-wysihtml5-command-value="code:<?php echo $oneClass ?>">
				<?php echo i18n('doc.wysiwyg.code_' . $oneClass) ?>
			</div>
		<?php endforeach;?>
	<?php endif;?>
		
	<?php if (!empty($toolButtons['createLink'])): ?>
		<a class="wysihtml5ToolLink" data-wysihtml5-command="createLink"><?php echo i18n('form.wysiwyg.create_link')?></a>
		<div data-wysihtml5-dialog="createLink" style="display: none;">
			<label>
		    	Link:
		    	<input data-wysihtml5-dialog-field="href" value="http://" class="text">
		    </label>
		    <a data-wysihtml5-dialog-action="save"><?php echo i18n('form.OK')?></a> <a data-wysihtml5-dialog-action="cancel"><?php echo i18n('form.cancel')?></a>
		</div>
	<?php endif;?>
</div>
