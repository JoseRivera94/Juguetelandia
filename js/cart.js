document.addEventListener('DOMContentLoaded', function() {
    // Cargar carrito al abrir la página
    loadCart();
    
    // Manejar el botón de checkout
    document.getElementById('checkoutBtn').addEventListener('click', function() {
        // Obtener dirección de envío del usuario
        fetch('php/get_user_address.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('shippingAddress').textContent = data.address;
                    
                    // Mostrar modal de confirmación
                    const checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
                    checkoutModal.show();
                } else {
                    alert('Error al obtener la dirección de envío');
                }
            });
    });
    
    // Confirmar pedido
    document.getElementById('confirmCheckout').addEventListener('click', function() {
        fetch('php/checkout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Pedido realizado con éxito. Número de pedido: ${data.orderId}`);
                window.location.href = 'index.html';
            } else {
                alert(`Error al realizar el pedido: ${data.message}`);
            }
            
            // Cerrar modal
            const checkoutModal = bootstrap.Modal.getInstance(document.getElementById('checkoutModal'));
            checkoutModal.hide();
        });
    });
});

function loadCart() {
    fetch('php/cart_operations.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.items.length === 0) {
                    // Mostrar mensaje de carrito vacío
                    document.getElementById('emptyCartMessage').classList.remove('d-none');
                    document.getElementById('cartContent').classList.add('d-none');
                } else {
                    // Mostrar items del carrito
                    document.getElementById('emptyCartMessage').classList.add('d-none');
                    document.getElementById('cartContent').classList.remove('d-none');
                    
                    renderCartItems(data.items);
                    updateOrderSummary(data.total);
                }
            } else {
                alert('Error al cargar el carrito');
            }
        });
}

function renderCartItems(items) {
    const cartItemsContainer = document.getElementById('cartItems');
    cartItemsContainer.innerHTML = '';
    
    items.forEach(item => {
        const itemElement = document.createElement('div');
        itemElement.className = 'cart-item';
        itemElement.innerHTML = `
            <div class="row">
                <div class="col-3">
                    <img src="${item.imagen || 'images/placeholder.jpg'}" alt="${item.nombre}" class="img-fluid rounded">
                </div>
                <div class="col-6">
                    <h6>${item.nombre}</h6>
                    <p class="text-muted mb-1">Edad: ${item.edad}</p>
                    <p class="mb-1">Precio: S/ ${item.precio.toFixed(2)}</p>
                    <div class="input-group input-group-sm" style="width: 120px;">
                        <button class="btn btn-outline-secondary decrease-qty" data-id="${item.juguete_id}" type="button">-</button>
                        <input type="number" class="form-control text-center qty-input" value="${item.cantidad}" min="1" max="${item.stock}" data-id="${item.juguete_id}">
                        <button class="btn btn-outline-secondary increase-qty" data-id="${item.juguete_id}" type="button">+</button>
                    </div>
                </div>
                <div class="col-3 text-end">
                    <p class="fw-bold">S/ ${item.monto_total.toFixed(2)}</p>
                    <button class="btn btn-sm btn-outline-danger remove-item" data-id="${item.juguete_id}">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        `;
        
        cartItemsContainer.appendChild(itemElement);
    });
    
    // Agregar event listeners para los controles de cantidad
    document.querySelectorAll('.decrease-qty').forEach(button => {
        button.addEventListener('click', function() {
            const toyId = this.getAttribute('data-id');
            const input = this.nextElementSibling;
            let newQty = parseInt(input.value) - 1;
            
            if (newQty < 1) newQty = 1;
            
            updateCartItem(toyId, newQty);
        });
    });
    
    document.querySelectorAll('.increase-qty').forEach(button => {
        button.addEventListener('click', function() {
            const toyId = this.getAttribute('data-id');
            const input = this.previousElementSibling;
            const max = parseInt(input.getAttribute('max'));
            let newQty = parseInt(input.value) + 1;
            
            if (newQty > max) newQty = max;
            
            updateCartItem(toyId, newQty);
        });
    });
    
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', function() {
            const toyId = this.getAttribute('data-id');
            const max = parseInt(this.getAttribute('max'));
            let newQty = parseInt(this.value);
            
            if (isNaN(newQty) newQty = 1;
            if (newQty < 1) newQty = 1;
            if (newQty > max) newQty = max;
            
            this.value = newQty;
            updateCartItem(toyId, newQty);
        });
    });
    
    // Agregar event listeners para eliminar items
    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', function() {
            const toyId = this.getAttribute('data-id');
            
            if (confirm('¿Estás seguro de eliminar este producto de tu carrito?')) {
                removeCartItem(toyId);
            }
        });
    });
}

function updateOrderSummary(total) {
    document.getElementById('subtotal').textContent = `S/ ${total.toFixed(2)}`;
    document.getElementById('total').textContent = `S/ ${total.toFixed(2)}`;
}

function updateCartItem(toyId, quantity) {
    fetch('php/cart_operations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update&toyId=${toyId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCart(); // Recargar el carrito
        } else {
            alert('Error al actualizar la cantidad: ' + data.message);
            loadCart(); // Recargar para mostrar valores correctos
        }
    });
}

function removeCartItem(toyId) {
    fetch('php/cart_operations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove&toyId=${toyId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCart(); // Recargar el carrito
        } else {
            alert('Error al eliminar el producto: ' + data.message);
        }
    });
}