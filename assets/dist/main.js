import { startStimulusApp } from '@symfony/stimulus-bundle';
import Dialog from '@stimulus-components/dialog'

const app = startStimulusApp();
app.register('dialog', Dialog)
