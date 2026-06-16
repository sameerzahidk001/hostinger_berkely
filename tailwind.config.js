module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./resources/**/*.ts",
  ],
  theme: {
    fontFamily: {
      canela: ["canela", "Canela", "sans-serif"],
      ghothic: ["trade-ghothic", "sans-serif"],
    },
    extend: {
      screens: { xxs: "320px", xs: "420px" },
      colors: {
        primary_orange: "#f8961f",
        primary: "#1E1E1E",
        secondary: "#656f77",
        light: "#f3f4f4",
        gray_one: "#8996a0",
        dark: "#1e1e1e",
        crimson: "#A41034",
        dark_light: "#181818",
        yellow: "#FFD575",
        navy: "#000435",
      },
    },
  },
  plugins: [],
}
