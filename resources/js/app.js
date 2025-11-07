import './bootstrap';
import { createApp } from 'vue';
import AppShellApp from './components/AppShellApp.vue';
import DashboardApp from './components/DashboardApp.vue';
import LoginApp from './components/LoginApp.vue';
import UserFormApp from './components/UserFormApp.vue';
import PegawaiFormApp from './components/PegawaiFormApp.vue';
import PegawaiIndexApp from './components/PegawaiIndexApp.vue';
import LoginActivityIndexApp from './components/LoginActivityIndexApp.vue';
import SkpdFormApp from './components/SkpdFormApp.vue';
import GajiFormApp from './components/GajiFormApp.vue';
import TppFormApp from './components/TppFormApp.vue';
import TppCalculationFormApp from './components/TppCalculationFormApp.vue';
import EbupotPreviewApp from './components/EbupotPreviewApp.vue';
import DigitalBookFormApp from './components/DigitalBookFormApp.vue';
import VideoTutorialFormApp from './components/VideoTutorialFormApp.vue';
import ResourceIndexApp from './components/ResourceIndexApp.vue';
import GajiIndexApp from './components/GajiIndexApp.vue';
import GajiShowApp from './components/GajiShowApp.vue';
import TppShowApp from './components/TppShowApp.vue';
import EbupotArchiveApp from './components/EbupotArchiveApp.vue';
import UserIndexApp from './components/UserIndexApp.vue';
import TppCalculationIndexApp from './components/TppCalculationIndexApp.vue';

const hydrateProps = (element) => {
    const defaults = {
        routes: {
            home: '/',
            login: '/login',
            captcha: '/captcha',
        },
    };

    if (!element || !element.dataset.props) {
        return defaults;
    }

    try {
        return {
            ...defaults,
            ...JSON.parse(element.dataset.props),
        };
    } catch (error) {
        console.error('Gagal mengurai properti Vue:', error);
        return defaults;
    }
};

const mountVueApp = (elementId, component) => {
    const element = document.getElementById(elementId);

    if (!element) {
        return;
    }

const app = createApp(component, hydrateProps(element));
    app.mount(element);
};

mountVueApp('app-shell-root', AppShellApp);
mountVueApp('dashboard-root', DashboardApp);
mountVueApp('login-root', LoginApp);
mountVueApp('user-form-root', UserFormApp);
mountVueApp('pegawai-form-root', PegawaiFormApp);
mountVueApp('pegawai-index-root', PegawaiIndexApp);
mountVueApp('skpd-form-root', SkpdFormApp);
mountVueApp('gaji-form-root', GajiFormApp);
mountVueApp('tpp-form-root', TppFormApp);
mountVueApp('tpp-calculation-root', TppCalculationFormApp);
mountVueApp('tpp-calculation-index-root', TppCalculationIndexApp);
mountVueApp('gaji-ebupot-root', EbupotPreviewApp);
mountVueApp('tpp-ebupot-root', EbupotPreviewApp);
mountVueApp('digital-book-form-root', DigitalBookFormApp);
mountVueApp('video-tutorial-form-root', VideoTutorialFormApp);
mountVueApp('digital-books-index-root', ResourceIndexApp);
mountVueApp('video-tutorials-index-root', ResourceIndexApp);
mountVueApp('gaji-index-root', GajiIndexApp);
mountVueApp('tpp-index-root', GajiIndexApp);
mountVueApp('gaji-show-root', GajiShowApp);
mountVueApp('tpp-show-root', TppShowApp);
mountVueApp('gaji-ebupot-index-root', EbupotArchiveApp);
mountVueApp('tpp-ebupot-index-root', EbupotArchiveApp);
mountVueApp('user-index-root', UserIndexApp);
mountVueApp('login-activity-index-root', LoginActivityIndexApp);
