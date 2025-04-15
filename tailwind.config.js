// This file should be placed in the root of your project (ems-cc/)
module.exports = {
  content: [
    "./**/*.php",
    "./includes/**/*.php",
    "./views/**/*.php",
    "./public/js/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        // Google-inspired color palette
        'primary': '#4285F4',     // Google Blue
        'secondary': '#34A853',   // Google Green
        'accent': '#FBBC05',      // Google Yellow
        'error': '#EA4335',       // Google Red
        
        // Surface and background colors
        'surface': '#FFFFFF',
        'background': '#F8F9FA',
        'background-variant': '#F1F3F4',
        
        // Text colors
        'text-primary': '#202124',
        'text-secondary': '#5F6368',
        'text-tertiary': '#80868B',
        'text-disabled': '#9AA0A6',
        
        // Border colors
        'border': '#DADCE0',
        'border-light': '#E8EAED',
        
        // State colors
        'hover': 'rgba(66, 133, 244, 0.04)',
        'focus': 'rgba(66, 133, 244, 0.12)',
        'selected': 'rgba(66, 133, 244, 0.08)',
      },
      fontFamily: {
        sans: ['Google Sans', 'Roboto', 'Arial', 'sans-serif'],
        roboto: ['Roboto', 'Arial', 'sans-serif'],
      },
      fontSize: {
        'display-1': ['3.5rem', { lineHeight: '4rem', fontWeight: '400' }],
        'display-2': ['2.8rem', { lineHeight: '3.25rem', fontWeight: '400' }],
        'headline-1': ['2rem', { lineHeight: '2.5rem', fontWeight: '400' }],
        'headline-2': ['1.5rem', { lineHeight: '2rem', fontWeight: '400' }],
        'title': ['1.25rem', { lineHeight: '1.75rem', fontWeight: '500' }],
        'subtitle': ['1rem', { lineHeight: '1.5rem', fontWeight: '500' }],
        'body-1': ['1rem', { lineHeight: '1.5rem', fontWeight: '400' }],
        'body-2': ['0.875rem', { lineHeight: '1.25rem', fontWeight: '400' }],
        'caption': ['0.75rem', { lineHeight: '1.25rem', fontWeight: '400' }],
      },
      spacing: {
        '4xs': '0.125rem',  // 2px
        '3xs': '0.25rem',   // 4px
        '2xs': '0.375rem',  // 6px
        'xs': '0.5rem',     // 8px
        'sm': '0.75rem',    // 12px
        'md': '1rem',       // 16px
        'lg': '1.25rem',    // 20px
        'xl': '1.5rem',     // 24px
        '2xl': '2rem',      // 32px
        '3xl': '2.5rem',    // 40px
        '4xl': '3rem',      // 48px
      },
      boxShadow: {
        'elevation-1': '0 1px 2px 0 rgba(60, 64, 67, 0.3), 0 1px 3px 1px rgba(60, 64, 67, 0.15)',
        'elevation-2': '0 1px 2px 0 rgba(60, 64, 67, 0.3), 0 2px 6px 2px rgba(60, 64, 67, 0.15)',
        'elevation-3': '0 1px 3px 0 rgba(60, 64, 67, 0.3), 0 4px 8px 3px rgba(60, 64, 67, 0.15)',
        'elevation-4': '0 2px 3px 0 rgba(60, 64, 67, 0.3), 0 6px 10px 4px rgba(60, 64, 67, 0.15)',
        'elevation-5': '0 4px 4px 0 rgba(60, 64, 67, 0.3), 0 8px 12px 6px rgba(60, 64, 67, 0.15)',
      },
      borderRadius: {
        'none': '0',
        'sm': '0.25rem',    // 4px
        'md': '0.5rem',     // 8px
        'lg': '0.75rem',    // 12px
        'xl': '1rem',       // 16px
        'full': '9999px',
      }
    },
  },
  plugins: [],
}
