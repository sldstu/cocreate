/**
 * Events management JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
  // Initialize date pickers
  const datePickers = document.querySelectorAll('.date-picker');
  if (datePickers.length > 0) {
      datePickers.forEach(picker => {
          // You can replace this with your preferred date picker library
          picker.type = 'date';
      });
  }
  
  // Initialize time pickers
  const timePickers = document.querySelectorAll('.time-picker');
  if (timePickers.length > 0) {
      timePickers.forEach(picker => {
          // You can replace this with your preferred time picker library
          picker.type = 'time';
      });
  }
  
  // Filter dropdown toggle
  const filterDropdownBtn = document.getElementById('filter-dropdown-btn');
  const filterDropdown = document.getElementById('filter-dropdown');
  
  if (filterDropdownBtn && filterDropdown) {
      // Add a debug log to check if elements were found
      console.log('Filter elements found:', { filterDropdownBtn, filterDropdown });
      
      // Toggle dropdown when button is clicked
      filterDropdownBtn.addEventListener('click', function(e) {
          e.preventDefault(); // Prevent any default action
          e.stopPropagation();
          console.log('Filter dropdown button clicked');
          filterDropdown.classList.toggle('hidden');
          
          // Hide other dropdowns
          if (statusDropdown && !statusDropdown.classList.contains('hidden')) {
              statusDropdown.classList.add('hidden');
          }
          
          // Add active style to button when dropdown is open
          if (!filterDropdown.classList.contains('hidden')) {
              filterDropdownBtn.style.backgroundColor = 'var(--color-primary)';
              filterDropdownBtn.style.color = '#ffffff';
          } else {
              filterDropdownBtn.style.backgroundColor = '';
              filterDropdownBtn.style.color = 'var(--color-text-secondary)';
          }
      });
      
      // Close dropdown when clicking outside
      document.addEventListener('click', function(e) {
          if (!filterDropdownBtn.contains(e.target) && !filterDropdown.contains(e.target)) {
              filterDropdown.classList.add('hidden');
              filterDropdownBtn.style.backgroundColor = '';
              filterDropdownBtn.style.color = 'var(--color-text-secondary)';
          }
      });
      
      // Prevent dropdown from closing when clicking inside it
      filterDropdown.addEventListener('click', function(e) {
          e.stopPropagation();
      });
      
      // Apply filters button
      const applyFiltersBtn = document.getElementById('apply-filters-btn');
      if (applyFiltersBtn) {
          applyFiltersBtn.addEventListener('click', function() {
              // Build URL parameters for server-side filtering
              const params = new URLSearchParams(window.location.search);
              params.set('page', 'lead_events');
              
              // Add parameters from filter inputs
              const dateFrom = document.getElementById('filter-date-from').value;
              const dateTo = document.getElementById('filter-date-to').value;
              const type = document.getElementById('filter-type').value;
              const department = document.getElementById('filter-department').value;
              const status = document.getElementById('filter-status').value;
              const visibility = document.getElementById('filter-visibility').value;
              
              if (dateFrom) params.set('date_from', dateFrom); else params.delete('date_from');
              if (dateTo) params.set('date_to', dateTo); else params.delete('date_to');
              if (type) params.set('type', type); else params.delete('type');
              if (department) params.set('department', department); else params.delete('department');
              if (status) params.set('status', status); else params.delete('status');
              if (visibility) params.set('visibility', visibility); else params.delete('visibility');
              
              // Preserve current view if set
              const currentView = params.get('view');
              if (currentView) params.set('view', currentView);
              
              // Close dropdown and update button styling
              filterDropdown.classList.add('hidden');
              
              // Update button to show filter is active
              const hasActiveFilters = checkIfAdvancedFiltersActive();
              if (hasActiveFilters) {
                  filterDropdownBtn.innerHTML = '<i class="fas fa-filter mr-1"></i> Filters (Active)';
                  filterDropdownBtn.style.backgroundColor = 'rgba(66, 133, 244, 0.1)';
                  filterDropdownBtn.style.color = '#4285F4';
              } else {
                  filterDropdownBtn.innerHTML = '<i class="fas fa-filter mr-1"></i> Filters';
                  filterDropdownBtn.style.backgroundColor = '';
                  filterDropdownBtn.style.color = 'var(--color-text-secondary)';
              }
              
              // Redirect with filters
              window.location.href = '?' + params.toString();
          });
      }
      
      // Reset filters button
      const resetFiltersBtn = document.getElementById('reset-filters-btn');
      if (resetFiltersBtn) {
          resetFiltersBtn.addEventListener('click', function() {
              // Clear all filter inputs
              document.getElementById('filter-date-from').value = '';
              document.getElementById('filter-date-to').value = '';
              document.getElementById('filter-type').value = '';
              document.getElementById('filter-department').value = '';
              document.getElementById('filter-status').value = '';
              document.getElementById('filter-visibility').value = '';
              
              // Reset filter button text
              filterDropdownBtn.innerHTML = '<i class="fas fa-filter mr-1"></i> Filters';
              filterDropdownBtn.style.backgroundColor = '';
              filterDropdownBtn.style.color = 'var(--color-text-secondary)';
              
              // Redirect to clear all filters from URL
              const params = new URLSearchParams(window.location.search);
              params.set('page', 'lead_events');
              
              // Preserve current view if set
              const currentView = params.get('view');
              if (currentView) params.set('view', currentView);
              
              // Clear all filter parameters
              params.delete('date_from');
              params.delete('date_to');
              params.delete('type');
              params.delete('department');
              params.delete('status');
              params.delete('visibility');
              
              window.location.href = '?' + params.toString();
          });
      }
      
      // Initialize filter values from URL parameters
      initializeFilterValues();
  }
  
  // Status dropdown toggle
  const statusDropdownBtn = document.getElementById('status-dropdown-btn');
  const statusDropdown = document.getElementById('status-dropdown');
  
  if (statusDropdownBtn && statusDropdown) {
      // Toggle dropdown when button is clicked
      statusDropdownBtn.addEventListener('click', function(e) {
          e.stopPropagation();
          statusDropdown.classList.toggle('hidden');
          
          // Hide other dropdowns
          if (filterDropdown && !filterDropdown.classList.contains('hidden')) {
              filterDropdown.classList.add('hidden');
          }
          
          // Add active style to button when dropdown is open
          if (!statusDropdown.classList.contains('hidden')) {
              statusDropdownBtn.style.backgroundColor = 'var(--color-primary)';
              statusDropdownBtn.style.color = '#ffffff';
          } else {
              statusDropdownBtn.style.backgroundColor = '';
              statusDropdownBtn.style.color = 'var(--color-text-secondary)';
          }
      });
      
      // Close dropdown when clicking outside
      document.addEventListener('click', function(e) {
          if (!statusDropdownBtn.contains(e.target) && !statusDropdown.contains(e.target)) {
              statusDropdown.classList.add('hidden');
              statusDropdownBtn.style.backgroundColor = '';
              statusDropdownBtn.style.color = 'var(--color-text-secondary)';
          }
      });
      
      // Handle filter button clicks
      const filterButtons = document.querySelectorAll('.filter-btn');
      if (filterButtons.length > 0) {
          filterButtons.forEach(button => {
              button.addEventListener('click', function() {
                  const filter = this.getAttribute('data-filter');
                  
                  // Update active state
                  filterButtons.forEach(btn => btn.classList.remove('active'));
                  this.classList.add('active');
                  
                  // Update dropdown button text
                  if (filter === 'all') {
                      statusDropdownBtn.innerHTML = '<i class="fas fa-tasks mr-1"></i> Status <i class="fas fa-chevron-down ml-2 text-xs"></i>';
                  } else {
                      const buttonText = this.textContent.trim();
                      statusDropdownBtn.innerHTML = `<i class="fas fa-tasks mr-1"></i> ${buttonText} <i class="fas fa-chevron-down ml-2 text-xs"></i>`;
                      statusDropdownBtn.style.color = this.style.color;
                  }
                  
                  // Close dropdown
                  statusDropdown.classList.add('hidden');
                  
                  // Filter events
                  filterEventsByStatus(filter);
                  
                  // Update URL without reloading
                  const params = new URLSearchParams(window.location.search);
                  params.set('page', 'lead_events');
                  if (filter !== 'all') {
                      params.set('filter', filter);
                  } else {
                      params.delete('filter');
                  }
                  window.history.pushState({}, '', '?' + params.toString());
              });
          });
      }
  }
  
  // Function to filter events by status
  function filterEventsByStatus(status) {
      const eventElements = document.querySelectorAll('.event-card, .event-row');
      
      if (status === 'all') {
          // Show all events
          eventElements.forEach(element => {
              element.style.display = '';
          });
      } else {
          // Show only matching events
          eventElements.forEach(element => {
              if (element.getAttribute('data-status') === status) {
                  element.style.display = '';
              } else {
                  element.style.display = 'none';
              }
          });
      }
  }
  
  // Check if any advanced filters are active
  function checkIfAdvancedFiltersActive() {
      const dateFrom = document.getElementById('filter-date-from')?.value || '';
      const dateTo = document.getElementById('filter-date-to')?.value || '';
      const type = document.getElementById('filter-type')?.value || '';
      const department = document.getElementById('filter-department')?.value || '';
      const status = document.getElementById('filter-status')?.value || '';
      const visibility = document.getElementById('filter-visibility')?.value || '';
      
      return dateFrom !== '' || dateTo !== '' || type !== '' || department !== '' || status !== '' || visibility !== '';
  }
  
  // Initialize filter values from URL parameters
  function initializeFilterValues() {
      const params = new URLSearchParams(window.location.search);
      
      // Set filter values from URL parameters if they exist
      if (params.has('date_from')) {
          document.getElementById('filter-date-from').value = params.get('date_from');
      }
      
      if (params.has('date_to')) {
          document.getElementById('filter-date-to').value = params.get('date_to');
      }
      
      if (params.has('type')) {
          document.getElementById('filter-type').value = params.get('type');
      }
      
      if (params.has('department')) {
          document.getElementById('filter-department').value = params.get('department');
      }
      
      if (params.has('status')) {
          document.getElementById('filter-status').value = params.get('status');
      }
      
      if (params.has('visibility')) {
          document.getElementById('filter-visibility').value = params.get('visibility');
      }
      
      // Update filter button if any filters are active
      if (checkIfAdvancedFiltersActive()) {
          const filterDropdownBtn = document.getElementById('filter-dropdown-btn');
          if (filterDropdownBtn) {
              filterDropdownBtn.innerHTML = '<i class="fas fa-filter mr-1"></i> Filters (Active)';
              filterDropdownBtn.style.backgroundColor = 'rgba(66, 133, 244, 0.1)';
              filterDropdownBtn.style.color = '#4285F4';
          }
      }
  }
  
  // Set active filter on page load
  function initializeFilters() {
      const urlParams = new URLSearchParams(window.location.search);
      const filterParam = urlParams.get('filter');
      
      if (filterParam) {
          const filterButton = document.querySelector(`.filter-btn[data-filter="${filterParam}"]`);
          if (filterButton) {
              // Trigger click to activate filter
              filterButton.click();
          }
      }
  }
  
  // Initialize filters
  initializeFilters();
  
  // Handle delete event confirmation
  const deleteEventButtons = document.querySelectorAll('.delete-event');
  if (deleteEventButtons.length > 0) {
      deleteEventButtons.forEach(button => {
          button.addEventListener('click', function() {
              const eventId = this.dataset.id;
              if (confirm('Are you sure you want to delete this event? This action cannot be undone.\n\nAll event data including RSVPs, comments, and attachments will be permanently deleted.')) {
                  // Create a loading overlay
                  const overlay = document.createElement('div');
                  overlay.style.position = 'fixed';
                  overlay.style.top = '0';
                  overlay.style.left = '0';
                  overlay.style.width = '100%';
                  overlay.style.height = '100%';
                  overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
                  overlay.style.display = 'flex';
                  overlay.style.justifyContent = 'center';
                  overlay.style.alignItems = 'center';
                  overlay.style.zIndex = '9999';
                  
                  const spinner = document.createElement('div');
                  spinner.innerHTML = '<i class="fas fa-spinner fa-spin fa-3x" style="color: white;"></i>';
                  overlay.appendChild(spinner);
                  
                  document.body.appendChild(overlay);
                  
                  // Send delete request
                  window.location.href = 'views/lead/php/event_handler.php?action=delete_event&event_id=' + eventId;
              }
          });
      });
  }
  
  // Handle delete attachment confirmation
  const deleteAttachmentButtons = document.querySelectorAll('.delete-attachment');
  if (deleteAttachmentButtons.length > 0) {
      deleteAttachmentButtons.forEach(button => {
          button.addEventListener('click', function() {
              const attachmentId = this.dataset.id;
              const eventId = this.dataset.eventId || '';
              if (confirm('Are you sure you want to delete this attachment?')) {
                  window.location.href = 'views/lead/php/event_handler.php?action=delete_attachment&attachment_id=' + attachmentId + '&event_id=' + eventId;
              }
          });
      });
  }
  
  // Handle delete comment confirmation
  const deleteCommentButtons = document.querySelectorAll('.delete-comment');
  if (deleteCommentButtons.length > 0) {
      deleteCommentButtons.forEach(button => {
          button.addEventListener('click', function() {
              const commentId = this.dataset.id;
              const eventId = this.dataset.eventId || '';
              if (confirm('Are you sure you want to delete this comment?')) {
                  window.location.href = 'views/lead/php/event_handler.php?action=delete_comment&comment_id=' + commentId + '&event_id=' + eventId;
              }
          });
      });
  }
  
  // Toggle sections
  window.toggleSection = function(sectionId) {
      const section = document.getElementById(sectionId);
      const icon = document.querySelector(`[data-section="${sectionId}"] i`);
      
      if (section.style.display === 'none') {
          section.style.display = 'block';
          icon.classList.remove('fa-chevron-down');
          icon.classList.add('fa-chevron-up');
      } else {
          section.style.display = 'none';
          icon.classList.remove('fa-chevron-up');
          icon.classList.add('fa-chevron-down');
      }
  };
  
  // Preview image before upload
  window.previewImage = function(input) {
      if (input.files && input.files[0]) {
          const reader = new FileReader();
          
          reader.onload = function(e) {
              const preview = document.getElementById('image-preview');
              const placeholder = document.getElementById('image-placeholder');
              
              preview.src = e.target.result;
              preview.style.display = 'block';
              placeholder.style.display = 'none';
              
              // Update hidden input with base64 data
              document.getElementById('featured_image').value = e.target.result;
          };
          
          reader.readAsDataURL(input.files[0]);
      }
  };
  
  // Display selected files
  const fileInput = document.getElementById('attachments');
  if (fileInput) {
      fileInput.addEventListener('change', function() {
          const fileList = document.getElementById('file-list');
          fileList.innerHTML = '';
          
          if (this.files.length > 0) {
              const list = document.createElement('ul');
              list.className = 'space-y-1 mt-2';
              
              for (let i = 0; i < this.files.length; i++) {
                  const file = this.files[i];
                  const item = document.createElement('li');
                  item.className = 'text-xs flex items-center';
                  item.style.color = 'var(--color-text-secondary)';
                  
                  const icon = document.createElement('i');
                  icon.className = 'fas fa-file mr-2';
                  
                  const name = document.createElement('span');
                  name.textContent = file.name;
                  
                  const size = document.createElement('span');
                  size.className = 'ml-2';
                  size.style.color = 'var(--color-text-tertiary)';
                  size.textContent = `(${(file.size / 1024).toFixed(2)} KB)`;
                  
                  item.appendChild(icon);
                  item.appendChild(name);
                  item.appendChild(size);
                  list.appendChild(item);
              }
              
              fileList.appendChild(list);
          }
      });
  }
  
  // Enhanced Filter events with theme support
  const filterButtons = document.querySelectorAll('.filter-btn');
  if (filterButtons.length > 0) {
      // Set initial active filter button styles
      const activeButton = document.querySelector('.filter-btn.active');
      if (activeButton) {
          setActiveFilterButtonStyles(activeButton);
      }
      
      filterButtons.forEach(button => {
          button.addEventListener('click', function() {
              // Remove active class from all buttons
              filterButtons.forEach(btn => {
                  btn.classList.remove('active');
                  resetFilterButtonStyles(btn);
              });
              
              // Add active class to clicked button
              this.classList.add('active');
              setActiveFilterButtonStyles(this);
              
              // Filter events
              const filter = this.dataset.filter;
              filterEventsByStatus(filter);
              
              // If advanced filters are active, reapply them
              if (checkIfAdvancedFiltersActive()) {
                  applyAdvancedFilters();
              }
              
              // If we also have search input, combine filters
              const searchInput = document.getElementById('search-events');
              if (searchInput && searchInput.value.trim() !== '') {
                  searchEvents(searchInput.value.trim());
              }
              
              // Update URL parameter for filter without reloading
              updateUrlParam('filter', filter);
          });
      });
  }
  
  // Enhanced Real-time search events with theme support
  const searchInput = document.getElementById('search-events');
  if (searchInput) {
      searchInput.addEventListener('input', function() {
          searchEvents(this.value.toLowerCase().trim());
      });
  }
  
  // Function to filter events by status
  function filterEventsByStatus(filter) {
      const eventCards = document.querySelectorAll('.event-card, .event-row');
      let visibleCount = 0;
      
      eventCards.forEach(card => {
          if (filter === 'all') {
              card.style.display = '';
              visibleCount++;
          } else {
              const status = card.dataset.status;
              card.style.display = (status === filter) ? '' : 'none';
              if (status === filter) visibleCount++;
          }
      });
      
      // Show message if no events match the filter
      showNoEventsMessage(visibleCount === 0);
  }
  
  // Function to search events
  function searchEvents(searchTerm) {
      const eventCards = document.querySelectorAll('.event-card, .event-row');
      let visibleCount = 0;
      
      // Get current status filter
      const activeFilterBtn = document.querySelector('.filter-btn.active');
      const currentFilter = activeFilterBtn ? activeFilterBtn.dataset.filter : 'all';
      
      eventCards.forEach(card => {
          // Skip if already hidden by status filter
          if (card.style.display === 'none' && 
              ((currentFilter !== 'all' && card.dataset.status !== currentFilter))) {
              return;
          }
          
          // Search in title and description
          const title = card.querySelector('.event-title').textContent.toLowerCase();
          const description = card.querySelector('.event-description').textContent.toLowerCase();
          
          const showCard = searchTerm === '' || 
                           title.includes(searchTerm) || 
                           description.includes(searchTerm);
          
          card.style.display = showCard ? '' : 'none';
          if (showCard) visibleCount++;
      });
      
      // Show message if no events match the search
      showNoEventsMessage(visibleCount === 0);
      
      // Update URL parameter for search without reloading
      updateUrlParam('search', searchTerm);
  }
  
  // Function to set active filter button styles
  function setActiveFilterButtonStyles(button) {
      // Use theme variables for proper styling
      button.style.backgroundColor = 'var(--color-primary)';
      button.style.color = '#ffffff';
      button.style.fontWeight = '500';
      button.style.boxShadow = '0 1px 2px rgba(0, 0, 0, 0.1)';
  }
  
  // Function to reset filter button styles
  function resetFilterButtonStyles(button) {
      // Reset to default theme styling
      button.style.backgroundColor = '';
      button.style.color = 'var(--color-text-secondary)';
      button.style.fontWeight = '';
      button.style.boxShadow = '';
  }
  
  // Function to update URL parameters without page reload
  function updateUrlParam(param, value) {
      const url = new URL(window.location.href);
      if (value) {
          url.searchParams.set(param, value);
      } else {
          url.searchParams.delete(param);
      }
      window.history.replaceState({}, '', url);
  }
  
  // Function to show/hide "no events" message
  function showNoEventsMessage(shouldShow) {
      // Check if message exists, if not create it
      let noEventsMsg = document.getElementById('no-events-message');
      
      if (shouldShow) {
          if (!noEventsMsg) {
              const eventsContainer = document.querySelector('.grid') || document.querySelector('table');
              if (!eventsContainer) return;
              
              noEventsMsg = document.createElement('div');
              noEventsMsg.id = 'no-events-message';
              noEventsMsg.className = 'text-center py-8 google-card mt-4';
              noEventsMsg.innerHTML = `
                  <div class="rounded-full mx-auto p-4 mb-4" style="background-color: rgba(66, 133, 244, 0.1); width: fit-content;">
                      <i class="fas fa-calendar-alt text-2xl" style="color: #4285F4;"></i>
                  </div>
                  <h3 class="text-lg font-medium mb-2" style="color: var(--color-text-primary);">No Events Found</h3>
                  <p class="text-sm mb-4" style="color: var(--color-text-secondary);">No events match your current filters.</p>
              `;
              
              // Insert after the events container
              eventsContainer.parentNode.insertBefore(noEventsMsg, eventsContainer.nextSibling);
          } else {
              noEventsMsg.style.display = 'block';
          }
      } else if (noEventsMsg) {
          noEventsMsg.style.display = 'none';
      }
  }
  
  // Initialize calendar if the element exists
  const calendarEl = document.getElementById('events-calendar');
  if (calendarEl) {
      // Get events data from the data attribute
      const eventsData = JSON.parse(calendarEl.dataset.events || '[]');
      
      // Format events for FullCalendar
      const calendarEvents = eventsData.map(event => {
          // Determine color based on event type or status
          let color;
          switch (event.status) {
              case 'upcoming':
                  color = '#34A853';
                  break;
              case 'ongoing':
                  color = '#4285F4';
                  break;
              case 'completed':
                  color = '#EA4335';
                  break;
              case 'draft':
                  color = '#FBBC05';
                  break;
              case 'cancelled':
                  color = '#757575';
                  break;
              default:
                  color = '#FBBC05';
          }
          
          return {
              id: event.event_id,
              title: event.title,
              start: event.start_date,
              end: event.end_date,
              color: color,
              url: `?page=lead_events&event_id=${event.event_id}`,
              extendedProps: {
                  location: event.location,
                  department: event.department_name,
                  type: event.type_name,
                  status: event.status,
                  visibility: event.visibility
              }
          };
      });
      
      // Initialize FullCalendar
      const calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: 'dayGridMonth',
          headerToolbar: {
              left: 'prev,next today',
              center: 'title',
              right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
          },
          events: calendarEvents,
          eventTimeFormat: {
              hour: '2-digit',
              minute: '2-digit',
              meridiem: 'short'
          },
          eventClick: function(info) {
              info.jsEvent.preventDefault(); // prevent browser navigation
              window.location.href = info.event.url;
          },
          eventDidMount: function(info) {
              // Add tooltips to events
              const tooltip = new tippy(info.el, {
                  content: `
                      <div class="p-2">
                          <div class="font-medium mb-1">${info.event.title}</div>
                          <div class="text-xs mb-1">
                              <i class="fas fa-calendar-alt mr-1"></i> 
                              ${new Date(info.event.start).toLocaleDateString()} 
                              ${info.event.end ? ' - ' + new Date(info.event.end).toLocaleDateString() : ''}
                          </div>
                          <div class="text-xs mb-1">
                              <i class="fas fa-clock mr-1"></i> 
                              ${new Date(info.event.start).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} 
                              ${info.event.end ? ' - ' + new Date(info.event.end).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : ''}
                          </div>
                          ${info.event.extendedProps.location ? `
                          <div class="text-xs mb-1">
                              <i class="fas fa-map-marker-alt mr-1"></i> 
                              ${info.event.extendedProps.location}
                          </div>
                          ` : ''}
                          <div class="text-xs mb-1">
                              <i class="fas fa-tag mr-1"></i> 
                              ${info.event.extendedProps.type}
                          </div>
                          <div class="text-xs mb-1">
                              <i class="fas fa-info-circle mr-1"></i> 
                              ${ucfirst(info.event.extendedProps.status)}
                          </div>
                          <div class="text-xs">
                              <i class="fas fa-eye mr-1"></i> 
                              ${ucfirst(info.event.extendedProps.visibility || 'public')}
                          </div>
                      </div>
                  `,
                  allowHTML: true,
                  placement: 'top',
                  arrow: true,
                  theme: 'light'
              });
          }
      });
      
      calendar.render();
  }
  
  // Helper function to capitalize first letter
  function ucfirst(string) {
      if (!string) return '';
      return string.charAt(0).toUpperCase() + string.slice(1);
  }
  
  // Check if there are URL parameters to apply on page load
  function applyUrlFilters() {
      const params = new URLSearchParams(window.location.search);
      const filterParam = params.get('filter');
      const searchParam = params.get('search');
      
      // Apply filter if exists in URL
      if (filterParam) {
          const filterBtn = document.querySelector(`.filter-btn[data-filter="${filterParam}"]`);
          if (filterBtn) {
              // Simulate click on the filter button
              filterBtn.click();
          }
      }
      
      // Apply search if exists in URL
      if (searchParam) {
          const searchInput = document.getElementById('search-events');
          if (searchInput) {
              searchInput.value = searchParam;
              // Trigger the input event to filter the events
              searchInput.dispatchEvent(new Event('input'));
          }
      }
  }
  
  // Apply any URL filters on page load
  applyUrlFilters();

  // Event task completion tracking and publishing functionality
  const refreshCompletionStatusBtn = document.getElementById('refresh-completion-status');
  if (refreshCompletionStatusBtn) {
    refreshCompletionStatusBtn.addEventListener('click', function() {
      const eventId = this.dataset.id;
      if (!eventId) return;
      
      // Show loading state
      const originalContent = this.innerHTML;
      this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Updating...';
      this.disabled = true;
      
      // Send AJAX request to update completion status
      fetch('views/lead/php/event_handler.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_completion_status&event_id=${eventId}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update progress bar
          const progressBar = document.querySelector('.bg-gray-200 .h-full');
          const statusText = document.querySelector('.text-xs.font-medium');
          const readyStatus = document.querySelector('.rounded-full.text-xs.font-medium');
          
          if (progressBar) {
            progressBar.style.width = `${data.completion_status}%`;
            
            // Update color based on completion
            let statusColor = '#FBBC05'; // Default yellow for in progress
            if (data.completion_status >= 100) {
              statusColor = '#34A853'; // Green for complete
            } else if (data.completion_status > 0) {
              statusColor = '#4285F4'; // Blue for in progress
            } else if (data.completion_status == 0) {
              statusColor = '#EA4335'; // Red for not started
            }
            progressBar.style.backgroundColor = statusColor;
          }
          
          // Update status text
          if (statusText) {
            if (data.completion_status >= 100) {
              statusText.textContent = 'All tasks completed';
            } else if (data.completion_status > 0) {
              statusText.textContent = `${data.completion_status}% of tasks completed`;
            } else {
              statusText.textContent = 'No tasks assigned yet';
            }
          }
          
          // Update ready for publish status
          if (readyStatus) {
            if (data.ready_for_publish == 1) {
              readyStatus.textContent = 'Ready to Publish';
              readyStatus.style.backgroundColor = 'rgba(52, 168, 83, 0.1)';
              readyStatus.style.color = '#34A853';
            } else {
              readyStatus.textContent = 'Not Ready to Publish';
              readyStatus.style.backgroundColor = 'rgba(251, 188, 5, 0.1)';
              readyStatus.style.color = '#FBBC05';
            }
          }
          
          // Show success message
          showNotification('Success', 'Completion status updated successfully', 'success');
        } else {
          showNotification('Error', data.message || 'Failed to update completion status', 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showNotification('Error', 'Something went wrong. Please try again.', 'error');
      })
      .finally(() => {
        // Reset button
        this.innerHTML = originalContent;
        this.disabled = false;
      });
    });
  }
  
  // Function to display a notification
  function showNotification(title, message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 z-50 shadow-lg rounded-lg p-4 flex items-start max-w-sm transition-all duration-300';
    notification.style.transform = 'translateY(-20px)';
    notification.style.opacity = '0';
    notification.style.backgroundColor = 'var(--color-card)';
    notification.style.borderLeft = '4px solid';
    
    // Set color based on type
    if (type === 'success') {
      notification.style.borderColor = '#34A853';
    } else if (type === 'error') {
      notification.style.borderColor = '#EA4335';
    } else if (type === 'warning') {
      notification.style.borderColor = '#FBBC05';
    } else {
      notification.style.borderColor = '#4285F4';
    }
    
    // Create icon based on type
    let iconClass = 'fas fa-info-circle text-blue-500';
    if (type === 'success') iconClass = 'fas fa-check-circle text-green-500';
    if (type === 'error') iconClass = 'fas fa-exclamation-circle text-red-500';
    if (type === 'warning') iconClass = 'fas fa-exclamation-triangle text-yellow-500';
    
    notification.innerHTML = `
      <div class="mr-4">
        <i class="${iconClass} text-lg"></i>
      </div>
      <div class="flex-1">
        <h4 class="text-sm font-medium" style="color: var(--color-text-primary);">${title}</h4>
        <p class="text-xs mt-1" style="color: var(--color-text-secondary);">${message}</p>
      </div>
      <button class="ml-4 text-xs" style="color: var(--color-text-tertiary);" aria-label="Close">
        <i class="fas fa-times"></i>
      </button>
    `;
    
    // Add to DOM
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
      notification.style.transform = 'translateY(0)';
      notification.style.opacity = '1';
    }, 10);
    
    // Add close functionality
    const closeBtn = notification.querySelector('button');
    closeBtn.addEventListener('click', () => {
      notification.style.transform = 'translateY(-20px)';
      notification.style.opacity = '0';
      setTimeout(() => notification.remove(), 300);
    });
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
      if (document.body.contains(notification)) {
        notification.style.transform = 'translateY(-20px)';
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
      }
    }, 5000);
  }

  // Handle event publishing
  const publishEventBtn = document.getElementById('publish-event');
  if (publishEventBtn) {
    publishEventBtn.addEventListener('click', function() {
      const eventId = this.dataset.id;
      const action = this.dataset.action; // 'publish' or 'unpublish'
      
      if (!eventId || !action) return;
      
      // Get visibility value based on action
      const visibility = action === 'publish' ? 'public' : 'draft';
      
      // Show loading state
      const originalContent = this.innerHTML;
      this.innerHTML = `<i class="fas fa-spinner fa-spin mr-1"></i> ${action === 'publish' ? 'Publishing' : 'Unpublishing'}...`;
      this.disabled = true;
      
      // Send AJAX request to update visibility
      fetch('views/lead/php/event_handler.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_visibility&event_id=${eventId}&visibility=${visibility}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update UI
          if (action === 'publish') {
            this.textContent = 'Unpublish Event';
            this.classList.remove('bg-green-600', 'hover:bg-green-700');
            this.classList.add('bg-yellow-600', 'hover:bg-yellow-700');
            this.dataset.action = 'unpublish';
            
            showNotification('Success', 'Event has been published and is now visible to the public', 'success');
          } else {
            this.textContent = 'Publish Event';
            this.classList.remove('bg-yellow-600', 'hover:bg-yellow-700');
            this.classList.add('bg-green-600', 'hover:bg-green-700');
            this.dataset.action = 'publish';
            
            showNotification('Success', 'Event has been unpublished and is now in draft mode', 'success');
          }
        } else {
          showNotification('Error', data.message || 'Failed to update event visibility', 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showNotification('Error', 'Something went wrong. Please try again.', 'error');
      })
      .finally(() => {
        // Reset button if needed
        if (!data?.success) {
          this.innerHTML = originalContent;
        }
        this.disabled = false;
      });
    });
  }
});
