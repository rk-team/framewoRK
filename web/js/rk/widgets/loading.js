(function() {
	
	rk.widgets.loading.addOverlay = function(mContainer) {
		var oDiv = document.createElement('div'),
			oContainer,
			iWidth,
			iHeight,
			oPosition,
			sContainer,
			iZIndex;
		
		sContainer = rk.util.getDOMQueryString(mContainer);

		// one overlayLoading is already set for this container
		if(!rk.util.isEmpty(self.oIntervals[sContainer])) {
			return;
		}
		
		oContainer = rk.util.getContainer(mContainer);
		iZIndex = rk.util.getZIndex(oContainer);
		
		iWidth = $(oContainer).outerWidth();
		iHeight = $(oContainer).outerHeight();
		oPosition = $(oContainer).position();
		
		$(oDiv).addClass('overlayLoading');
		$(oDiv).css('width', iWidth);
		$(oDiv).css('height', iHeight);
		$(oDiv).css('overflow', 'hidden');
		$(oDiv).css('line-height', iHeight + 'px');
		$(oDiv).css('position', 'absolute');
		$(oDiv).css('top', oPosition.top);
		$(oDiv).css('left', oPosition.left);
		$(oDiv).css('z-index', iZIndex + 10);
		
		$(oDiv).css('margin-top', $(oContainer).css('margin-top'));
		$(oDiv).css('margin-right', $(oContainer).css('margin-right'));
		$(oDiv).css('margin-bottom', $(oContainer).css('margin-bottom'));
		$(oDiv).css('margin-left', $(oContainer).css('margin-left'));
		
		$(oDiv).css('border-top-left-radius', $(oContainer).css('border-top-left-radius'));
		$(oDiv).css('border-top-right-radius', $(oContainer).css('border-top-right-radius'));
		$(oDiv).css('border-bottom-left-radius', $(oContainer).css('border-bottom-left-radius'));
		$(oDiv).css('border-bottom-right-radius', $(oContainer).css('border-bottom-right-radius'));
		
		$(oDiv).html('<span style="max-height: ' + iHeight + 'px"><div class="loadingSprite"></div></span>');
		
		$(oContainer).parent().prepend(oDiv);

		// we keep the timer
		self.oIntervals[sContainer] = {
			iIndex: 1,
			oContainer: oDiv,
			oTimer: setInterval(function() {
				self.loadingTick(sContainer);
			}, 100)
		};
	};
	
	rk.widgets.loading.removeOverlay = function(mContainer) {
		var sContainer = rk.util.getDOMQueryString(mContainer),
			oContainer;
		
		if(rk.util.isEmpty(self.oIntervals[sContainer])) {
			return;
		}
		
		oContainer = self.oIntervals[sContainer].oContainer;
		
		// clear interval and delete the entree
		clearInterval(self.oIntervals[sContainer].oTimer);
		delete self.oIntervals[sContainer];
		
		// removing overlay
		$(oContainer).remove();
	};
	
	rk.widgets.loading.hasOverlay = function (mContainer) {
		
		sContainer = rk.util.getDOMQueryString(mContainer);
		// one overlay is already set for the container
		if(!rk.util.isEmpty(self.oIntervals[sContainer])) {
			return true;
		}
		return false;
	};
	
	rk.widgets.loading.loadingTick = function(sContainer) {		
		var iIndex = self.oIntervals[sContainer].iIndex,
			oContainer = self.oIntervals[sContainer].oContainer,
			sClass,
			oLoading;
		
		oLoading = $('.loadingSprite', oContainer);
		
		// removing precedent class
		sClass = 'step' + iIndex;
		oLoading.removeClass(sClass);
		
		if(iIndex == 12) {
			iIndex = 1;
		} else {
			iIndex++;
		}
		
		// adding new class for the next step
		sClass = 'step' + iIndex;
		oLoading.addClass(sClass);
		
		self.oIntervals[sContainer].iIndex = iIndex;
	};
	
	rk.widgets.loading.oIntervals = {};
	
	var self = rk.widgets.loading;
	
})();