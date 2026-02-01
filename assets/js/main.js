$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Confirm delete actions
    $('.btn-delete').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Format currency
    window.formatCurrency = function(amount) {
        return '₱' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    };
    
    // Format date/time
    window.formatDateTime = function(datetime) {
        const date = new Date(datetime);
        return date.toLocaleString('en-PH', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };
    
    // Modal functions
    window.openModal = function(modalId) {
        $('#' + modalId).fadeIn();
    };
    
    window.closeModal = function(modalId) {
        $('#' + modalId).fadeOut();
    };
    
    // Close modal when clicking outside
    $('.modal').on('click', function(e) {
        if (e.target === this) {
            $(this).fadeOut();
        }
    });
    
    // Close button
    $('.close').on('click', function() {
        $(this).closest('.modal').fadeOut();
    });
});