import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['dropzone', 'input', 'file']
    
    async initialize() {
        this.liveComponent = await getComponent(this.element);
    }
    
    connect() {
        this.dropzoneTargets.forEach((dropzone) => {
            dropzone.addEventListener('dragover', (e) => this.dragover(e, dropzone));
            dropzone.addEventListener('dragleave', (e) => this.dragleave(e, dropzone));
        });
        
        this.dropzoneTarget.addEventListener('drop', this.drop.bind(this));
        
        window.addEventListener('file:move', (e) => {
            e.dataTransfer.setData('path', e.detail.path);
        });
        
        this.timer = null;
        this.currentPath = null;
    }
    
    startTimer(path) {
        console.log('start timer');
        clearTimeout(this.timer);
        this.timer = setTimeout(() => {
            this.liveComponent.action('changePath', {path});
        }, 2000)
    }
    
    dragover(e, dropzone) {
        e.preventDefault();
        console.log('dragover');
        let path = dropzone.getAttribute('data-path');
        const animation = dropzone.getAttribute('data-animation');
        
        if (animation === 'true') {
            dropzone.classList.add('dragover');
        }
        
        if (path) {
            if (this.currentPath !== path) {
                this.currentPath = path;
                this.startTimer(path);
            }
        }
    }
    
    dragleave(e, dropzone) {
        e.preventDefault();
        
        dropzone.classList.remove('dragover');
        
        if (this.currentPath !== dropzone.getAttribute('data-path')) {
            console.log('clear timer');
            clearTimeout(this.timer);
        }
    }
    
    drop(e) {
        e.preventDefault();
        const toElement = e.toElement;
        // get closest dropzone
        const dropzone = toElement.closest('[data-dropzone-target="dropzone"]');
        const path = dropzone.getAttribute('data-path');
        
        this.dropzoneTargets.forEach((d) => {
            d.classList.remove('dragover');
        });
        
        const file_to_move = e.dataTransfer.getData('path');
        
        if (file_to_move && path) {
            this.liveComponent.action('move', {from: file_to_move, to: path});
        } else {
            this.upload(e.dataTransfer.files, path);
        }
    }
    
    upload(files, path) {
        this.inputTarget.files = files;
        
        this.liveComponent.files("upload[]", this.inputTarget);

        this.liveComponent.action('upload', {path});
    }
}
