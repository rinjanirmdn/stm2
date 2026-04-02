/**
 * Search Highlight Utility
 * Shared across all pages to highlight matching search terms in table cells.
 * Replicates the orange highlight from Activity Logs (logs-index.js).
 */

/**
 * Walk text nodes inside an element and wrap matches with <mark class="st-search-highlight">.
 * @param {HTMLElement} element
 * @param {string} searchTerm
 */
export function highlightTextInNode(element, searchTerm) {
    var walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT, null, false);
    var nodesToReplace = [];
    var lowerSearch = searchTerm.toLowerCase();

    while (walker.nextNode()) {
        var node = walker.currentNode;
        var text = node.textContent;
        var lowerText = text.toLowerCase();
        if (lowerText.indexOf(lowerSearch) !== -1) {
            nodesToReplace.push(node);
        }
    }

    nodesToReplace.forEach(function (textNode) {
        var text = textNode.textContent;
        var lowerText = text.toLowerCase();
        var fragment = document.createDocumentFragment();
        var lastIndex = 0;

        var idx = lowerText.indexOf(lowerSearch, lastIndex);
        while (idx !== -1) {
            if (idx > lastIndex) {
                fragment.appendChild(document.createTextNode(text.substring(lastIndex, idx)));
            }
            var mark = document.createElement('mark');
            mark.className = 'st-search-highlight';
            mark.textContent = text.substring(idx, idx + searchTerm.length);
            fragment.appendChild(mark);
            lastIndex = idx + searchTerm.length;
            idx = lowerText.indexOf(lowerSearch, lastIndex);
        }
        if (lastIndex < text.length) {
            fragment.appendChild(document.createTextNode(text.substring(lastIndex)));
        }

        textNode.parentNode.replaceChild(fragment, textNode);
    });
}

/**
 * Highlight matching search terms in all cells of a <tbody>.
 * Removes any previous highlights first.
 * @param {HTMLElement} tbody - The <tbody> element to search within.
 * @param {string} searchTerm - The search string to highlight (min 2 chars).
 */
export function highlightSearchInTable(tbody, searchTerm) {
    if (!tbody) return;

    var rows = tbody.querySelectorAll('tr');
    rows.forEach(function (row) {
        var cells = row.querySelectorAll('td');
        cells.forEach(function (cell) {
            // Remove existing highlights
            var marks = cell.querySelectorAll('mark.st-search-highlight');
            marks.forEach(function (mark) {
                var parent = mark.parentNode;
                parent.replaceChild(document.createTextNode(mark.textContent), mark);
                parent.normalize();
            });

            if (!searchTerm || searchTerm.length < 2) return;

            highlightTextInNode(cell, searchTerm);
        });
    });
}
