var ButtonLoader = (function() {

	ButtonLoader.prototype.interval = null;

	ButtonLoader.prototype.originalText = '';

	ButtonLoader.prototype.disabledElement = null;

	function ButtonLoader(element, loadingText) {
		this.element = element;
		this.loadingText = loadingText;
		this.originalText = this.element.is('input') ? this.element.val() : this.element.html();
	}

	ButtonLoader.prototype.disable = function(element) {
		this.disabledElement = element;
		if (this.disabledElement.is('input') || this.disabledElement.is('button')) {
		  return this.disabledElement.prop('disabled', true);
		}
	};

	ButtonLoader.prototype.start = function() {
		var loading, tail,
		  _this = this;
		this.updateText(this.loadingText);
		tail = '.';
		loading = function() {
		  if (tail.length > 3) tail = '';
		  _this.updateText(_this.loadingText + tail);
		  return tail += '.';
		};
		return this.interval = setInterval(loading, 350);
	};

	ButtonLoader.prototype.stop = function() {
		clearInterval(this.interval);
		this.updateText(this.originalText);
		if (this.disabledElement) return this.disabledElement.prop('disabled', false);
	};

	ButtonLoader.prototype.updateText = function(text) {
		if (this.element.is('input')) {
		  return this.element.val(text);
		} else {
		  return this.element.html(text);
		}
	};

	return ButtonLoader;

})();