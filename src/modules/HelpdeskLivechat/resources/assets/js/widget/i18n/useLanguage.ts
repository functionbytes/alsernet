import { useEffect, useState } from 'react';
import { translations, type Language } from './translations';

/**
 * Detects the language from the data-lang attribute in the body tag
 * Falls back to browser language or default 'es'
 */
export function getDetectedLanguage(): Language {
    // Try to get from data-lang attribute on body or html
    const bodyLang = document.body.getAttribute('data-lang');
    if (bodyLang && bodyLang in translations) {
        return bodyLang as Language;
    }

    const htmlLang = document.documentElement.getAttribute('data-lang');
    if (htmlLang && htmlLang in translations) {
        return htmlLang as Language;
    }

    // Try to get from lang attribute
    const htmlLangAttr = document.documentElement.getAttribute('lang');
    if (htmlLangAttr) {
        const langCode = htmlLangAttr.split('-')[0]; // en-US -> en
        if (langCode in translations) {
            return langCode as Language;
        }
    }

    // Try browser language
    const browserLang = navigator.language.split('-')[0];
    if (browserLang in translations) {
        return browserLang as Language;
    }

    // Default to Spanish
    return 'es';
}

/**
 * Hook to get current language and detect it from the page
 */
export function useLanguage(): Language {
    const [language, setLanguage] = useState<Language>(() => getDetectedLanguage());

    useEffect(() => {
        // Check for language changes (useful if body attribute changes dynamically)
        const observer = new MutationObserver(() => {
            const detectedLang = getDetectedLanguage();
            setLanguage(detectedLang);
        });

        observer.observe(document.body, {
            attributes: true,
            attributeFilter: ['data-lang'],
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-lang', 'lang'],
        });

        return () => observer.disconnect();
    }, []);

    return language;
}

/**
 * Helper function to get translation text with support for variable replacement
 */
export function getTranslation(language: Language, key: string, variables?: Record<string, string>): string {
    const keys = key.split('.');
    let value: any = translations[language];

    for (const k of keys) {
        value = value?.[k];
    }

    if (typeof value !== 'string') {
        // Fallback to Spanish
        value = translations.es;
        for (const k of keys) {
            value = value?.[k];
        }
    }

    if (!value) return key;

    // Replace variables like :number, :minutes
    if (variables) {
        let result = value;
        for (const [varKey, varValue] of Object.entries(variables)) {
            result = result.replace(`:${varKey}`, varValue);
        }
        return result;
    }

    return value || key;
}

/**
 * Hook that returns the translation function
 */
export function useTranslation() {
    const language = useLanguage();

    return (key: string, variables?: Record<string, string>) => {
        return getTranslation(language, key, variables);
    };
}
