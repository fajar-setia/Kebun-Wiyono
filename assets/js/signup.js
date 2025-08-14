// Password toggle functionality
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'bi bi-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'bi bi-eye';
    }
}

// Form submission with loading effect
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = document.querySelector('.login-btn');
            const btnText = document.querySelector('.btn-text');

            // Add loading state
            submitBtn.classList.add('loading');
            btnText.textContent = 'Signing In...';
            submitBtn.disabled = true;

            // Simulate login process
            setTimeout(() => {
                // Remove loading state
                submitBtn.classList.remove('loading');
                btnText.textContent = 'Sign In';
                submitBtn.disabled = false;


                
                // Show success message (you can replace this with actual login logic)
                loginForm.submit();
            }, 2000);
        });
    }

    // Enhanced input focus effects
    document.querySelectorAll('.form-floating input').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });

    // Floating shapes animation enhancement
    document.querySelectorAll('.shape').forEach((shape, index) => {
        shape.style.animationDuration = (4 + Math.random() * 4) + 's';
        shape.style.animationDelay = (Math.random() * 2) + 's';
    });

    // Add subtle parallax effect to floating shapes
    document.addEventListener('mousemove', (e) => {
        const shapes = document.querySelectorAll('.shape');
        const mouseX = e.clientX / window.innerWidth;
        const mouseY = e.clientY / window.innerHeight;

        shapes.forEach((shape, index) => {
            const speed = (index + 1) * 0.3; // Dikurangi dari 0.5 untuk efek yang lebih halus
            const x = (mouseX - 0.5) * speed;
            const y = (mouseY - 0.5) * speed;

            shape.style.transform = `translate(${x}px, ${y}px)`;
        });
    });

    // Smooth scroll reveal animation
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'slideUp 0.6s ease-out forwards';
            }
        });
    }, observerOptions);

    // Observe form elements for reveal animation
    document.querySelectorAll('.form-group').forEach(el => {
        observer.observe(el);
    });

    // Prevent form submission on Enter key for better UX
    document.querySelectorAll('.form-floating input').forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const form = this.closest('form');
                if (form) {
                    form.dispatchEvent(new Event('submit'));
                }
            }
        });
    });
});