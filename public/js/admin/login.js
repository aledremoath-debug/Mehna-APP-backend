document.addEventListener('DOMContentLoaded', function () {
    // عناصر DOM
    const characters = document.querySelectorAll('.character');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('passwordToggle');
    const passwordIcon = document.getElementById('passwordIcon');
    const loginForm = document.getElementById('loginForm');
    const characterMessage = document.getElementById('characterMessage');

    // حالة كلمة المرور (ظاهرة/مخفية)
    let isPasswordVisible = false;
    // حالة لمعرفة ما إذا كانت الشخصيات تنظر إلى الحقول أم لا
    let charactersLookingAtFields = true;

    // تهيئة الشخصيات عند التحميل
    function initializeCharacters() {
        // عند تحميل الصفحة، الشخصيات تنظر إلى الحقول وتكون مبتسمة
        lookAtInput(emailInput);

        // حركات بهلوانية لكل شخصية
        characters.forEach((character, index) => {
            setTimeout(() => {
                // حركات مختلفة لكل شخصية
                switch (index) {
                    case 0:
                        character.style.animation = 'bounce 2s ease-in-out 2';
                        break;
                    case 1:
                        character.querySelector('.left-arm').style.animation = 'wave 1.5s ease-in-out 2';
                        break;
                    case 2:
                        character.querySelector('.right-arm').style.animation = 'wave 1.5s ease-in-out 2 reverse';
                        break;
                    case 3:
                        character.style.animation = 'spin 2s linear 1';
                        break;
                }

                // إعادة تعيين الحركة بعد الانتهاء
                setTimeout(() => {
                    character.style.animation = '';
                    character.querySelectorAll('.arm, .leg').forEach(limb => {
                        limb.style.animation = '';
                    });
                }, 3000);
            }, index * 300);
        });

        // عرض رسالة ترحيبية
        showCharacterMessage("Welcome! We're ready to help you login.");

        // إضافة حركة خفيفة مستمرة للشخصيات
        setInterval(() => {
            if (charactersLookingAtFields) {
                characters.forEach((character, index) => {
                    setTimeout(() => {
                        character.style.transform = `translateY(${Math.sin(Date.now() / 1000 + index) * 3}px)`;
                    }, index * 100);
                });
            }
        }, 100);
    }

    // عرض رسالة من الشخصيات
    function showCharacterMessage(message) {
        characterMessage.textContent = message;
        characterMessage.classList.add('show');

        // إخفاء الرسالة بعد 5 ثوانٍ
        setTimeout(() => {
            characterMessage.classList.remove('show');
        }, 5000);
    }

    // جعل الشخصيات تنظر إلى حقل الإدخال النشط
    function lookAtInput(inputElement) {
        // إذا كانت الشخصيات لا تنظر إلى الحقول (بسبب إخفاء كلمة المرور)
        if (!charactersLookingAtFields) return;

        characters.forEach(character => {
            const eyes = character.querySelectorAll('.eye');

            // جعل العيون تنظر إلى اليمين (نحو الحقول)
            eyes.forEach(eye => {
                eye.classList.remove('looking-left');
                eye.classList.add('looking-right');
            });

            // تعبير الفم إلى ابتسامة
            const mouth = character.querySelector('.character-mouth');
            mouth.classList.remove('sad');
            mouth.classList.add('happy');

            // تحريك الأذرع للأعلى قليلاً (سعادة)
            const arms = character.querySelectorAll('.arm');
            arms.forEach(arm => {
                arm.style.transform = 'rotate(-10deg)';
            });
        });
    }

    // جعل الشخصيات تنظر بعيدًا عن الحقول (حزينة)
    function lookAwayFromFields() {
        charactersLookingAtFields = false;

        characters.forEach(character => {
            const eyes = character.querySelectorAll('.eye');

            // جعل العيون تنظر إلى اليسار (بعيدًا عن الحقول)
            eyes.forEach(eye => {
                eye.classList.remove('looking-right');
                eye.classList.add('looking-left');
            });

            // تعبير الفم إلى حزن
            const mouth = character.querySelector('.character-mouth');
            mouth.classList.remove('happy');
            mouth.classList.add('sad');

            // تحريك الأذرع للأسفل (حزن)
            const arms = character.querySelectorAll('.arm');
            arms.forEach(arm => {
                arm.style.transform = 'rotate(15deg)';
            });

            // حركة حزن خفيفة
            character.style.animation = 'sad-sway 3s ease-in-out infinite';
        });

        showCharacterMessage("We can't see your password now, but that's more secure!");
    }

    // إعادة الشخصيات للنظر إلى الحقول (سعيدة)
    function lookBackAtFields() {
        charactersLookingAtFields = true;

        characters.forEach(character => {
            character.style.animation = '';

            // النظر إلى حقل كلمة المرور
            const eyes = character.querySelectorAll('.eye');
            eyes.forEach(eye => {
                eye.classList.remove('looking-left');
                eye.classList.add('looking-right');
            });

            // تعبير الفم إلى ابتسامة
            const mouth = character.querySelector('.character-mouth');
            mouth.classList.remove('sad');
            mouth.classList.add('happy');

            // تحريك الأذرع للاحتفال
            const arms = character.querySelectorAll('.arm');
            arms.forEach(arm => {
                arm.style.transform = 'rotate(-20deg)';
            });
        });

        showCharacterMessage("We can see your password now! But don't worry, we'll keep it safe!");
    }

    // التفاعل مع إظهار/إخفاء كلمة المرور
    function togglePasswordVisibility() {
        isPasswordVisible = !isPasswordVisible;

        if (isPasswordVisible) {
            // إظهار كلمة المرور
            passwordInput.type = 'text';
            passwordIcon.classList.remove('fa-eye');
            passwordIcon.classList.add('fa-eye-slash');

            // الشخصيات تعود للنظر إلى الحقول بابتسامة
            lookBackAtFields();
        } else {
            // إخفاء كلمة المرور
            passwordInput.type = 'password';
            passwordIcon.classList.remove('fa-eye-slash');
            passwordIcon.classList.add('fa-eye');

            // الشخصيات تنظر بعيدًا عن الحقول بحزن
            lookAwayFromFields();
        }
    }

    // إرسال النموذج - السماح بالإرسال الفعلي إلى السيرفر
    function handleLogin(e) {
        // الحصول على البيانات
        const email = emailInput.value;
        const password = passwordInput.value;

        // التحقق البسيط من جانب العميل
        if (!email || !password) {
            e.preventDefault(); // نمنع الإرسال فقط إذا كانت الحقول فارغة
            showCharacterMessage("Please fill in all fields!");
            return;
        }

        // عرض رسالة تحميل
        showCharacterMessage("Checking your credentials...");

        // حركة احتفالية قبل إرسال النموذج
        characters.forEach(character => {
            character.style.animation = 'bounce 0.8s ease-in-out 2';
        });

        // السماح للنموذج بالإرسال إلى السيرفر (لا نستخدم e.preventDefault())
    }

    // تهيئة الأحداث
    function initEvents() {
        // التركيز على حقول الإدخال
        emailInput.addEventListener('focus', () => {
            if (charactersLookingAtFields) {
                lookAtInput(emailInput);
                showCharacterMessage("Enter your email address!");
            }
        });

        passwordInput.addEventListener('focus', () => {
            if (charactersLookingAtFields) {
                lookAtInput(passwordInput);
                showCharacterMessage("Now enter your password!");
            }
        });

        // زر إظهار/إخفاء كلمة المرور
        if (passwordToggle) {
            passwordToggle.addEventListener('click', togglePasswordVisibility);
        }

        // إرسال النموذج
        if (loginForm) {
            loginForm.addEventListener('submit', handleLogin);
        }

        // حركة عند التحويم على الشخصيات
        characters.forEach(character => {
            character.addEventListener('mouseenter', function () {
                this.style.transform = 'scale(1.05)';
            });

            character.addEventListener('mouseleave', function () {
                this.style.transform = '';
            });
        });
    }

    // تهيئة التطبيق
    initializeCharacters();
    initEvents();
});