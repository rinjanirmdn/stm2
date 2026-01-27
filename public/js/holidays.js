/**
 * Indonesia Holidays Helper - JavaScript
 * Provides holiday data for calendar components
 */

// Global function to get Indonesian holidays
window.getIndonesiaHolidays = function() {
    // This will be populated by the server-side code
    // For now, return empty object - will be populated by controller
    return window.indonesiaHolidays || {};
};

// Store holidays globally for reuse
window.indonesiaHolidays = {};

// Function to update holidays from server
window.updateIndonesiaHolidays = function(holidays) {
    window.indonesiaHolidays = holidays || {};
};

// Helper function to check if date is holiday
window.isIndonesiaHoliday = function(dateStr) {
    return window.indonesiaHolidays[dateStr] || false;
};

// Helper function to get holiday name
window.getIndonesiaHolidayName = function(dateStr) {
    return window.indonesiaHolidays[dateStr] || null;
};
