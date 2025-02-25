import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'lazy' */
export default class extends Controller {
	static values = {data: String, dataMimeType: String}
	static targets = ['modal', 'input', 'inputDelete', 'preview', 'previewImage', 'previewEmbed', 'name']
	
	connect() {
		window.addEventListener('filemanager:choose', this.choose.bind(this));
		
		// if this.inputTarget is file input, listen to change event
		if (this.inputTarget.tagName === 'INPUT' && this.inputTarget.type === 'file') {
			this.inputTarget.addEventListener('change', this.upload.bind(this));
		}
		
		if (this.dataValue) {
			// we preserve the original name if we have value
			this.setPreview(this.dataValue, this.dataMimeTypeValue, false);
		}
		
		if (this.hasInputDeleteTarget) {
			this.handleDeleteInput(this.dataValue !== '');
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
			this.setPreview(preview, e.detail.mimeType, e.detail.name);
		}
	}
	
	setPreview(url, mimeType, name = '') {
		this.previewTargets.forEach(target => {
			target.style.display = 'none';
		});
		
		if (typeof name === 'string') {
			this.nameTarget.innerText = name || '';
		}
		
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
		this.handleDeleteInput(false);
	}
	
	upload(e) {
		e.preventDefault();
		const file = this.inputTarget.files[0];
		if (!file) {
			return;
		}
		
		// set preview
		const reader = new FileReader();
		reader.onload = (e) => {
			this.setPreview(e.target.result, file.type, file.name);
		};
		reader.readAsDataURL(file);
		
		this.handleDeleteInput();
	}
	
	handleDeleteInput(hasValue = true) {
		if (this.hasInputDeleteTarget) {
			this.inputDeleteTarget.value = hasValue ? '0' : '1';
			this.inputDeleteTarget.checked = !hasValue;
			this.inputDeleteTarget.dispatchEvent(new CustomEvent('change', {bubbles: true}));
		}
		
		if (hasValue) {
			this.inputTarget.style.pointerEvents = 'none';
		} else {
			this.inputTarget.style.pointerEvents = 'auto';
		}
	}
}
