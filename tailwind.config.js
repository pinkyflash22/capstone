/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  theme: {
    extend: {
      colors: {
        priorityHigh: {
          text: '#B23A22',
          bg: '#FBEAE4',
        },
        priorityMedium: {
          text: '#A5680B',
          bg: '#FBF0DD',
        },
        priorityLow: {
          text: '#1E7A4C',
          bg: '#E7F5EC',
        },
        workflowDraft: '#6B6E76',
        workflowApproved: '#1E7A4C',
        aiActionPlan: {
          text: '#7A4FB8',
          bg: '#F3EEFB',
        },
        aiDraft: {
          text: '#7A4FB8',
          bg: '#F3EEFB',
        },
        readyReview: {
          text: '#4A4E58',
          bg: '#F0EEEA',
        },
        suggestedTag: {
          text: '#3A6FB0',
          bg: '#EAF1FB',
        },
        addRegister: '#E4602C',
        revisionFlag: {
          text: '#B8461C',
          bg: '#FCEEE7',
        },
        registerDivider: '#E9E5DE',
      },
    },
  },
  plugins: [],
};


// /** @type {import('tailwindcss').Config} */
// export default {
//   content: [
//     "./resources/**/*.blade.php",
//     "./resources/**/*.js",
//     "./resources/**/*.vue",
//   ],
//   theme: {
//     extend: {},
//   },
//   plugins: [],
// }

