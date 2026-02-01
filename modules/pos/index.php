<?php
    $page_title = 'Point of Sale';
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/header.php';

    requireRole(['Cashier', 'Supervisor', 'Administrator']);

    $database = new Database();
    $conn = $database->getConnection();

    // Get all active products
    $products = [];
    try {
        $stmt = $conn->prepare("
            SELECT product_id, barcode, product_name, price, stock_quantity
            FROM products
            WHERE is_active = 1
            ORDER BY product_name
        ");
        $stmt->execute();
        $products = $stmt->fetchAll();
    } catch(PDOException $e) {
        // Error handling
    }
?>

<div class="pos-container">
    <!-- Products Section -->
    <div class="pos-products">
        <div class="card-header">
            <h3>Products</h3>
        </div>
        
        <div class="search-box">
            <input type="text" id="productSearch" class="form-control" placeholder="Search by name or scan barcode...">
        </div>
        
        <div class="product-grid" id="productGrid">
            <?php foreach($products as $product): ?>
                <div class="product-item" 
                     data-id="<?php echo $product['product_id']; ?>"
                     data-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                     data-price="<?php echo $product['price']; ?>"
                     data-stock="<?php echo $product['stock_quantity']; ?>">
                    <div class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                    <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                    <div style="font-size: 0.8rem; color: #7f8c8d;">Stock: <?php echo $product['stock_quantity']; ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Cart Section -->
    <div class="pos-cart">
        <div class="card-header">
            <h3>Cart</h3>
        </div>
        
        <div class="cart-items" id="cartItems">
            <p style="text-align: center; color: #7f8c8d; padding: 20px;">Cart is empty</p>
        </div>
        
        <div class="cart-total">
            <div class="cart-total-row">
                <span>Subtotal:</span>
                <span id="subtotal">₱0.00</span>
            </div>
            <div class="cart-total-row">
                <span>Discount:</span>
                <span id="discount">₱0.00</span>
            </div>
            <div class="cart-total-row grand-total">
                <span>Total:</span>
                <span id="total">₱0.00</span>
            </div>
        </div>
        
        <div class="form-group">
            <label for="discountType">Discount Type</label>
            <select id="discountType" class="form-control">
                <option value="None">None</option>
                <option value="Senior Citizen">Senior Citizen (20%)</option>
                <option value="Person With Disability">PWD (20%)</option>
                <option value="Athlete">Athlete (20%)</option>
                <option value="Solo Parent">Solo Parent (20%)</option>
            </select>
        </div>
        
        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
            <button class="btn btn-success" id="btnCheckout" style="flex: 1;">Checkout</button>
            <button class="btn btn-danger" id="btnCancelSale" style="flex: 1;">Cancel Sale</button>
        </div>
        
        <button class="btn btn-warning" id="btnReprint" style="width: 100%;">Reprint Last Receipt</button>
    </div>
</div>

<!-- Checkout Modal -->
<div id="checkoutModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="closeModal('checkoutModal')">&times;</span>
            <h3>Checkout</h3>
        </div>
        
        <div class="form-group">
            <label>Total Amount</label>
            <input type="text" id="checkoutTotal" class="form-control" readonly>
        </div>
        
        <div class="form-group">
            <label for="paymentAmount">Payment Amount</label>
            <input type="number" id="paymentAmount" class="form-control" step="0.01" min="0">
        </div>
        
        <div class="form-group">
            <label>Change</label>
            <input type="text" id="changeAmount" class="form-control" readonly>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-success" id="btnProcessPayment">Process Payment</button>
            <button class="btn btn-secondary" onclick="closeModal('checkoutModal')">Cancel</button>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div id="receiptModal" class="modal">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <span class="close" onclick="closeModal('receiptModal')">&times;</span>
            <h3>Receipt</h3>
        </div>
        
        <div id="receiptContent"></div>
        
        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button class="btn btn-primary" onclick="printReceipt()">Print</button>
            <button class="btn btn-secondary" onclick="closeModal('receiptModal'); location.reload();">New Transaction</button>
        </div>
    </div>
</div>

<!-- Post Void Modal -->
<div id="postVoidModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="closeModal('postVoidModal')">&times;</span>
            <h3>Post Void Transaction</h3>
        </div>
        
        <div class="form-group">
            <label for="voidTransactionNumber">Transaction Number</label>
            <input type="text" id="voidTransactionNumber" class="form-control">
        </div>
        
        <div class="form-group">
            <label for="voidReason">Reason for Voiding</label>
            <textarea id="voidReason" class="form-control" rows="3"></textarea>
        </div>
        
        <div class="form-group">
            <label for="supervisorUsername">Supervisor Username</label>
            <input type="text" id="supervisorUsername" class="form-control">
        </div>
        
        <div class="form-group">
            <label for="supervisorPassword">Supervisor Password</label>
            <input type="password" id="supervisorPassword" class="form-control">
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-danger" id="btnProcessVoid">Process Void</button>
            <button class="btn btn-secondary" onclick="closeModal('postVoidModal')">Cancel</button>
        </div>
    </div>
</div>

<script src="/sariph-pos/assets/js/pos.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>