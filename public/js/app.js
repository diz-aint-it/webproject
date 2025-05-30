document.addEventListener('DOMContentLoaded', function() {
    // Cart quantity update handlers
    const quantityInputs = document.querySelectorAll('.cart-quantity');
    quantityInputs.forEach(input => {
        input.addEventListener('change', updateCartItem);
    });

    // Add to cart buttons
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', addToCart);
    });

    // Remove from cart buttons
    const removeButtons = document.querySelectorAll('.remove-from-cart');
    removeButtons.forEach(button => {
        button.addEventListener('click', removeFromCart);
    });
});

// Update cart item quantity
async function updateCartItem(event) {
    const input = event.target;
    const productId = input.dataset.productId;
    const quantity = input.value;

    try {
        const response = await fetch(`/cart/update/${productId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ quantity: quantity })
        });

        if (response.ok) {
            const data = await response.json();
            updateCartTotal(data.total);
            showNotification('Cart updated successfully', 'success');
        } else {
            showNotification('Failed to update cart', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    }
}

// Add item to cart
async function addToCart(event) {
    const button = event.target;
    const productId = button.dataset.productId;

    try {
        const response = await fetch(`/cart/add/${productId}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.ok) {
            const data = await response.json();
            updateCartCount(data.cartCount);
            showNotification('Item added to cart', 'success');
        } else {
            showNotification('Failed to add item to cart', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    }
}

// Remove item from cart
async function removeFromCart(event) {
    const button = event.target;
    const productId = button.dataset.productId;

    try {
        const response = await fetch(`/cart/remove/${productId}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (response.ok) {
            const data = await response.json();
            updateCartTotal(data.total);
            button.closest('.cart-item').remove();
            showNotification('Item removed from cart', 'success');
        } else {
            showNotification('Failed to remove item from cart', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    }
}

// Update cart total
function updateCartTotal(total) {
    const totalElement = document.querySelector('.cart-total');
    if (totalElement) {
        totalElement.textContent = `$${total.toFixed(2)}`;
    }
}

// Update cart count
function updateCartCount(count) {
    const countElement = document.querySelector('.cart-count');
    if (countElement) {
        countElement.textContent = count;
    }
}

// Show notification
function showNotification(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('main');
    container.insertBefore(alertDiv, container.firstChild);

    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Form validation
const forms = document.querySelectorAll('.needs-validation');
forms.forEach(form => {
    form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
}); 