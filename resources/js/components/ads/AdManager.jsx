import { observeAdSlots } from './AdSlot.jsx';
import { loadMonetagScripts } from './MonetagLoader.jsx';
import { scheduleRifimediaPopup } from './RifimediaPopup.jsx';
import { mountSmartLinkButtons } from './SmartLinkButton.jsx';
import { mountStickyMobileAd } from './StickyMobileAd.jsx';

function isBlockedPage(config) {
    if (!config?.enabled || config.isAdmin || config.isEmbed || config.isAuthPage) {
        return true;
    }

    return Boolean(config.isWatchPage && config.disableAdsOnWatchPage);
}

function runWhenInteractive(callback) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
        return;
    }

    callback();
}

export function mountAdManager() {
    runWhenInteractive(() => {
        const config = window.RifiAdsConfig || {};

        if (isBlockedPage(config)) {
            return;
        }

        mountStickyMobileAd(config);
        mountSmartLinkButtons(config);
        observeAdSlots();

        const loadMonetag = () => loadMonetagScripts(config);

        if (config.rifimediaPopup?.enabled) {
            scheduleRifimediaPopup(config.rifimediaPopup, loadMonetag);
            window.setTimeout(loadMonetag, 12000);
            return;
        }

        loadMonetag();
    });
}
