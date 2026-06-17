export function toAbsoluteUrl(url) {
    if (!url) return url;

    try {
        return new URL(url, window.location.origin).toString();
    } catch (error) {
        console.error('Invalid stream URL:', url, error);
        return url;
    }
}

export function detectStreamType(url, explicitType = null) {
    const sourceUrl = String(url || '');
    const clean = sourceUrl.split('?')[0].toLowerCase();
    const type = String(explicitType || '').trim().toLowerCase();

    if (type && type !== 'auto') {
        if (['hls', 'm3u', 'm3u8'].includes(type)) return 'hls';
        if (['mpegts', 'ts', 'stream', 'channel_proxy'].includes(type)) return 'mpegts';
        if (type === 'mp4') return 'mp4';
        return type;
    }

    if (clean.endsWith('.m3u8') || sourceUrl.toLowerCase().includes('m3u8')) return 'hls';
    if (clean.endsWith('.ts') || clean.endsWith('.mpegts') || clean.includes('/live/')) return 'mpegts';
    if (clean.endsWith('.mp4')) return 'mp4';

    return 'auto';
}

export function getBufferedAhead(video) {
    try {
        const current = video.currentTime;
        for (let index = 0; index < video.buffered.length; index += 1) {
            const start = video.buffered.start(index);
            const end = video.buffered.end(index);
            if (current >= start && current <= end) return Math.max(0, end - current);
        }
    } catch {
        // A changing MediaSource can invalidate ranges while they are being inspected.
    }

    return 0;
}
