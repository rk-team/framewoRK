<div id="<?php echo $containerId ?>">

	<div id="<?php echo $toolBarId ?>" style="display: none;">
	   <?php echo $rkHelper->includeFormWidgetTpl('wysihtml5Toolbar.php', array('toolButtons' => $toolButtons)) ?>
	</div>
	
	<textarea <?php echo $classAttribute . $disabledAttribute ?> name="<?php echo $name ?>" id="<?php echo $id ?>"><?php echo $value ?></textarea>	
</div>

<script type="text/javascript">
	var oEditor = new rk.widgets.wysihtml5({
		sContainerId:  "<?php echo $containerId ?>",
		sInputId:	   "<?php echo $id ?>",
		sToolBarId:    "<?php echo $toolBarId ?>",
		sEditorCss:    <?php echo $editorCss ?>,
		oJSParams:     <?php echo json_encode($jsParams) ?>
	});
</script>
