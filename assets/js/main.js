// Medical Excuse Management System JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize modals
    var modalList = [].slice.call(document.querySelectorAll('.modal'));
    modalList.map(function (modalEl) {
        return new bootstrap.Modal(modalEl);
    });

    // Handle department change for course filtering
    const departmentSelect = document.getElementById('department');
    const yearSelect = document.getElementById('year');
    const courseSelect = document.getElementById('course_id');

    if (departmentSelect && yearSelect && courseSelect) {
        function loadCourses() {
            const department = departmentSelect.value;
            const year = yearSelect.value;
            
            if (department && year) {
                fetch(`../includes/get_courses.php?department=${department}&year=${year}`)
                    .then(response => response.json())
                    .then(data => {
                        courseSelect.innerHTML = '<option value="">Select Course</option>';
                        data.forEach(course => {
                            courseSelect.innerHTML += `<option value="${course.id}">${course.course_code} - ${course.course_name}</option>`;
                        });
                    })
                    .catch(error => {
                        console.error('Error loading courses:', error);
                        showAlert('Error loading courses', 'danger');
                    });
            } else {
                courseSelect.innerHTML = '<option value="">Select Department and Year first</option>';
            }
        }

        departmentSelect.addEventListener('change', loadCourses);
        yearSelect.addEventListener('change', loadCourses);
    }

    // File upload preview
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById(this.id + '_preview');
            
            if (file && preview) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (file.type.startsWith('image/')) {
                        preview.innerHTML = `<img src="${e.target.result}" class="file-preview" alt="Preview">`;
                    } else {
                        preview.innerHTML = `<div class="alert alert-info">File selected: ${file.name}</div>`;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Confirmation dialogs
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    confirmButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm');
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('tbody tr');
            
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Mark notifications as read
    const notificationItems = document.querySelectorAll('.notification-item');
    notificationItems.forEach(item => {
        item.addEventListener('click', function() {
            const notificationId = this.getAttribute('data-notification-id');
            if (notificationId) {
                fetch('../includes/mark_notification_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ notification_id: notificationId })
                });
                this.classList.remove('unread');
            }
        });
    });
});

// Utility functions
function showAlert(message, type = 'info', duration = 5000) {
    const alertContainer = document.getElementById('alertContainer') || document.body;
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertContainer.insertBefore(alertDiv, alertContainer.firstChild);
    
    if (duration > 0) {
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.style.transition = 'opacity 0.5s';
                alertDiv.style.opacity = '0';
                setTimeout(() => alertDiv.remove(), 500);
            }
        }, duration);
    }
}

function showLoading(show = true) {
    let overlay = document.querySelector('.loading-overlay');
    
    if (show) {
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.innerHTML = '<div class="spinner-border text-primary"></div>';
            document.body.appendChild(overlay);
        }
        overlay.style.display = 'flex';
    } else {
        if (overlay) {
            overlay.style.display = 'none';
        }
    }
}

function formatDate(dateString) {
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

function downloadFile(filename, url) {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// AJAX form submission
function submitForm(formId, successCallback = null) {
    const form = document.getElementById(formId);
    if (!form) return;

    const formData = new FormData(form);
    showLoading(true);

    fetch(form.action || window.location.href, {
        method: form.method || 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showLoading(false);
        if (data.success) {
            showAlert(data.message || 'Operation successful', 'success');
            if (successCallback) successCallback(data);
        } else {
            showAlert(data.message || 'Operation failed', 'danger');
        }
    })
    .catch(error => {
        showLoading(false);
        console.error('Error:', error);
        showAlert('An error occurred. Please try again.', 'danger');
    });
}

// Real-time status updates
function checkForUpdates() {
    if (typeof applicationId !== 'undefined') {
        fetch(`../includes/get_application_status.php?id=${applicationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    updateStatusDisplay(data.status);
                }
            })
            .catch(error => console.error('Status update error:', error));
    }
}

function updateStatusDisplay(status) {
    const statusElement = document.getElementById('current-status');
    if (statusElement) {
        statusElement.className = `badge ${getStatusBadgeClass(status)}`;
        statusElement.textContent = formatStatus(status);
    }
}

function getStatusBadgeClass(status) {
    const statusClasses = {
        'pending': 'badge-warning',
        'admin_reviewed': 'badge-info',
        'admin_rejected': 'badge-danger',
        'hod_approved': 'badge-success',
        'hod_rejected': 'badge-danger',
        'completed': 'badge-primary'
    };
    return statusClasses[status] || 'badge-secondary';
}

function formatStatus(status) {
    const statusTexts = {
        'pending': 'Pending Admin Review',
        'admin_reviewed': 'Sent to HOD',
        'admin_rejected': 'Rejected by Admin',
        'hod_approved': 'Approved by HOD',
        'hod_rejected': 'Rejected by HOD',
        'completed': 'Completed'
    };
    return statusTexts[status] || status.replace('_', ' ');
}

// Set up periodic status checks (every 30 seconds)
setInterval(checkForUpdates, 30000);

// Print functionality
function printPage() {
    window.print();
}

// Export to CSV
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(row => {
        const cells = row.querySelectorAll('td, th');
        const rowData = Array.from(cells).map(cell => {
            return '"' + cell.textContent.replace(/"/g, '""') + '"';
        });
        csv.push(rowData.join(','));
    });

    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    downloadFile(filename, url);
    window.URL.revokeObjectURL(url);
}