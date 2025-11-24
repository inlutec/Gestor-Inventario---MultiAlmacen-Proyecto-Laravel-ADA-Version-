/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      fontSize: {
        xs: ['0.675rem', { lineHeight: '0.9rem' }],     // 0.75rem * 0.9 = 0.675rem
        sm: ['0.7875rem', { lineHeight: '1.125rem' }],  // 0.875rem * 0.9 = 0.7875rem
        base: ['0.9rem', { lineHeight: '1.35rem' }],    // 1rem * 0.9 = 0.9rem
        lg: ['1.0125rem', { lineHeight: '1.575rem' }],  // 1.125rem * 0.9 = 1.0125rem
        xl: ['1.125rem', { lineHeight: '1.8rem' }],     // 1.25rem * 0.9 = 1.125rem
        '2xl': ['1.35rem', { lineHeight: '2.025rem' }], // 1.5rem * 0.9 = 1.35rem
        '3xl': ['1.6875rem', { lineHeight: '2.25rem' }],// 1.875rem * 0.9 = 1.6875rem
        '4xl': ['2.025rem', { lineHeight: '2.7rem' }],  // 2.25rem * 0.9 = 2.025rem
        '5xl': ['2.7rem', { lineHeight: '1' }],         // 3rem * 0.9 = 2.7rem
        '6xl': ['3.375rem', { lineHeight: '1' }],       // 3.75rem * 0.9 = 3.375rem
        '7xl': ['4.05rem', { lineHeight: '1' }],        // 4.5rem * 0.9 = 4.05rem
        '8xl': ['5.4rem', { lineHeight: '1' }],         // 6rem * 0.9 = 5.4rem
        '9xl': ['7.2rem', { lineHeight: '1' }],         // 8rem * 0.9 = 7.2rem
      },
      spacing: {
        '0': '0',
        'px': '1px',
        '0.5': '0.1125rem',  // 0.125rem * 0.9 = 0.1125rem
        '1': '0.225rem',     // 0.25rem * 0.9 = 0.225rem
        '1.5': '0.3375rem',  // 0.375rem * 0.9 = 0.3375rem
        '2': '0.45rem',      // 0.5rem * 0.9 = 0.45rem
        '2.5': '0.5625rem',  // 0.625rem * 0.9 = 0.5625rem
        '3': '0.675rem',     // 0.75rem * 0.9 = 0.675rem
        '3.5': '0.7875rem',  // 0.875rem * 0.9 = 0.7875rem
        '4': '0.9rem',       // 1rem * 0.9 = 0.9rem
        '5': '1.125rem',     // 1.25rem * 0.9 = 1.125rem
        '6': '1.35rem',      // 1.5rem * 0.9 = 1.35rem
        '7': '1.575rem',     // 1.75rem * 0.9 = 1.575rem
        '8': '1.8rem',       // 2rem * 0.9 = 1.8rem
        '9': '2.025rem',     // 2.25rem * 0.9 = 2.025rem
        '10': '2.25rem',     // 2.5rem * 0.9 = 2.25rem
        '11': '2.475rem',    // 2.75rem * 0.9 = 2.475rem
        '12': '2.7rem',      // 3rem * 0.9 = 2.7rem
        '14': '3.15rem',     // 3.5rem * 0.9 = 3.15rem
        '16': '3.6rem',      // 4rem * 0.9 = 3.6rem
        '20': '4.5rem',      // 5rem * 0.9 = 4.5rem
        '24': '5.4rem',      // 6rem * 0.9 = 5.4rem
        '28': '6.3rem',      // 7rem * 0.9 = 6.3rem
        '32': '7.2rem',      // 8rem * 0.9 = 7.2rem
        '36': '8.1rem',      // 9rem * 0.9 = 8.1rem
        '40': '9rem',        // 10rem * 0.9 = 9rem
        '44': '9.9rem',      // 11rem * 0.9 = 9.9rem
        '48': '10.8rem',     // 12rem * 0.9 = 10.8rem
        '52': '11.7rem',     // 13rem * 0.9 = 11.7rem
        '56': '12.6rem',     // 14rem * 0.9 = 12.6rem
        '60': '13.5rem',     // 15rem * 0.9 = 13.5rem
        '64': '14.4rem',     // 16rem * 0.9 = 14.4rem
        '72': '16.2rem',     // 18rem * 0.9 = 16.2rem
        '80': '18rem',       // 20rem * 0.9 = 18rem
        '96': '21.6rem',     // 24rem * 0.9 = 21.6rem
      },
      colors: {
        // Colores oficiales de la Junta de Andalucía
        junta: {
          green: {
            DEFAULT: '#006A4E',
            50: '#E6F0ED',
            100: '#CCE1DA',
            200: '#99C3B5',
            300: '#66A591',
            400: '#33876C',
            500: '#006A4E',
            600: '#00553E',
            700: '#00402F',
            800: '#002B1F',
            900: '#001510',
          },
          yellow: {
            DEFAULT: '#F3B20A',
            50: '#FEF8E8',
            100: '#FDF1D1',
            200: '#FBE3A3',
            300: '#F9D575',
            400: '#F7C747',
            500: '#F3B20A',
            600: '#C28E08',
            700: '#926A06',
            800: '#614704',
            900: '#312302',
          },
        },
        // Paleta complementaria ADA (tonos verdes/teal sobrios)
        ada: {
          primary: {
            DEFAULT: '#0F6D5D',
            50: '#E6F2F0',
            100: '#CCE6E1',
            200: '#99CDC3',
            300: '#66B4A6',
            400: '#339B88',
            500: '#0F6D5D',
            600: '#0C574A',
            700: '#094138',
            800: '#062B25',
            900: '#041613',
          },
          accent: {
            DEFAULT: '#00A86B',
            50: '#E6F8F1',
            100: '#CCF2E3',
            200: '#99E6C7',
            300: '#66D9AB',
            400: '#33CD8F',
            500: '#00A86B',
            600: '#008656',
            700: '#006541',
            800: '#00432B',
            900: '#002216',
          }
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
