/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
  ],
  theme: {
    extend: {
      fontFamily: {
        "client": ["var(--client-font)"]
      },
      colors: {
        "primary": "var(--primary)",
        "success": "var(--success)",
        "danger": "var(--danger)",

      },
      backgroundImage: {
        "heroBg": "url('/frontend/images/hero/background.jpg')",
        "mobileBg": "url('/frontend/images/hero/mobilebg.png')",
        'userBg': 'linear-gradient(112deg, rgba(255, 255, 255, 0.50) 0%, rgba(255, 255, 255, 0.70) 100%)',
        'cardBg': 'linear-gradient(112deg, rgba(255, 255, 255, 0.50)0%, rgba(255, 255, 255, 0.70)100%)'
      },

      boxShadow: {
        "card": "2px 4px 32px 0px rgba(0, 39, 147, 0.04)",
        "btnDanger": "0px 10px 32px 0px rgba(236, 79, 85, 0.25)",
        "btnNext": "0px 10px 32px 0px rgba(0, 39, 147, 0.25)",
        "btnSuccess": " 0px 10px 32px 0px rgba(65, 214, 106, 0.25)",
        "btnAdmin": '0px 10px 32px 0px rgba(79, 151, 236, 0.25)',
        "btnReception": '0px 10px 32px 0px rgba(148, 79, 236, 0.25)',
        "btnEmployee": '0px 10px 32px 0px rgba(236, 135, 79, 0.25)',
        "idcard": '0px 6px 12px 0px rgba(58, 54, 148, 0.06)'
      }
    },
  },
  plugins: [],
}