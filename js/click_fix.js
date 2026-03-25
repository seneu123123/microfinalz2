/**
 * COMPREHENSIVE CLICK/RESPONSE FIX
 * Addresses all click event issues in the MRO system
 */

// Global error handling
window.addEventListener('error', function(event) {
    console.error('[GLOBAL ERROR]', event.error.message);
    if (window.debugLog) {
        window.debugLog('JavaScript Error: ' + event.error.message);
    }
});

window.addEventListener('unhandledrejection', function(event) {
    console.error('[UNHANDLED PROMISE]', event.reason);
    if (window.debugLog) {
        window.debugLog('Unhandled Promise: ' + event.reason);
    }
});

// Enhanced click event handler
function addClickHandler(element, handler, options = {}) {
    if (!element) {
        console.error('[CLICK ERROR] Element not found');
        return false;
    }
    
    // Remove existing click handlers to prevent duplicates
    element.removeEventListener('click', handler);
    
    // Add new click handler with error catching
    const wrappedHandler = function(event) {
        try {
            console.log('[CLICK] Element clicked:', element.tagName, element.className || element.id);
            if (window.debugLog) {
                window.debugLog(`Click: ${element.tagName} ${element.className || element.id}`);
            }
            
            // Prevent default if specified
            if (options.preventDefault) {
                event.preventDefault();
            }
            
            // Call the handler
            handler.call(this, event);
            
        } catch (error) {
            console.error('[CLICK ERROR]', error.message);
            if (window.debugLog) {
                window.debugLog('Click handler error: ' + error.message);
            }
            
            // Show user-friendly error
            if (window.Swal) {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while processing your request.',
                    icon: 'error',
                    timer: 3000,
                    showConfirmButton: false
                });
            }
        }
    };
    
    element.addEventListener('click', wrappedHandler);
    
    // Add visual feedback
    element.style.cursor = 'pointer';
    element.addEventListener('mouseenter', function() {
        this.style.opacity = '0.8';
    });
    element.addEventListener('mouseleave', function() {
        this.style.opacity = '1';
    });
    
    console.log('[CLICK] Handler added to:', element.tagName, element.className || element.id);
    return true;
}

// Safe API call with comprehensive error handling
window.safeApiCall = async function(url, options = {}) {
    try {
        console.log('[API] Calling:', url);
        if (window.debugLog) {
            window.debugLog(`API Call: ${url}`);
        }
        
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('[API] Response:', data);
        
        if (window.debugLog) {
            window.debugLog(`API Response: ${data.success ? 'Success' : 'Failed'}`);
        }
        
        if (!data.success) {
            throw new Error(data.message || 'API request failed');
        }
        
        return data;
    } catch (error) {
        console.error('[API ERROR]', error.message);
        if (window.debugLog) {
            window.debugLog(`API Error: ${error.message}`);
        }
        
        // Show user-friendly error message
        if (window.Swal) {
            Swal.fire({
                title: 'Connection Error',
                text: 'Unable to connect to the server. Please check your connection.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
        
        throw error;
    }
};

// Enhanced element update function
window.safeUpdate = function(elementId, value) {
    try {
        const element = document.getElementById(elementId);
        if (!element) {
            console.error('[UPDATE ERROR] Element not found:', elementId);
            if (window.debugLog) {
                window.debugLog(`Update error: Element #${elementId} not found`);
            }
            return false;
        }
        
        element.textContent = value;
        console.log('[UPDATE] Updated:', elementId, '->', value);
        return true;
    } catch (error) {
        console.error('[UPDATE ERROR]', error.message);
        if (window.debugLog) {
            window.debugLog(`Update error: ${error.message}`);
        }
        return false;
    }
};

// Initialize all click handlers when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('[INIT] DOM ready - initializing click handlers');
    
    // Add click handlers to all buttons
    const buttons = document.querySelectorAll('button, .action-btn, .click-test');
    buttons.forEach((button, index) => {
        addClickHandler(button, function(event) {
            console.log('[BUTTON] Button clicked:', button.textContent.trim());
            if (window.debugLog) {
                window.debugLog(`Button clicked: ${button.textContent.trim()}`);
            }
            
            // Default button behavior
            if (button.textContent.includes('Test')) {
                if (window.Swal) {
                    Swal.fire({
                        title: 'Button Test',
                        text: `Button ${index + 1} is working!`,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            }
        });
    });
    
    // Add click handlers to all stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        addClickHandler(card, function(event) {
            console.log('[STAT] Stat card clicked:', index);
            if (window.debugLog) {
                window.debugLog(`Stat card clicked: ${index}`);
            }
            
            if (window.Swal) {
                const titles = ['Active Work Orders', 'Pending Requests', 'Completed Today', 'Available Technicians'];
                Swal.fire({
                    title: 'Stat Card Clicked',
                    text: titles[index] || 'Unknown stat',
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
    
    // Add click handlers to all table rows
    const tableRows = document.querySelectorAll('.table-row');
    tableRows.forEach((row, index) => {
        addClickHandler(row, function(event) {
            console.log('[ROW] Table row clicked:', index);
            if (window.debugLog) {
                window.debugLog(`Table row clicked: ${index}`);
            }
            
            if (window.Swal) {
                Swal.fire({
                    title: 'Row Selected',
                    text: `Row ${index + 1} selected`,
                    icon: 'info',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });
    });
    
    // Add click handlers to all links
    const links = document.querySelectorAll('a');
    links.forEach((link, index) => {
        if (link.onclick) return; // Skip if already has onclick
        
        addClickHandler(link, function(event) {
            console.log('[LINK] Link clicked:', link.href);
            if (window.debugLog) {
                window.debugLog(`Link clicked: ${link.href}`);
            }
            
            // Allow default link behavior
            return true;
        }, { preventDefault: false });
    });
    
    console.log('[INIT] Click handlers initialized');
    if (window.debugLog) {
        window.debugLog('Click handlers initialized');
    }
});

// Re-initialize click handlers for dynamic content
window.reinitializeClickHandlers = function() {
    console.log('[REINIT] Reinitializing click handlers');
    
    // Remove existing handlers and re-add them
    const buttons = document.querySelectorAll('button, .action-btn');
    buttons.forEach((button) => {
        // Clone and replace to remove all event listeners
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
    });
    
    // Re-add handlers
    setTimeout(() => {
        const event = new Event('DOMContentLoaded');
        document.dispatchEvent(event);
    }, 100);
};

// Fix for SweetAlert2 modals not responding
if (typeof Swal !== 'undefined') {
    // Override Swal.fire to add error handling
    const originalSwalFire = Swal.fire;
    Swal.fire = function(options) {
        try {
            console.log('[SWAL] Showing modal:', options.title || 'Modal');
            if (window.debugLog) {
                window.debugLog(`Modal: ${options.title || 'Modal'}`);
            }
            return originalSwalFire.call(this, options);
        } catch (error) {
            console.error('[SWAL ERROR]', error.message);
            if (window.debugLog) {
                window.debugLog(`SweetAlert error: ${error.message}`);
            }
            return Promise.reject(error);
        }
    };
}

// Fix for Chart.js not responding
if (typeof Chart !== 'undefined') {
    const originalChart = Chart;
    Chart = function(context, config) {
        try {
            console.log('[CHART] Creating chart');
            if (window.debugLog) {
                window.debugLog('Chart created');
            }
            return new originalChart(context, config);
        } catch (error) {
            console.error('[CHART ERROR]', error.message);
            if (window.debugLog) {
                window.debugLog(`Chart error: ${error.message}`);
            }
            return null;
        }
    };
    Chart.prototype = originalChart.prototype;
}

// Fix for Lucide icons not responding
if (typeof lucide !== 'undefined') {
    const originalCreateIcons = lucide.createIcons;
    lucide.createIcons = function() {
        try {
            console.log('[LUCIDE] Creating icons');
            if (window.debugLog) {
                window.debugLog('Lucide icons created');
            }
            return originalCreateIcons.call(this);
        } catch (error) {
            console.error('[LUCIDE ERROR]', error.message);
            if (window.debugLog) {
                window.debugLog(`Lucide error: ${error.message}`);
            }
        }
    };
}

// Global click test function
window.testAllClicks = function() {
    console.log('[TEST] Testing all clickable elements');
    if (window.debugLog) {
        window.debugLog('Testing all clickable elements');
    }
    
    const clickables = document.querySelectorAll('button, .action-btn, .stat-card, .table-row, a');
    let workingCount = 0;
    
    clickables.forEach((element, index) => {
        try {
            // Simulate click
            const event = new MouseEvent('click', {
                bubbles: true,
                cancelable: true,
                view: window
            });
            
            if (element.dispatchEvent(event)) {
                workingCount++;
                console.log(`[TEST] Element ${index + 1}: WORKING`);
                if (window.debugLog) {
                    window.debugLog(`Test: Element ${index + 1} working`);
                }
            } else {
                console.log(`[TEST] Element ${index + 1}: CANCELLED`);
            }
        } catch (error) {
            console.error(`[TEST] Element ${index + 1}: ERROR`, error.message);
        }
    });
    
    console.log(`[TEST] Complete: ${workingCount}/${clickables.length} elements working`);
    if (window.debugLog) {
        window.debugLog(`Test complete: ${workingCount}/${clickables.length} working`);
    }
    
    return workingCount;
};

console.log('[CLICK-FIX] Comprehensive click/response fix loaded');
