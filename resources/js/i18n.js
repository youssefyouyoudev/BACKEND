import arabic from '../lang/ar.json';

export const t = (key, replacements = {}) => {
    const locale = document.documentElement.lang.toLowerCase();
    let value = locale.startsWith('ar') ? (arabic[key] || key) : key;

    Object.entries(replacements).forEach(([name, replacement]) => {
        value = value.replaceAll(`:${name}`, String(replacement));
    });

    return value;
};

export const choice = (singular, plural, count) => t(count === 1 ? singular : plural, { count });
