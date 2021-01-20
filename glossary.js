var elements = document.getElementsByClassName('word');
for(var i = 0, len = elements.length; i < len; i++) {
	elements [i].addEventListener('click', function() {
		if(!this.dataset.clicked || this.dataset.clicked == 'false') {
			this.dataset.clicked = 'true';
			this.setAttribute('style', 'font-weight: bold; background: #333; color: #fff; padding: 0.2em 0.5em; border-top-left-radius: 0.3em; border-bottom-left-radius: 0.3em;');
			this.insertAdjacentHTML('afterend', '<span style="background: #666; color: #fff; padding: 0.2em 0.5em; border-top-right-radius: 0.3em; border-bottom-right-radius: 0.3em;"><em>'+this.dataset.part+'</em>. '+this.dataset.definition+'</span>');
		}
		else {
			this.dataset.clicked = 'false';
			this.removeAttribute('style');
			this.parentNode.removeChild(this.nextSibling);
		}
	});
}
