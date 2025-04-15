// This file should be placed in public/js/theme.js

document.addEventListener('DOMContentLoaded', function() {
  // Check for saved theme preference or use device preference
  const savedTheme = localStorage.getItem('theme') || 
    (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  
  // Apply the theme
  document.documentElement.setAttribute('data-theme', savedTheme);
  
  // Function to toggle theme
  window.toggleTheme = function() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    // Set the new theme
    document.documentElement.setAttribute('data-theme', newTheme);
    
    // Save the preference
    localStorage.setItem('theme', newTheme);
  }
});
