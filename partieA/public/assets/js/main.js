/**
 * Mini E-Commerce - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Quantity controls
    initQuantityControls();
    
    // Add to cart buttons
    initAddToCart();
    
    // Delete confirmations
    initDeleteConfirmations();
});

/**
 * Initialize quantity control buttons
 */
function initQuantityControls() {
    document.querySelectorAll('.quantity-control').forEach(function(control) {
        const input = control.querySelector('input[type="number"]');
        const minusBtn = control.querySelector('.btn-minus');
        const plusBtn = control.querySelector('.btn-plus');
        
        if (minusBtn) {
            minusBtn.addEventListener('click', function() {
                const currentVal = parseInt(input.value) || 1;
                if (currentVal > 1) {
                    input.value = currentVal - 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }
        
        if (plusBtn) {
            plusBtn.addEventListener('click', function() {
                const currentVal = parseInt(input.value) || 1;
                const max = parseInt(input.getAttribute('max')) || 999;
                if (currentVal < max) {
                    input.value = currentVal + 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }
    });
}

/**
 * Initialize add to cart functionality
 */
function initAddToCart() {
    document.querySelectorAll('.add-to-cart-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';
            btn.disabled = true;
            
            // Re-enable after form submission
            setTimeout(function() {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 1000);
        });
    });
}

/**
 * Initialize delete confirmations
 */
function initDeleteConfirmations() {
    document.querySelectorAll('[data-confirm]').forEach(function(element) {
        element.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * Update cart item quantity via AJAX (optional enhancement)
 */
function updateCartQuantity(productId, quantity) {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    
    fetch('/cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to update cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
