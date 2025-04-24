document.addEventListener('DOMContentLoaded', function() {
    // Current page for pagination
    let currentPage = 1;
    
    // Initial load of events
    loadEvents();
    
    // Event listeners for filters
    document.getElementById('eventTypeFilter').addEventListener('change', applyFilters);
    document.getElementById('dateFilter').addEventListener('change', applyFilters);
    document.getElementById('searchEvents').addEventListener('input', debounce(applyFilters, 500));
    document.getElementById('resetFilters').addEventListener('click', resetFilters);
    
    // Event listeners for CRUD operations
    document.getElementById('saveEventBtn').addEventListener('click', saveEvent);
    document.getElementById('updateEventBtn').addEventListener('click', updateEvent);
    document.getElementById('confirmDeleteBtn').addEventListener('click', deleteEvent);
    document.getElementById('exportAttendeesBtn').addEventListener('click', exportAttendees);
    
    // Initialize edit event form with the same fields as add event form
    initializeEditForm();
    
    // Make these functions globally accessible
    window.viewEvent = viewEvent;
    window.editEvent = editEvent;
    window.viewAttendees = viewAttendees;
    window.confirmDelete = confirmDelete;
    window.loadEventsPage = loadEventsPage;
    window.updateAttendeeStatus = updateAttendeeStatus;
    
    /**
     * Load events with optional filters
     */
    function loadEvents(page = 1) {
        currentPage = page;
        
        // Get filter values
        const eventType = document.getElementById('eventTypeFilter').value;
        const eventDate = document.getElementById('dateFilter').value;
        const searchTerm = document.getElementById('searchEvents').value;
        
        // Show loading indicator
        document.getElementById('eventsTableBody').innerHTML = '<tr><td colspan="10" class="text-center">Loading...</td></tr>';
        
        // Prepare data for AJAX request
        const data = new FormData();
        data.append('action', 'getEvents');
        data.append('page', page);
        
        if (eventType) data.append('type', eventType);
        if (eventDate) data.append('date', eventDate);
        if (searchTerm) data.append('search', searchTerm);
        
        // Make AJAX request
        fetch('php/event_handler.php', {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayEvents(data.data.events);
                setupPagination(data.data.pagination);
            } else {
                showAlert('error', 'Failed to load events: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'An error occurred while loading events');
        });
    }
    
    /**
     * Load events for a specific page
     */
    function loadEventsPage(page) {
        loadEvents(page);
        return false; // Prevent default link behavior
    }
    
    /**
     * Display events in the table
     */
    function displayEvents(events) {
        const tableBody = document.getElementById('eventsTableBody');
        
        if (events.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="10" class="text-center">No events found</td></tr>';
            return;
        }
        
        let html = '';
        events.forEach((event, index) => {
            // Format date for display
            const eventDate = new Date(event.event_date);
            const formattedDate = eventDate.toLocaleDateString();
            
            // Determine status class
            let statusClass = '';
            switch(event.status) {
                case 'upcoming':
                    statusClass = 'bg-primary';
                    break;
                case 'ongoing':
                    statusClass = 'bg-success';
                    break;
                case 'completed':
                    statusClass = 'bg-secondary';
                    break;
                case 'cancelled':
                    statusClass = 'bg-danger';
                    break;
            }
            
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${event.name}</td>
                    <td>${capitalizeFirstLetter(event.type)}</td>
                    <td>${formattedDate}</td>
                    <td>${event.event_time}</td>
                    <td>${event.location}</td>
                    <td>${event.capacity}</td>
                    <td>${event.registered_count || 0}</td>
                    <td><span class="badge ${statusClass}">${capitalizeFirstLetter(event.status)}</span></td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-info" onclick="viewEvent(${event.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-primary" onclick="editEvent(${event.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-success" onclick="viewAttendees(${event.id})">
                                <i class="fas fa-users"></i>
                            </button>
                            <button type="button" class="btn btn-danger" onclick="confirmDelete(${event.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        tableBody.innerHTML = html;
    }
    
    /**
     * Set up pagination controls
     */
    function setupPagination(pagination) {
        const paginationElement = document.getElementById('eventsPagination');
        const totalPages = pagination.pages;
        
        let html = '';
        
        // Previous button
        html += `
            <li class="page-item ${pagination.page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="return loadEventsPage(${pagination.page - 1})">Previous</a>
            </li>
        `;
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            html += `
                <li class="page-item ${pagination.page === i ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="return loadEventsPage(${i})">${i}</a>
                </li>
            `;
        }
        
        // Next button
        html += `
            <li class="page-item ${pagination.page === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="return loadEventsPage(${pagination.page + 1})">Next</a>
            </li>
        `;
        
        paginationElement.innerHTML = html;
    }
    
    /**
     * Apply filters and reload events
     */
    function applyFilters() {
        loadEvents(1); // Reset to first page when filters change
    }
    
    /**
     * Reset all filters and reload events
     */
    function resetFilters() {
        document.getElementById('eventTypeFilter').value = '';
        document.getElementById('dateFilter').value = '';
        document.getElementById('searchEvents').value = '';
        loadEvents(1);
    }
    
    /**
     * View event details
     */
    function viewEvent(eventId) {
        // Prepare data for AJAX request
        const data = new FormData();
        data.append('action', 'getEvent');
        data.append('id', eventId);
        
        // Make AJAX request
        fetch('php/event_handler.php', {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Create a modal to display event details
                const event = data.data;
                const eventDate = new Date(event.event_date);
                const formattedDate = eventDate.toLocaleDateString();
                
                let modalHtml = `
                    <div class="modal fade" id="viewEventModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Event Details: ${event.name}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            ${event.image_url ? `<img src="${event.image_url}" class="img-fluid mb-3" alt="${event.name}">` : ''}
                                            <h6>Description:</h6>
                                            <p>${event.description}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <ul class="list-group">
                                                <li class="list-group-item"><strong>Type:</strong> ${capitalizeFirstLetter(event.type)}</li>
                                                <li class="list-group-item"><strong>Date:</strong> ${formattedDate}</li>
                                                <li class="list-group-item"><strong>Time:</strong> ${event.event_time}</li>
                                                <li class="list-group-item"><strong>Location:</strong> ${event.location}</li>
                                                <li class="list-group-item"><strong>Capacity:</strong> ${event.capacity}</li>
                                                <li class="list-group-item"><strong>Status:</strong> ${capitalizeFirstLetter(event.status)}</li>
                                                <li class="list-group-item"><strong>Created:</strong> ${new Date(event.created_at).toLocaleString()}</li>
                                                ${event.updated_at ? `<li class="list-group-item"><strong>Last Updated:</strong> ${new Date(event.updated_at).toLocaleString()}</li>` : ''}
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" onclick="editEvent(${event.id})">Edit Event</button>
                                    <button type="button" class="btn btn-success" onclick="viewAttendees(${event.id})">View Attendees</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Add modal to the document
                const modalContainer = document.createElement('div');
                modalContainer.innerHTML = modalHtml;
                document.body.appendChild(modalContainer);
                
                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('viewEventModal'));
                modal.show();
                
                // Remove modal from DOM when hidden
                document.getElementById('viewEventModal').addEventListener('hidden.bs.modal', function() {
                    document.body.removeChild(modalContainer);
                });
            } else {
                showAlert('error', 'Failed to load event details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'An error occurred while loading event details');
        });
    }
    
    /**
     * Save a new event
     */
    function saveEvent() {
        const form = document.getElementById('addEventForm');
        
        // Basic form validation
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        // Get form data
        const formData = new FormData(form);
        formData.append('action', 'createEvent');
        
        // Handle file upload if present
        const imageFile = document.getElementById('eventImage').files[0];
        if (imageFile) {
            formData.append('eventImage', imageFile);
        }
        
        // Disable save button to prevent double submission
        document.getElementById('saveEventBtn').disabled = true;
        
        // Make AJAX request
        fetch('php/event_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal and reset form
                const modal = bootstrap.Modal.getInstance(document.getElementById('addEventModal'));
                modal.hide();
                form.reset();
                
                // Show success message and reload events
                showAlert('success', 'Event created successfully');
                loadEvents(currentPage);
            } else {
                showAlert('error', 'Failed to create event: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'An error occurred while creating the event');
        })
        .finally(() => {
            // Re-enable save button
            document.getElementById('saveEventBtn').disabled = false;
        });
    }
    
    /**
     * Initialize edit form with the same fields as add form
     */
    function initializeEditForm() {
        const editForm = document.getElementById('editEventForm');
        
        // Create the same fields as in the add form but with different IDs
        editForm.innerHTML = `
            <input type="hidden" id="editEventId" name="eventId">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="editEventName" class="form-label">Event Name</label>
                    <input type="text" class="form-control" id="editEventName" name="eventName" required>
                </div>
                <div class="col-md-6">
                    <label for="editEventType" class="form-label">Event Type</label>
                    <select class="form-select" id="editEventType" name="eventType" required>
                        <option value="">Select Type</option>
                        <option value="workshop">Workshop</option>
                        <option value="seminar">Seminar</option>
                        <option value="conference">Conference</option>
                        <option value="meeting">Meeting</option>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="editEventDate" class="form-label">Date</label>
                    <input type="date" class="form-control" id="editEventDate" name="eventDate" required>
                </div>
                <div class="col-md-6">
                    <label for="editEventTime" class="form-label">Time</label>
                    <input type="time" class="form-control" id="editEventTime" name="eventTime" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                <label for="editEventLocation" class="form-label">Location</label>
                    <input type="text" class="form-control" id="editEventLocation" name="eventLocation" required>
                </div>
                <div class="col-md-6">
                    <label for="editEventCapacity" class="form-label">Capacity</label>
                    <input type="number" class="form-control" id="editEventCapacity" name="eventCapacity" min="1" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="editEventDescription" class="form-label">Description</label>
                <textarea class="form-control" id="editEventDescription" name="eventDescription" rows="3" required></textarea>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="editEventImage" class="form-label">Event Image</label>
                    <input type="file" class="form-control" id="editEventImage" name="eventImage">
                    <small class="form-text text-muted">Leave empty to keep current image</small>
                </div>
                <div class="col-md-6">
                    <label for="editEventStatus" class="form-label">Status</label>
                    <select class="form-select" id="editEventStatus" name="eventStatus" required>
                        <option value="upcoming">Upcoming</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
        `;
    }
    
    /**
     * Load event data for editing
     */
    function editEvent(eventId) {
        // Prepare data for AJAX request
        const data = new FormData();
        data.append('action', 'getEvent');
        data.append('id', eventId);
        
        // Make AJAX request
        fetch('php/event_handler.php', {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate edit form with event data
                const event = data.data;
                document.getElementById('editEventId').value = event.id;
                document.getElementById('editEventName').value = event.name;
                document.getElementById('editEventType').value = event.type;
                document.getElementById('editEventDate').value = event.event_date;
                document.getElementById('editEventTime').value = event.event_time;
                document.getElementById('editEventLocation').value = event.location;
                document.getElementById('editEventCapacity').value = event.capacity;
                document.getElementById('editEventDescription').value = event.description;
                document.getElementById('editEventStatus').value = event.status;
                
                // Close view modal if it's open
                const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewEventModal'));
                if (viewModal) {
                    viewModal.hide();
                }
                
                // Show edit modal
                const modal = new bootstrap.Modal(document.getElementById('editEventModal'));
                modal.show();
            } else {
                showAlert('error', 'Failed to load event details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'An error occurred while loading event details');
        });
    }
    
    /**
     * Update an existing event
     */
    function updateEvent() {
        const form = document.getElementById('editEventForm');
        
        // Basic form validation
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        // Get form data
        const formData = new FormData(form);
        formData.append('action', 'updateEvent');
        
        // Handle file upload if present
        const imageFile = document.getElementById('editEventImage').files[0];
        if (imageFile) {
            formData.append('eventImage', imageFile);
        }
        
        // Disable update button to prevent double submission
        document.getElementById('updateEventBtn').disabled = true;
        
        // Make AJAX request
        fetch('php/event_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('editEventModal'));
                modal.hide();
                
                // Show success message and reload events
                showAlert('success', 'Event updated successfully');
                loadEvents(currentPage);
            } else {
                showAlert('error', 'Failed to update event: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'An error occurred while updating the event');
        })
        .finally(() => {
            // Re-enable update button
            document.getElementById('updateEventBtn').disabled = false;
        });
    }
    
    /**
     * Confirm event deletion
     */
    function confirmDelete(eventId) {
        document.getElementById('deleteEventId').value = eventId;
        const modal = new bootstrap.Modal(document.getElementById('deleteEventModal'));
        modal.show();
    }
    
    /**
     * Delete an event
     */
    function deleteEvent() {
        const eventId = document.getElementById('deleteEventId').value;
        
        // Prepare data for AJAX request
        const data = new FormData();
        data.append('action', 'deleteEvent');
        data.append('id', eventId);
        
        // Disable delete button to prevent double submission
        document.getElementById('confirmDeleteBtn').disabled = true;
        
        // Make AJAX request
        fetch('php/event_handler.php', {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteEventModal'));
                modal.hide();
                
                // Show success message and reload events
                showAlert('success', 'Event deleted successfully');
                loadEvents(currentPage);
            } else {
                showAlert('error', 'Failed to delete event: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'An error occurred while deleting the event');
        })
        .finally(() => {
            // Re-enable delete button
            document.getElementById('confirmDeleteBtn').disabled = false;
        });
    }
    
    /**
     * View event attendees
     */
    function viewAttendees(eventId) {
        // Update modal title with event name
        document.getElementById('viewAttendeesModalLabel').textContent = 'Loading attendees...';
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('viewAttendeesModal'));
        modal.show();
        
        // Prepare data for AJAX request
        const data = new FormData();
        data.append('action', 'getEventAttendees');
        data.append('eventId', eventId);
        
        // Make AJAX request
        fetch('php/event_handler.php', {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update modal title with event name
                document.getElementById('viewAttendeesModalLabel').textContent = 
                    `Attendees for: ${data.data.event.name}`;
                
                // Store event ID for export
                document.getElementById('exportAttendeesBtn').dataset.eventId = eventId;
                
                // Display attendees
                displayAttendees(data.data.attendees);
            } else {
                document.getElementById('attendeesTableBody').innerHTML = 
                    `<tr><td colspan="7" class="text-center text-danger">Error: ${data.message}</td></tr>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('attendeesTableBody').innerHTML = 
                '<tr><td colspan="7" class="text-center text-danger">An error occurred while loading attendees</td></tr>';
        });
    }
    
    /**
     * Display attendees in the table
     */
    function displayAttendees(attendees) {
        const tableBody = document.getElementById('attendeesTableBody');
        
        if (attendees.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="7" class="text-center">No attendees registered for this event</td></tr>';
            return;
        }
        
        let html = '';
        attendees.forEach((attendee, index) => {
            // Format date for display
            const regDate = new Date(attendee.registration_date);
            const formattedDate = regDate.toLocaleDateString() + ' ' + regDate.toLocaleTimeString();
            
            // Determine status class
            let statusClass = '';
            switch(attendee.status) {
                case 'registered':
                    statusClass = 'bg-primary';
                    break;
                case 'attended':
                    statusClass = 'bg-success';
                    break;
                case 'cancelled':
                    statusClass = 'bg-danger';
                    break;
                case 'no-show':
                    statusClass = 'bg-warning';
                    break;
            }
            
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${attendee.name}</td>
                    <td>${attendee.email}</td>
                    <td>${attendee.phone || 'N/A'}</td>
                    <td>${formattedDate}</td>
                    <td><span class="badge ${statusClass}">${capitalizeFirstLetter(attendee.status)}</span></td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                Update Status
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="updateAttendeeStatus(${attendee.id}, 'registered')">Registered</a></li>
                                <li><a class="dropdown-item" href="#" onclick="updateAttendeeStatus(${attendee.id}, 'attended')">Attended</a></li>
                                <li><a class="dropdown-item" href="#" onclick="updateAttendeeStatus(${attendee.id}, 'no-show')">No Show</a></li>
                                <li><a class="dropdown-item" href="#" onclick="updateAttendeeStatus(${attendee.id}, 'cancelled')">Cancelled</a></li>
                            </ul>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        tableBody.innerHTML = html;
    }
    
    /**
     * Update attendee status
     */
    function updateAttendeeStatus(attendeeId, status) {
        // Prepare data for AJAX request
        const data = new FormData();
        data.append('action', 'updateAttendeeStatus');
        data.append('attendeeId', attendeeId);
        data.append('status', status);
        
        // Make AJAX request
        fetch('php/event_handler.php', {
            method: 'POST',
            body: data
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showAlert('success', 'Attendee status updated successfully');
                
                // Refresh attendees list
                const eventId = document.getElementById('exportAttendeesBtn').dataset.eventId;
                viewAttendees(eventId);
            } else {
                showAlert('error', 'Failed to update attendee status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'An error occurred while updating attendee status');
        });
    }
    
    /**
     * Export attendees to Excel
     */
    function exportAttendees() {
        const eventId = document.getElementById('exportAttendeesBtn').dataset.eventId;
        
        // Add export functionality to event_handler.php
        window.location.href = `php/event_handler.php?action=exportAttendees&eventId=${eventId}`;
    }
    
    /**
     * Show alert message
     */
    function showAlert(type, message) {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Add to page
        const container = document.querySelector('main');
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }, 5000);
    }
    
    /**
     * Capitalize first letter of a string
     */
    function capitalizeFirstLetter(string) {
        if (!string) return '';
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
    
    /**
     * Debounce function to limit how often a function can be called
     */
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                func.apply(context, args);
            }, wait);
        };
    }
});
