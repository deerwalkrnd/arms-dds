/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
  ],
  theme: {
    extend: {
      colors: {
        //dark orange is used as variable for dhading school color palette
        "dark-orange": "#00B3E6",
        "light-orange": "#FCA311",
        "dark-gray": "#f5f5f5",
        "custom-black": "#36312F",
        "tableHead-gray": "#f5f5f5",
        "background-gray": "#E5E5E5",
        "topNavbar":"#767676"
      }
    },
  },
  plugins: [],
}

