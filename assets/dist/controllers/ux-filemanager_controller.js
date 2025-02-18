import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'lazy' */
export default class extends Controller {
	static values = {data: String, dataMimeType: String}
	static targets = ['modal', 'input', 'preview', 'previewImage', 'previewEmbed']
	
	connect() {
		window.addEventListener('filemanager:choose', this.choose.bind(this));
		
		if (this.dataValue) {
			this.setPreview(this.dataValue, this.dataMimeTypeValue);
		}
	}
	
	choose(e) {
		e.preventDefault();
		
		const input = e.detail.inputId;
		if (input !== this.inputTarget.id) {
			return;
		}
		
		this.inputTarget.value = e.detail.id;
		this.inputTarget.dispatchEvent(new CustomEvent('change', {bubbles: true}));
		this.close();
		
		const preview = e.detail.preview;
		if (preview) {
			this.setPreview(preview, e.detail.mimeType);
		}
	}
	
	setPreview(url, mimeType) {
		this.previewTargets.forEach(target => {
			target.style.display = 'none';
		});
		
		if (!url) {
			return;
		}
		
		let target = this.previewEmbedTarget;
		
		switch (mimeType) {
			case 'image/jpeg':
			case 'image/png':
			case 'image/gif':
			case 'image/svg+xml':
			case 'image/webp':
			case 'image/bmp':
				target = this.previewImageTarget;
				break;
		}
		
		target.src = url;
		target.style.display = 'block';
	}
	
	open() {
		this.modalTarget.style.transition = '';
		this.modalTarget.style.transform = 'scale(1) translateX(0) translateY(0)';
		document.body.style.overflow = 'hidden';
		this.modalTarget.showModal();
	}
	
	close() {
		document.body.style.overflow = '';
		this.modalTarget.close();
	}
	
	resize(e) {
		e.preventDefault();
		
		this.modalTarget.setAttribute('resize', this.modalTarget.getAttribute('resize') === 'true' ? 'false' : 'true');
		
		this.modalTarget.style.transition = 'width, height 0.3s';
		if (this.modalTarget.getAttribute('resize') === 'true') {
			this.modalTarget.style.width = '80%';
			this.modalTarget.style.height = '80%';
		} else {
			this.modalTarget.style.width = '100%';
			this.modalTarget.style.height = '100%';
		}
		
		// Wait for the transition to end
		setTimeout(() => {
			this.modalTarget.style.transition = '';
		}, 300);
	}
	
	minimize(e) {
		e.preventDefault();
		
		this.modalTarget.style.transition = 'transform 0.3s';
		this.modalTarget.style.transform = 'scale(0) translateX(100%) translateY(100%)';
		
		setTimeout(() => {
			this.modalTarget.close();
		}, 300);
	}
	
	reset(e) {
		e.preventDefault();
		
		this.inputTarget.value = '';
		this.inputTarget.dispatchEvent(new CustomEvent('change', {bubbles: true}));
		this.setPreview("");
	}
}
