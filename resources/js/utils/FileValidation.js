/**
 * File validation utilities
 */
export class FileValidator {
    /**
     * Validate filename for invalid characters
     * @param {string} filename - The filename to validate
     * @returns {Object} - Validation result with isValid boolean and error message
     */
    static validateFilename(filename) {
        const result = {
            isValid: true,
            errors: [],
            filename: filename
        };

        // Check for spaces
        if (filename.includes(' ')) {
            result.isValid = false;
            result.errors.push('spaces');
        }

        // Check for parentheses
        if (filename.includes('(') || filename.includes(')')) {
            result.isValid = false;
            result.errors.push('parentheses');
        }

        // Check for Cyrillic characters
        if (/[\u0400-\u04FF]/.test(filename)) {
            result.isValid = false;
            result.errors.push('cyrillic characters');
        }

        // Check for other problematic characters
        const problematicChars = /[<>:"/\\|?*[\]{};=&$#,~`!@%^+]/.test(filename);
        if (problematicChars) {
            result.isValid = false;
            result.errors.push('special characters');
        }

        return result;
    }

    /**
     * Get user-friendly error message
     * @param {Array} errors - Array of error types
     * @returns {string} - Human-readable error message
     */
    static getErrorMessage(errors) {
        if (errors.length === 0) return '';

        const errorMap = {
            'spaces': 'Spaces',
            'parentheses': 'Parentheses ( )',
            'cyrillic characters': 'Cyrillic characters',
            'special characters': 'Special characters (<>:"/\\|?*[]{};=&$#,~`!@%^+)'
        };

        const errorMessages = errors.map(error => errorMap[error] || error);
        
        if (errorMessages.length === 1) {
            return `The filename contains ${errorMessages[0].toLowerCase()}.`;
        } else if (errorMessages.length === 2) {
            return `The filename contains ${errorMessages[0].toLowerCase()} and ${errorMessages[1].toLowerCase()}.`;
        } else {
            const lastError = errorMessages.pop();
            return `The filename contains ${errorMessages.join(', ').toLowerCase()}, and ${lastError.toLowerCase()}.`;
        }
    }

    /**
     * Get sanitized filename suggestion
     * @param {string} filename - The original filename
     * @returns {string} - Sanitized filename suggestion
     */
    static getSanitizedFilename(filename) {
        // Remove extension to work on base name
        const lastDotIndex = filename.lastIndexOf('.');
        const baseName = lastDotIndex > 0 ? filename.substring(0, lastDotIndex) : filename;
        const extension = lastDotIndex > 0 ? filename.substring(lastDotIndex) : '';

        let sanitized = baseName
            // Replace spaces with underscores
            .replace(/\s+/g, '_')
            // Replace parentheses with underscore
            .replace(/[()]/g, '_')
            // Replace Cyrillic characters with Latin equivalents or remove
            .replace(/[\u0400-\u04FF]/g, (char) => {
                // Basic Cyrillic to Latin mapping for common characters
                const cyrillicMap = {
                    'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd',
                    'е': 'e', 'ё': 'yo', 'ж': 'zh', 'з': 'z', 'и': 'i',
                    'й': 'y', 'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n',
                    'о': 'o', 'п': 'p', 'р': 'r', 'с': 's', 'т': 't',
                    'у': 'u', 'ф': 'f', 'х': 'h', 'ц': 'c', 'ч': 'ch',
                    'ш': 'sh', 'щ': 'sch', 'ъ': '', 'ы': 'y', 'ь': '',
                    'э': 'e', 'ю': 'yu', 'я': 'ya',
                    'А': 'A', 'Б': 'B', 'В': 'V', 'Г': 'G', 'Д': 'D',
                    'Е': 'E', 'Ё': 'YO', 'Ж': 'ZH', 'З': 'Z', 'И': 'I',
                    'Й': 'Y', 'К': 'K', 'Л': 'L', 'М': 'M', 'Н': 'N',
                    'О': 'O', 'П': 'P', 'Р': 'R', 'С': 'S', 'Т': 'T',
                    'У': 'U', 'Ф': 'F', 'Х': 'H', 'Ц': 'C', 'Ч': 'CH',
                    'Ш': 'SH', 'Щ': 'SCH', 'Ъ': '', 'Ы': 'Y', 'Ь': '',
                    'Э': 'E', 'Ю': 'YU', 'Я': 'YA'
                };
                return cyrillicMap[char] || '_';
            })
            // Remove other problematic characters
            .replace(/[<>:"/\\|?*[\]{};=&$#,~`!@%^+]/g, '_')
            // Remove consecutive underscores
            .replace(/_+/g, '_')
            // Remove leading/trailing underscores
            .replace(/^_+|_+$/g, '');

        return sanitized + extension;
    }
}
