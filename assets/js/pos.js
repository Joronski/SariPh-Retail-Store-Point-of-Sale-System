let cart = [];
let lastTransactionId = null;

$(document).ready(function() {
    loadCart();
    
    // Product search
    $('#productSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.product-item').each(function() {
            const productName = $(this).data('name').toLowerCase();
            const visible = productName.includes(searchTerm);
            $(this).toggle(visible);
        });
        
        // Check if barcode (Enter key)
        if (event.key === 'Enter' && searchTerm.length > 0) {
            searchProductByBarcode(searchTerm);
        }
    });
    
    // Add product to cart
    $('.product-item').on('click', function() {
        const product = {
            id: $(this).data('id'),
            name: $(this).data('name'),
            price: parseFloat($(this).data('price')),
            stock: parseInt($(this).data('stock'))
        };
        
        addToCart(product);
    });
    
    // Discount type change
    $('#discountType').on('change', function() {
        updateCart();
    });
    
    // Checkout button
    $('#btnCheckout').on('click', function() {
        if (cart.length === 0) {
            alert('Cart is empty!');
            return;
        }
        
        const total = calculateTotal();
        $('#checkoutTotal').val(formatCurrency(total));
        $('#paymentAmount').val('');
        $('#changeAmount').val('₱0.00');
        openModal('checkoutModal');
        $('#paymentAmount').focus();
    });
    
    // Payment amount change
    $('#paymentAmount').on('keyup', function() {
        const total = calculateTotal();
        const payment = parseFloat($(this).val()) || 0;
        const change = payment - total;
        
        $('#changeAmount').val(formatCurrency(change >= 0 ? change : 0));
    });
    
    // Process payment
    $('#btnProcessPayment').on('click', function() {
        processSale();
    });
    
    // Cancel sale
    $('#btnCancelSale').on('click', function() {
        if (cart.length === 0) {
            alert('Cart is already empty!');
            return;
        }
        
        if (confirm('Are you sure you want to cancel this sale?')) {
            cancelSale();
        }
    });
    
    // Reprint receipt
    $('#btnReprint').on('click', function() {
        if (!lastTransactionId) {
            alert('No transaction to reprint!');
            return;
        }
        
        reprintReceipt(lastTransactionId);
    });
});

function searchProductByBarcode(barcode) {
    // Search for product by barcode in the grid
    let found = false;
    $('.product-item').each(function() {
        const productName = $(this).data('name').toLowerCase();
        if (productName.includes(barcode)) {
            $(this).click();
            found = true;
            return false; // break loop
        }
    });
    
    if (!found) {
        alert('Product not found!');
    }
    
    $('#productSearch').val('');
    $('.product-item').show();
}

function addToCart(product) {
    // Check stock
    const existingItem = cart.find(item => item.id === product.id);
    const currentQty = existingItem ? existingItem.quantity : 0;
    
    if (currentQty >= product.stock) {
        alert('Insufficient stock!');
        return;
    }
    
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: product.price,
            quantity: 1
        });
    }
    
    updateCart();
    saveCart();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCart();
    saveCart();
}

function updateQuantity(index, change) {
    if (cart[index].quantity + change <= 0) {
        removeFromCart(index);
        return;
    }
    
    cart[index].quantity += change;
    updateCart();
    saveCart();
}

function voidItem(index) {
    if (confirm('Are you sure you want to void this item?')) {
        removeFromCart(index);
    }
}

function updateCart() {
    const cartItemsDiv = $('#cartItems');
    
    if (cart.length === 0) {
        cartItemsDiv.html('<p style="text-align: center; color: #7f8c8d; padding: 20px;">Cart is empty</p>');
        $('#subtotal').text('₱0.00');
        $('#discount').text('₱0.00');
        $('#total').text('₱0.00');
        return;
    }
    
    let html = '';
    cart.forEach((item, index) => {
        const subtotal = item.price * item.quantity;
        html += `
            <div class="cart-item">
                <div style="flex: 1;">
                    <div style="font-weight: 600;">${item.name}</div>
                    <div style="font-size: 0.9rem; color: #7f8c8d;">
                        ₱${item.price.toFixed(2)} x ${item.quantity}
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="font-weight: 600;">₱${subtotal.toFixed(2)}</div>
                    <button class="btn btn-secondary" style="padding: 5px 10px;" onclick="updateQuantity(${index}, -1)">-</button>
                    <button class="btn btn-secondary" style="padding: 5px 10px;" onclick="updateQuantity(${index}, 1)">+</button>
                    <button class="btn btn-danger" style="padding: 5px 10px;" onclick="voidItem(${index})">×</button>
                </div>
            </div>
        `;
    });
    
    cartItemsDiv.html(html);
    
    // Calculate totals
    const subtotal = calculateSubtotal();
    const discount = calculateDiscount(subtotal);
    const total = subtotal - discount;
    
    $('#subtotal').text(formatCurrency(subtotal));
    $('#discount').text(formatCurrency(discount));
    $('#total').text(formatCurrency(total));
}

function calculateSubtotal() {
    return cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
}

function calculateDiscount(subtotal) {
    const discountType = $('#discountType').val();
    
    if (discountType === 'None') {
        return 0;
    }
    
    // All discounts are 20%
    return subtotal * 0.20;
}

function calculateTotal() {
    const subtotal = calculateSubtotal();
    const discount = calculateDiscount(subtotal);
    return subtotal - discount;
}

function saveCart() {
    localStorage.setItem('pos_cart', JSON.stringify(cart));
}

function loadCart() {
    const savedCart = localStorage.getItem('pos_cart');
    if (savedCart) {
        cart = JSON.parse(savedCart);
        updateCart();
    }
}

function clearCart() {
    cart = [];
    localStorage.removeItem('pos_cart');
    updateCart();
}

function processSale() {
    const total = calculateTotal();
    const payment = parseFloat($('#paymentAmount').val()) || 0;
    
    if (payment < total) {
        alert('Insufficient payment amount!');
        return;
    }
    
    const change = payment - total;
    const subtotal = calculateSubtotal();
    const discountType = $('#discountType').val();
    const discount = calculateDiscount(subtotal);
    
    const saleData = {
        items: cart,
        subtotal: subtotal,
        discount_type: discountType,
        discount_amount: discount,
        total_amount: total,
        payment_amount: payment,
        change_amount: change
    };
    
    $.ajax({
        url: '/sariph-pos/modules/pos/process_sale.php',
        method: 'POST',
        data: JSON.stringify(saleData),
        contentType: 'application/json',
        success: function(response) {
            const result = JSON.parse(response);
            
            if (result.success) {
                lastTransactionId = result.sale_id;
                closeModal('checkoutModal');
                showReceipt(result);
                clearCart();
            } else {
                alert('Error processing sale: ' + result.message);
            }
        },
        error: function() {
            alert('Error processing sale. Please try again.');
        }
    });
}

function showReceipt(data) {
    const receipt = generateReceiptHTML(data);
    $('#receiptContent').html(receipt);
    openModal('receiptModal');
}

function generateReceiptHTML(data) {
    let itemsHTML = '';
    data.items.forEach(item => {
        itemsHTML += `
            <div class="receipt-item">
                <span>${item.name}</span>
                <span>₱${(item.price * item.quantity).toFixed(2)}</span>
            </div>
            <div style="font-size: 0.85rem; color: #666; margin-bottom: 5px;">
                ${item.quantity} x ₱${item.price.toFixed(2)}
            </div>
        `;
    });
    
    return `
        <div class="receipt">
            <div class="receipt-header">
                <h3 style="margin: 0;">${data.store_name}</h3>
                <p style="margin: 5px 0; font-size: 0.9rem;">${data.store_address}</p>
                <p style="margin: 5px 0; font-size: 0.9rem;">${data.store_contact}</p>
                <p style="margin: 5px 0; font-size: 0.9rem;">${data.store_tin}</p>
            </div>
            
            <div style="margin: 15px 0; text-align: center;">
                <div><strong>Transaction #: ${data.transaction_number}</strong></div>
                <div style="font-size: 0.85rem;">${data.date_time}</div>
                <div style="font-size: 0.85rem;">Cashier: ${data.cashier_name}</div>
            </div>
            
            <div class="receipt-items">
                ${itemsHTML}
            </div>
            
            <div class="receipt-footer">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>Subtotal:</span>
                    <span>₱${data.subtotal.toFixed(2)}</span>
                </div>
                ${data.discount_type !== 'None' ? `
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>Discount (${data.discount_type}):</span>
                    <span>₱${data.discount_amount.toFixed(2)}</span>
                </div>
                ` : ''}
                <div class="receipt-total">
                    <span>TOTAL:</span>
                    <span>₱${data.total_amount.toFixed(2)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 10px;">
                    <span>Payment:</span>
                    <span>₱${data.payment_amount.toFixed(2)}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Change:</span>
                    <span>₱${data.change_amount.toFixed(2)}</span>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 20px; font-size: 0.85rem;">
                <p>Thank you for shopping!</p>
                <p>Please come again.</p>
            </div>
        </div>
    `;
}

function printReceipt() {
    const receiptContent = document.getElementById('receiptContent').innerHTML;
    const printWindow = window.open('', '', 'width=400,height=600');
    printWindow.document.write(`
        <html>
        <head>
            <title>Print Receipt</title>
            <style>
                body { font-family: 'Courier New', monospace; }
                .receipt { padding: 20px; }
            </style>
        </head>
        <body>
            ${receiptContent}
            <script>
                window.onload = function() {
                    window.print();
                    window.close();
                };
            </script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

function cancelSale() {
    $.ajax({
        url: '/sariph-pos/modules/pos/cancel_sale.php',
        method: 'POST',
        data: JSON.stringify({ items: cart }),
        contentType: 'application/json',
        success: function(response) {
            const result = JSON.parse(response);
            
            if (result.success) {
                alert('Sale cancelled successfully.');
                clearCart();
            } else {
                alert('Error cancelling sale: ' + result.message);
            }
        },
        error: function() {
            alert('Error cancelling sale. Please try again.');
        }
    });
}

function reprintReceipt(saleId) {
    $.ajax({
        url: '/sariph-pos/modules/pos/reprint_receipt.php',
        method: 'POST',
        data: JSON.stringify({ sale_id: saleId }),
        contentType: 'application/json',
        success: function(response) {
            const result = JSON.parse(response);
            
            if (result.success) {
                result.is_reprint = true;
                showReceipt(result);
            } else {
                alert('Error reprinting receipt: ' + result.message);
            }
        },
        error: function() {
            alert('Error reprinting receipt. Please try again.');
        }
    });
}