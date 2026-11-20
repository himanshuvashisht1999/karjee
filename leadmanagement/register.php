<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>New Lead Registration</title>
    <meta name="description" content="Register a new lead in the lead management system" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="assets/css/style.css?v=1.1.2" />
    <!-- SweetAlert2 library for gorgeous and immersive alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'assets/php/header.php'; ?>
    <?php include 'assets/php/sidebar.php'; ?>
    
    <main class="main-content">
        <section class="form-section">
            <h2><i class="fa-solid fa-user-plus text-primary"></i> Register New Lead</h2>
            <form id="lead-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="contact"><i class="fa-solid fa-phone text-muted"></i> Contact No</label>
                        <div style="position: relative; width: 100%;">
                            <input type="tel" id="contact" name="contact" required placeholder="e.g. 9876543210" autocomplete="off" />
                            <div id="contact-options" class="custom-select-options" style="margin-top: 4px;"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="name"><i class="fa-solid fa-user text-muted"></i> Farmer / Lead Name</label>
                        <input type="text" id="name" name="name" required placeholder="Enter name" />
                    </div>
                    <div class="form-group">
                        <label for="state-search"><i class="fa-solid fa-map text-muted"></i> State</label>
                        <div class="custom-select-wrapper" id="state-wrapper">
                            <input type="text" id="state-search" class="custom-select-search" placeholder="Search and select State..." autocomplete="off" required />
                            <input type="hidden" id="state" name="state" required />
                            <span class="custom-select-arrow"><i class="fa-solid fa-chevron-down"></i></span>
                            <div id="state-options" class="custom-select-options"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="district-search"><i class="fa-solid fa-location-crosshairs text-muted"></i> District</label>
                        <div class="custom-select-wrapper" id="district-wrapper">
                            <input type="text" id="district-search" class="custom-select-search" placeholder="Search and select District..." autocomplete="off" required disabled />
                            <input type="hidden" id="district" name="district" required />
                            <span class="custom-select-arrow"><i class="fa-solid fa-chevron-down"></i></span>
                            <div id="district-options" class="custom-select-options"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="city"><i class="fa-solid fa-tree-city text-muted"></i> City / Village</label>
                        <input type="text" id="city" name="city" required placeholder="Enter city or village" />
                    </div>
                    <!-- Advanced Multi-Product Selection Panel -->
                    <div class="form-group full-width" style="border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); padding: calc(var(--spacing) * 1.2); background: rgba(255, 255, 255, 0.4); margin-bottom: var(--spacing); box-shadow: inset 0 2px 4px 0 rgba(148, 163, 184, 0.03);">
                        <label style="font-size: 1rem; font-weight: 600; color: var(--primary-accent); margin-bottom: 0.75rem; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-basket-shopping"></i> Enquiry Product List
                        </label>
                        
                        <div class="multi-product-form-grid" style="display: grid; grid-template-columns: 2fr 1fr auto; align-items: flex-end; gap: 12px; margin-bottom: 1.25rem;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-size: 0.85rem; margin-bottom: 0.3rem;"><i class="fa-solid fa-seedling text-muted"></i> Product</label>
                                <div class="custom-select-wrapper" id="product-wrapper">
                                    <input type="text" id="product-search" class="custom-select-search" placeholder="Search product..." autocomplete="off" />
                                    <input type="hidden" id="product" />
                                    <span class="custom-select-arrow"><i class="fa-solid fa-chevron-down"></i></span>
                                    <div id="product-options" class="custom-select-options"></div>
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="quantity" style="font-size: 0.85rem; margin-bottom: 0.3rem;"><i class="fa-solid fa-cubes text-muted"></i> Quantity</label>
                                <input type="number" id="quantity" min="1" placeholder="Qty" style="height: auto; padding: 0.75rem 1rem;" />
                            </div>
                            <button type="button" id="btn-add-product" class="btn-primary" style="padding: 0.75rem 1.25rem; width: auto; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; border-radius: var(--border-radius-sm); background: var(--secondary-accent); border: none; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25); height: 46px;">
                                <i class="fa-solid fa-plus"></i> Add Product
                            </button>
                        </div>

                        <!-- Selected Products List Table -->
                        <div class="added-products-wrapper" style="border: 1px dashed var(--border-color); border-radius: var(--border-radius-sm); padding: 0.5rem; background: rgba(255,255,255,0.8); overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;" id="added-products-table">
                                <thead>
                                    <tr style="border-bottom: 1px solid var(--border-color); color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px;">
                                        <th style="padding: 8px 12px; text-align: left;">Product</th>
                                        <th style="padding: 8px 12px; text-align: center; width: 120px;">Qty</th>
                                        <th style="padding: 8px 12px; text-align: center; width: 80px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="added-products-list">
                                    <tr class="empty-product-row">
                                        <td colspan="3" style="text-align: center; color: var(--text-light); padding: 1.5rem 0;">No products added to this enquiry yet. Please add at least one.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label for="remark"><i class="fa-regular fa-comment-dots text-muted"></i> Remarks</label>
                        <textarea id="remark" name="remark" rows="3" placeholder="Add details or special requests..."></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-primary" id="submit-btn">
                    <span class="btn-text"><i class="fa-solid fa-paper-plane"></i> Submit Lead</span>
                </button>
            </form>
        </section>
    </main>
    <script src="assets/js/register.js"></script>
</body>
</html>
