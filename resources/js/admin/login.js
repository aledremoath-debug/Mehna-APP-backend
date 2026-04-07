// document.addEventListener('DOMContentLoaded', function () {
//     const loginForm = document.querySelector('form');
//     const emailInput = document.getElementById('email');
//     const passwordInput = document.getElementById('password');

//     if (loginForm) {
//         loginForm.addEventListener('submit', function (e) {
//             // Basic client-side validation
//             let isValid = true;

//             if (!emailInput.value || !emailInput.value.includes('@')) {
//                 isValid = false;
//                 emailInput.classList.add('border-red-500');
//             } else {
//                 emailInput.classList.remove('border-red-500');
//             }

//             if (!passwordInput.value || passwordInput.value.length < 6) {
//                 isValid = false;
//                 passwordInput.classList.add('border-red-500');
//             } else {
//                 passwordInput.classList.remove('border-red-500');
//             }

//             if (!isValid) {
//                 e.preventDefault();
//                 // Optional: Show toast or error message
//             }
//         });
//     }

//     // Input focus effects
//     const inputs = document.querySelectorAll('.form-input');
//     inputs.forEach(input => {
//         input.addEventListener('focus', () => {
//             input.parentElement.classList.add('focused');
//         });
//         input.addEventListener('blur', () => {
//             input.parentElement.classList.remove('focused');
//         });
//     });
// });
