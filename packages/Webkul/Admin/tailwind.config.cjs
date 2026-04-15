/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./src/Resources/**/*.blade.php",
        "./src/Resources/**/*.js",
        "../ImportExport/src/Resources/**/*.blade.php",
        "../ImportExport/src/Resources/**/*.js",
    ],

    theme: {
        container: {
            center: true,

            screens: {
                "2xl": "1920px",
            },

            padding: {
                DEFAULT: "16px",
            },
        },

        screens: {
            sm: "525px",
            md: "768px",
            lg: "1024px",
            xl: "1240px",
            "2xl": "1920px",
        },

        extend: {
            colors: {
                darkGreen: '#40994A',
                darkBlue: '#0044F2',
                darkPink: '#F85156',
                // Modern violet-based color palette
                primary: {
                    50: '#f5f3ff',
                    100: '#ede9fe',
                    200: '#ddd6fe',
                    300: '#c4b5fd',
                    400: '#a78bfa',
                    500: '#8b5cf6',
                    600: '#7c3aed',
                    700: '#6d28d9',
                    800: '#5b21b6',
                    900: '#4c1d95',
                },
                accent: {
                    50: '#fdf4ff',
                    100: '#fae8ff',
                    200: '#f5d0fe',
                    300: '#f0abfc',
                    400: '#e879f9',
                    500: '#d946ef',
                    600: '#c026d3',
                    700: '#a21caf',
                    800: '#86198f',
                    900: '#701a75',
                },
                surface: {
                    50: '#fafafa',
                    100: '#f5f5f5',
                    200: '#e5e5e5',
                    300: '#d4d4d4',
                },
                violet: {
                    50: '#f5f3ff',
                    100: '#ede9fe',
                    200: '#ddd6fe',
                    300: '#c4b5fd',
                    400: '#a78bfa',
                    500: '#8b5cf6',
                    600: '#7c3aed',
                    700: '#6d28d9',
                    800: '#5b21b6',
                    900: '#4c1d95',
                },
            },

            fontFamily: {
                inter: ['Inter'],
                icon: ['icomoon'],
                poppins: ['Poppins', 'sans-serif'],
            },

            boxShadow: {
                'soft': '0 2px 8px -2px rgba(0, 0, 0, 0.05), 0 4px 12px -2px rgba(0, 0, 0, 0.03)',
                'soft-lg': '0 4px 20px -4px rgba(0, 0, 0, 0.08)',
                'soft-xl': '0 8px 30px -6px rgba(0, 0, 0, 0.1)',
                'glow': '0 0 15px rgba(139, 92, 246, 0.25)',
                'glow-sm': '0 0 8px rgba(139, 92, 246, 0.2)',
                'glow-lg': '0 0 25px rgba(139, 92, 246, 0.3)',
                'inner-soft': 'inset 0 1px 2px 0 rgba(0, 0, 0, 0.02)',
                'card': '0 1px 2px rgba(0, 0, 0, 0.04), 0 2px 8px rgba(0, 0, 0, 0.02)',
                'card-hover': '0 4px 20px -4px rgba(139, 92, 246, 0.12), 0 2px 4px rgba(0, 0, 0, 0.04)',
                'strong': '0 1px 3px rgba(0, 0, 0, 0.06), 0 2px 6px rgba(0, 0, 0, 0.03)',
            },

            backgroundImage: {
                'gradient-primary': 'linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%)',
                'gradient-primary-soft': 'linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%)',
                'gradient-success': 'linear-gradient(135deg, #10b981 0%, #34d399 100%)',
                'gradient-warning': 'linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%)',
                'gradient-danger': 'linear-gradient(135deg, #ef4444 0%, #f87171 100%)',
                'gradient-info': 'linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%)',
                'gradient-sidebar': 'linear-gradient(180deg, #1e1b4b 0%, #312e81 100%)',
                'gradient-card': 'linear-gradient(180deg, #ffffff 0%, #fafafa 100%)',
                'gradient-violet': 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 50%, #6d28d9 100%)',
            },

            borderRadius: {
                'xl': '1rem',
                '2xl': '1.5rem',
            },

            animation: {
                'fade-in': 'fadeIn 0.25s ease-out',
                'fade-in-up': 'fadeInUp 0.3s ease-out',
                'slide-up': 'slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1)',
                'slide-down': 'slideDown 0.3s cubic-bezier(0.16, 1, 0.3, 1)',
                'slide-in-right': 'slideInRight 0.3s cubic-bezier(0.16, 1, 0.3, 1)',
                'scale-in': 'scaleIn 0.2s cubic-bezier(0.16, 1, 0.3, 1)',
                'pulse-soft': 'pulseSoft 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'bounce-soft': 'bounceSoft 0.5s ease-out',
                'shimmer': 'shimmer 2s linear infinite',
            },

            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%': { transform: 'translateY(10px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                slideDown: {
                    '0%': { transform: 'translateY(-10px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                scaleIn: {
                    '0%': { transform: 'scale(0.95)', opacity: '0' },
                    '100%': { transform: 'scale(1)', opacity: '1' },
                },
                pulseSoft: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.7' },
                },
                fadeInUp: {
                    '0%': { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideInRight: {
                    '0%': { opacity: '0', transform: 'translateX(-10px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                bounceSoft: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-4px)' },
                },
                shimmer: {
                    '0%': { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
            },
        },
    },
    
    darkMode: 'class',

    plugins: [],

    safelist: [
        {
            pattern: /icon-/,
        }
    ]
};
