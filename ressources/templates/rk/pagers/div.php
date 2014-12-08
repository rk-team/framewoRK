<pager id="<?php echo $DOMId ?>">
	<div class="content">
		<?php if(!empty($formFilters)): ?>
		<div class="pagerFilters">
			<div class="filtersHeader"><?php echo i18n('pager.filters_title')?></div>
			<div class="filtersContent"><?php echo $formFilters; ?></div>
		</div>
		<?php endif; ?>
		
		<?php if(!empty($paginationLinks)): ?>
		<div class="pagination">
			<div class="info">
				<?php echo i18n('pager.nb_matches', array('nb' => $nbMatches)) ?> -
				<?php echo i18n('pager.page') . ' : ' . $currentPage . ' / ' . $nbPages; ?> 
			</div>
			<div class="links">
				<?php if(!empty($paginationLinks['left'])): ?>
				<div class="left">
				<?php foreach($paginationLinks['left'] as $one): ?>
				<?php echo $rkHelper->includePagerTpl('paginationLink.php', $one)?>
				<?php endforeach; ?>
				</div>
				<?php endif; ?>
				
				<?php if(!empty($paginationLinks['numbers'])): ?>
				<?php foreach($paginationLinks['numbers'] as $one): ?>
				<?php echo $rkHelper->includePagerTpl('paginationLink.php', $one)?>
				<?php endforeach; ?>
				<?php endif; ?>
				
				<?php if(!empty($paginationLinks['right'])): ?>
				<div class="right">
				<?php foreach($paginationLinks['right'] as $one): ?>
				<?php echo $rkHelper->includePagerTpl('paginationLink.php', $one)?>
				<?php endforeach; ?>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php endif;?>
		<?php if(!empty($data)): ?>
			<?php foreach($data as $oneData): ?>
				<div class="row" data-pk="<?php echo $oneData[$pk] ?>">
					<?php foreach($columns as $colName => $oneColumn): ?>
						<div class="<?php echo $colName?>"><?php echo $oneColumn->getOutput($oneData); ?></div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		<?php else: ?>
			<?php echo i18n($emptyMessage) ?>
		<?php endif; ?>
	</div>
	
	<?php if(!empty($extraButtons)): ?>
	<div class="extraButtons">
		<?php foreach($extraButtons as $name => $oneButton): ?>
		<?php
		$windowTitle = ''; 
		if(!empty($oneButton['windowTitle'])) {
			$windowTitle = ' data-rkWindowTitle="' . str_replace('"', '', $oneButton['windowTitle']) . '" ';
		}?>
		<a class="<?php echo $name ?> <?php if (!empty($oneButton['class'])) { echo $oneButton['class'];} ?>" <?php echo $windowTitle ?> href="<?php echo $oneButton['target'] ?>"><?php echo $oneButton['label']?></a>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
</pager>

<?php if(!empty($JSParams)): ?>
<script type="text/javascript">
rk.widgets.pager.createInstance(<?php echo json_encode($JSParams)?>);
</script>
<?php endif; ?>
