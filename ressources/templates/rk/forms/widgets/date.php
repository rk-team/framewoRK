<input type="text" <?php echo $classAttribute . $disabledAttribute; ?> value="<?php if(!empty($value)) echo $value->dbFormat(false); ?>" name="<?php echo $name ?>" id="<?php echo $id; ?>" />

<script type="text/javascript">
new rk.widgets.datepicker({
	mTarget: '#<?php echo $id; ?>'
});
</script>
