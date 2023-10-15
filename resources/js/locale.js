export function getCurrentLocale() {
    console.log(localStorage);
    return localStorage.getItem('locale') || 'en'; // Default to English
}

export function setCurrentLocale(locale) {
    localStorage.setItem('locale', locale);
}
