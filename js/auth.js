// Validación del formulario de login
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    let isValid = true;
    
    // Validar email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        document.getElementById('emailError').textContent = 'Por favor ingrese un correo electrónico válido';
        isValid = false;
    } else {
        document.getElementById('emailError').textContent = '';
    }
    
    // Validar contraseña segura
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/;
    if (!passwordRegex.test(password)) {
        document.getElementById('passwordError').textContent = 'La contraseña no cumple con los requisitos de seguridad';
        isValid = false;
    } else {
        document.getElementById('passwordError').textContent = '';
    }
    
    if (isValid) {
        this.submit();
    }
});

// Manejo de sesión de usuario
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si el usuario está logueado
    checkAuthStatus();
    
    // Manejar logout
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            fetch('php/logout.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'index.html';
                    }
                });
        });
    }
});

function checkAuthStatus() {
    fetch('php/check_auth.php')
        .then(response => response.json())
        .then(data => {
            if (data.authenticated) {
                // Mostrar información del usuario y opción de logout
                document.getElementById('user-actions').classList.add('d-none');
                document.getElementById('user-info').classList.remove('d-none');
                document.getElementById('username-display').textContent = data.username;
                document.getElementById('cart-count').textContent = data.cartCount || '0';
            } else {
                // Mostrar opciones de login/registro
                document.getElementById('user-actions').classList.remove('d-none');
                document.getElementById('user-info').classList.add('d-none');
            }
        });
}