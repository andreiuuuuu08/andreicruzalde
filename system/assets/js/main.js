// Wait for the document to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add Bootstrap validation styles to forms
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.classList.add('fade');
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
    
    // Add fade-in animation to cards
    document.querySelectorAll('.card').forEach(function(card) {
        card.classList.add('fade-in');
    });
    
    // Initialize rating circles
    initRatingCircles();
    
    // Add tooltips to rating elements
    initTooltips();
    
    // Add responsive table wrappers
    document.querySelectorAll('table.table').forEach(function(table) {
        if (!table.parentElement.classList.contains('table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
});

// Initialize rating circles based on score
function initRatingCircles() {
    document.querySelectorAll('[data-rating]').forEach(function(element) {
        const rating = parseInt(element.getAttribute('data-rating'));
        if (rating >= 1 && rating <= 5) {
            element.classList.add('rating-circle', `rating-${rating}`);
            
            // Add screen reader text for accessibility
            const srSpan = document.createElement('span');
            srSpan.className = 'sr-only';
            srSpan.textContent = `Rating: ${rating} out of 5`;
            element.appendChild(srSpan);
        }
    });
}

// Initialize Bootstrap tooltips
function initTooltips() {
    // Check if Bootstrap's tooltip plugin is available
    if (typeof bootstrap !== 'undefined' && typeof bootstrap.Tooltip !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

// Print functionality for reports
function printReport() {
    window.print();
}

// Toggle dark/light mode
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    
    // Save preference to localStorage
    const isDarkMode = document.body.classList.contains('dark-mode');
    localStorage.setItem('darkMode', isDarkMode ? 'enabled' : 'disabled');
}

// Check for saved dark mode preference on load
(function() {
    if (localStorage.getItem('darkMode') === 'enabled') {
        document.body.classList.add('dark-mode');
    }
})();
